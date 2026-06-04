<?php

namespace App\Services;

use App\Libraries\OddsProvider\OddsProviderInterface;
use App\Libraries\OddsProvider\TheOddsApiAdapter;
use App\Libraries\OddsProvider\KambiAdapter;
use App\Models\SportModel;
use App\Models\LeagueModel;
use App\Models\EventModel;
use App\Models\MarketModel;
use App\Models\OddModel;

/**
 * Servicio de Sincronización de Odds.
 * Consume un OddsProvider y persiste eventos/mercados/cuotas en la BD de Codex_ss.
 * Detecta cambios de cuota y dispara broadcast WebSocket.
 */
class OddsSyncService
{
    private OddsProviderInterface $provider;
    private SportModel $sportModel;
    private LeagueModel $leagueModel;
    private EventModel $eventModel;
    private MarketModel $marketModel;
    private OddModel $oddModel;

    private int $eventsCreated = 0;
    private int $eventsUpdated = 0;
    private int $oddsUpdated = 0;
    private int $marketsCreated = 0;
    private array $log = [];

    public function __construct(?OddsProviderInterface $provider = null)
    {
        // Factory: elegir provider según .env
        if ($provider === null) {
            $providerName = getenv('ODDS_PROVIDER') ?: 'theoddsapi';
            $this->provider = match ($providerName) {
                'kambi'       => new KambiAdapter(),
                default       => new TheOddsApiAdapter(),
            };
        } else {
            $this->provider = $provider;
        }

        $this->sportModel  = new SportModel();
        $this->leagueModel = new LeagueModel();
        $this->eventModel  = new EventModel();
        $this->marketModel = new MarketModel();
        $this->oddModel    = new OddModel();
    }

    /**
     * Sincroniza odds para un deporte específico.
     * @param string $sportKey ej: "soccer_argentina_primera_division"
     * @param array $markets ej: ['h2h', 'totals']
     * @return array Resumen de la sincronización
     */
    public function syncSport(string $sportKey, array $markets = ['h2h', 'totals']): array
    {
        $this->log("Iniciando sincronización para: {$sportKey}");

        // 1. Obtener odds del provider
        $events = $this->provider->getOdds($sportKey, $markets);

        if (empty($events)) {
            $this->log("No se encontraron eventos para {$sportKey}");
            return $this->getSummary();
        }

        $this->log("Recibidos " . count($events) . " eventos del provider");

        // 2. Resolver o crear sport + league
        $sportId = $this->resolveOrCreateSport($sportKey, $events);
        $leagueId = $this->resolveOrCreateLeague($sportId, $sportKey, $events);

        // 3. Procesar cada evento
        foreach ($events as $apiEvent) {
            $this->processEvent($apiEvent, $leagueId);
        }

        // Log remaining API requests
        if ($this->provider instanceof TheOddsApiAdapter) {
            $remaining = $this->provider->getRemainingRequests();
            $this->log("Requests API restantes: {$remaining}");
        }

        return $this->getSummary();
    }

    /**
     * Devuelve la lista de deportes disponibles del provider.
     */
    public function listSports(): array
    {
        return $this->provider->getSports();
    }

    /**
     * Procesa un evento individual.
     */
    private function processEvent(array $apiEvent, int $leagueId): void
    {
        $homeTeam = $apiEvent['home_team'] ?? '';
        $awayTeam = $apiEvent['away_team'] ?? '';
        $apiId    = $apiEvent['id'] ?? '';
        $startTime = $apiEvent['commence_time'] ?? null;

        if (empty($homeTeam) || empty($awayTeam)) {
            return;
        }

        // Convertir ISO 8601 a datetime MySQL
        if ($startTime) {
            try {
                $dt = new \DateTime($startTime);
                $dt->setTimezone(new \DateTimeZone('America/Argentina/Buenos_Aires'));
                $startTime = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $startTime = date('Y-m-d H:i:s');
            }
        }

        // Upsert evento por api_fixture_id
        $existingEvent = $this->eventModel
            ->where('api_fixture_id', $apiId)
            ->where('api_provider', $this->provider->getProviderName())
            ->first();

        if ($existingEvent) {
            // Actualizar si cambió algo
            $this->eventModel->update($existingEvent['id'], [
                'start_time' => $startTime,
            ]);
            $eventId = $existingEvent['id'];
            $this->eventsUpdated++;
        } else {
            // Detectar flags de país
            $homeFlag = $this->detectFlag($homeTeam);
            $awayFlag = $this->detectFlag($awayTeam);

            $eventId = $this->eventModel->insert([
                'league_id'      => $leagueId,
                'home_team'      => $homeTeam,
                'away_team'      => $awayTeam,
                'home_flag'      => $homeFlag,
                'away_flag'      => $awayFlag,
                'api_fixture_id' => $apiId,
                'api_provider'   => $this->provider->getProviderName(),
                'start_time'     => $startTime,
                'status'         => 'active',
                'settled'        => 0,
                'score_home'     => 0,
                'score_away'     => 0,
            ]);
            $this->eventsCreated++;
            $this->log("  + Evento: {$homeTeam} vs {$awayTeam}");
        }

        // Procesar cuotas del primer bookmaker disponible
        $bookmakers = $apiEvent['bookmakers'] ?? [];
        if (!empty($bookmakers)) {
            // Preferir Betsson, luego el primero disponible
            $selectedBookmaker = $bookmakers[0];
            foreach ($bookmakers as $bm) {
                if (stripos($bm['key'] ?? '', 'betsson') !== false) {
                    $selectedBookmaker = $bm;
                    break;
                }
            }

            $apiMarkets = $selectedBookmaker['markets'] ?? [];
            foreach ($apiMarkets as $apiMarket) {
                $this->processMarket($eventId, $apiMarket);
            }
        }
    }

    /**
     * Procesa un mercado y sus odds.
     */
    private function processMarket(int $eventId, array $apiMarket): void
    {
        $marketKey = $apiMarket['key'] ?? '';
        $marketName = $this->mapMarketName($marketKey);

        // Buscar mercado existente
        $existingMarket = $this->marketModel
            ->where('event_id', $eventId)
            ->where('name', $marketName)
            ->first();

        if ($existingMarket) {
            $marketId = $existingMarket['id'];
        } else {
            $marketId = $this->marketModel->insert([
                'event_id' => $eventId,
                'name'     => $marketName,
                'type'     => $marketKey,
                'status'   => 'active',
            ]);
            $this->marketsCreated++;
        }

        // Procesar outcomes/odds
        $outcomes = $apiMarket['outcomes'] ?? [];
        foreach ($outcomes as $outcome) {
            $this->processOdd($marketId, $eventId, $outcome);
        }
    }

    /**
     * Procesa una cuota individual. Detecta cambios y dispara WebSocket.
     */
    private function processOdd(int $marketId, int $eventId, array $outcome): void
    {
        $selection = $outcome['name'] ?? '';
        $newValue  = (float) ($outcome['price'] ?? 0);
        $point     = $outcome['point'] ?? null;

        // Si tiene "point" (over/under), incluirlo en el label
        if ($point !== null) {
            $selection = $selection . ' ' . $point;
        }

        // Buscar odd existente
        $existingOdd = $this->oddModel
            ->where('market_id', $marketId)
            ->where('selection', $selection)
            ->first();

        if ($existingOdd) {
            $oldValue = (float) $existingOdd['odds_decimal'];

            if (abs($oldValue - $newValue) > 0.001) {
                // ¡Cambió la cuota! Actualizar y disparar broadcast
                $this->oddModel->update($existingOdd['id'], [
                    'odds_decimal' => $newValue,
                    'status'       => 'active',
                ]);
                $this->oddsUpdated++;

                // Broadcast via WebSocket
                $this->broadcastOddChange($eventId, $existingOdd['id'], $oldValue, $newValue);
            }
        } else {
            $this->oddModel->insert([
                'market_id'    => $marketId,
                'selection'    => $selection,
                'odds_decimal' => $newValue,
                'active'       => 1,
                'status'       => 'active',
            ]);
            $this->oddsUpdated++;
        }
    }

    /**
     * Dispara broadcast WebSocket cuando cambia una cuota.
     */
    private function broadcastOddChange(int $eventId, int $oddId, float $oldValue, float $newValue): void
    {
        $direction = $newValue > $oldValue ? 'up' : 'down';

        try {
            $payload = json_encode([
                'event_id'  => $eventId,
                'odd_id'    => $oddId,
                'old_value' => number_format($oldValue, 2),
                'new_value' => number_format($newValue, 2),
                'status'    => $direction,
            ]);

            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
                    'content' => $payload,
                    'timeout' => 2,
                    'ignore_errors' => true,
                ],
            ]);

            @file_get_contents('http://localhost:3000/broadcast', false, $context);
        } catch (\Exception $e) {
            // WebSocket no disponible, no bloquear la sincronización
        }
    }

    /**
     * Resuelve o crea el sport en la BD.
     */
    private function resolveOrCreateSport(string $sportKey, array $events): int
    {
        // Extraer categoría genérica: "soccer_argentina_xxx" -> "soccer"
        $parts = explode('_', $sportKey);
        $sportSlug = $parts[0] ?? 'other';
        $sportName = ucfirst($sportSlug);

        // Map de slugs a nombres legibles
        $sportNames = [
            'soccer'             => 'Fútbol',
            'basketball'         => 'Básquet',
            'tennis'             => 'Tenis',
            'baseball'           => 'Béisbol',
            'americanfootball'   => 'Fútbol Americano',
            'icehockey'          => 'Hockey sobre Hielo',
            'mma'                => 'MMA',
            'boxing'             => 'Boxeo',
            'cricket'            => 'Cricket',
            'rugbyleague'        => 'Rugby League',
            'rugbyunion'         => 'Rugby Union',
            'golf'               => 'Golf',
            'handball'           => 'Handball',
            'volleyball'         => 'Voley',
        ];
        $sportName = $sportNames[$sportSlug] ?? $sportName;

        $existing = $this->sportModel->where('slug', $sportSlug)->first();
        if ($existing) {
            return (int) $existing['id'];
        }

        return (int) $this->sportModel->insert([
            'name'   => $sportName,
            'slug'   => $sportSlug,
            'icon'   => $sportSlug,
            'active' => 1,
        ]);
    }

    /**
     * Resuelve o crea la liga en la BD.
     */
    private function resolveOrCreateLeague(int $sportId, string $sportKey, array $events): int
    {
        // Usar sport_title del primer evento como nombre de liga
        $leagueName = $events[0]['sport_title'] ?? $sportKey;

        $existing = $this->leagueModel
            ->where('sport_id', $sportId)
            ->where('name', $leagueName)
            ->first();

        if ($existing) {
            return (int) $existing['id'];
        }

        // Detectar país del nombre de la liga
        $country = $this->detectCountryFromLeague($leagueName);

        return (int) $this->leagueModel->insert([
            'sport_id'   => $sportId,
            'name'       => $leagueName,
            'country'    => $country,
            'active'     => 1,
            'sort_order' => 99,
        ]);
    }

    /**
     * Mapea key de mercado de la API al nombre legible usado en Codex_ss.
     */
    private function mapMarketName(string $key): string
    {
        return match ($key) {
            'h2h'       => 'Ganador del Partido',
            'spreads'   => 'Handicap',
            'totals'    => 'Goles - Más/Menos',
            'outrights' => 'Ganador del Torneo',
            'btts'      => 'Ambos Equipos Marcan',
            default     => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    /**
     * Detecta el código de bandera de un equipo.
     */
    private function detectFlag(string $team): string
    {
        $flags = [
            'Argentina' => 'ar', 'River Plate' => 'ar', 'Boca Juniors' => 'ar',
            'Racing' => 'ar', 'Independiente' => 'ar', 'San Lorenzo' => 'ar',
            'Vélez' => 'ar', 'Huracán' => 'ar', 'Lanús' => 'ar',
            'Brasil' => 'br', 'Mexico' => 'mx', 'México' => 'mx',
            'España' => 'es', 'Spain' => 'es', 'Barcelona' => 'es', 'Real Madrid' => 'es',
            'Francia' => 'fr', 'France' => 'fr', 'PSG' => 'fr',
            'Italia' => 'it', 'Italy' => 'it', 'Juventus' => 'it',
            'Alemania' => 'de', 'Germany' => 'de', 'Bayern' => 'de',
            'Inglaterra' => 'gb-eng', 'England' => 'gb-eng',
            'Portugal' => 'pt', 'Países Bajos' => 'nl', 'Netherlands' => 'nl',
            'Uruguay' => 'uy', 'Colombia' => 'co', 'Chile' => 'cl',
            'Perú' => 'pe', 'Ecuador' => 'ec', 'Paraguay' => 'py',
            'Bolivia' => 'bo', 'Venezuela' => 've',
            'Estados Unidos' => 'us', 'USA' => 'us', 'United States' => 'us',
            'Japón' => 'jp', 'Japan' => 'jp', 'Corea' => 'kr', 'Korea' => 'kr',
        ];

        foreach ($flags as $keyword => $flag) {
            if (stripos($team, $keyword) !== false) {
                return $flag;
            }
        }
        return '';
    }

    /**
     * Detecta país desde el nombre de la liga.
     */
    private function detectCountryFromLeague(string $name): string
    {
        $countries = [
            'Argentina' => 'Argentina', 'Primera División' => 'Argentina',
            'Premier League' => 'Inglaterra', 'La Liga' => 'España',
            'Serie A' => 'Italia', 'Bundesliga' => 'Alemania',
            'Ligue 1' => 'Francia', 'MLS' => 'USA', 'Liga MX' => 'México',
            'Brasileirão' => 'Brasil', 'Copa Libertadores' => 'Sudamérica',
            'Champions League' => 'Europa', 'UEFA' => 'Europa',
            'NBA' => 'USA', 'NFL' => 'USA', 'MLB' => 'USA',
        ];

        foreach ($countries as $keyword => $country) {
            if (stripos($name, $keyword) !== false) {
                return $country;
            }
        }
        return '';
    }

    private function log(string $message): void
    {
        $this->log[] = $message;
        log_message('info', "[OddsSync] {$message}");
    }

    public function getSummary(): array
    {
        return [
            'events_created'  => $this->eventsCreated,
            'events_updated'  => $this->eventsUpdated,
            'markets_created' => $this->marketsCreated,
            'odds_updated'    => $this->oddsUpdated,
            'log'             => $this->log,
        ];
    }
}
