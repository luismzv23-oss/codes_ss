<?php

namespace App\Services;

use App\Models\SportModel;
use App\Models\LeagueModel;
use App\Models\EventModel;
use App\Models\MarketModel;
use App\Models\OddModel;
use App\Models\StagedEventModel;
use App\Libraries\AuditLogger;

class EventLoaderService
{
    public function enrichStagedEventMetadata(array $stagedEvent): array
    {
        $sportKey = (string) ($stagedEvent['sport_key'] ?? '');
        $home = (string) ($stagedEvent['home_team'] ?? '');
        $away = (string) ($stagedEvent['away_team'] ?? '');
        $startTime = (string) ($stagedEvent['start_time'] ?? '');

        if ($home === '' || $away === '') {
            return [];
        }

        return $this->enrichEventMetadataFromFreeFeeds($sportKey, $home, $away, $startTime);
    }

    /**
     * Obtener los deportes de fútbol disponibles en la API (o mock)
     */
    public function getAvailableSoccerSports(): array
    {
        $apiKey = env('THE_ODDS_API_KEY') ?: getenv('THE_ODDS_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception("La API Key (THE_ODDS_API_KEY) no está configurada. Por favor, añádela en el archivo .env para extraer información real de la API oficial.");
        }

        try {
            $url = "https://api.the-odds-api.com/v4/sports/?apiKey=" . urlencode($apiKey) . "&all=true";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: CodexSS/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response !== false) {
                $data = json_decode($response, true);
                if (is_array($data)) {
                    $soccerSports = [];
                    foreach ($data as $sport) {
                        $key = $sport['key'] ?? '';
                        $group = $sport['group'] ?? '';
                        if (str_contains(strtolower($group), 'soccer') || str_starts_with($key, 'soccer_')) {
                            $soccerSports[] = [
                                'key'         => $key,
                                'title'       => $sport['title'] ?? $key,
                                'description' => $sport['description'] ?? '',
                                'active'      => (bool)($sport['active'] ?? false)
                            ];
                        }
                    }
                    if (!empty($soccerSports)) {
                        return $soccerSports;
                    }
                }
            } else {
                throw new \Exception("Error al conectar con la API Oficial. Código HTTP: " . $httpCode);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception in getAvailableSoccerSports: ' . $e->getMessage());
            throw $e;
        }

        return [];
    }

    /**
     * Trae eventos de las ligas seleccionadas y los guarda en la tabla de staging
     */
    public function fetchAndStage(array $sportKeys): array
    {
        $apiKey = env('THE_ODDS_API_KEY') ?: getenv('THE_ODDS_API_KEY');
        
        // Generate batch ID (UUID v4 style)
        $batchId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stagedEventModel = new StagedEventModel();
        $totalStaged = 0;
        $duplicatesSkipped = 0;
        $refreshedExisting = 0;

        // If no API key, throw error for real data requirement
        if (empty($apiKey)) {
            throw new \Exception("La API Key (THE_ODDS_API_KEY) no está configurada. Por favor, añádela en el archivo .env para extraer información real de las páginas oficiales.");
        }

        // With API key
        foreach ($sportKeys as $key) {
            try {
                $url = "https://api.the-odds-api.com/v4/sports/" . urlencode($key) . "/odds/?apiKey=" . urlencode($apiKey) . "&regions=us,eu&markets=h2h,totals,btts&oddsFormat=decimal";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'User-Agent: CodexSS/1.0'
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200 || $response === false) {
                    log_message('error', "The Odds API returned HTTP Code $httpCode for sport $key: " . $response);
                    continue;
                }

                $data = json_decode($response, true);
                if (!is_array($data)) {
                    log_message('error', "The Odds API response for $key is not an array: " . $response);
                    continue;
                }

                // Process events
                foreach ($data as $apiEvent) {
                    if (empty($apiEvent['home_team']) || empty($apiEvent['away_team'])) {
                        continue;
                    }

                    $home = $apiEvent['home_team'];
                    $away = $apiEvent['away_team'];
                    $startTime = $this->parseImportedStartTime($apiEvent['commence_time'] ?? null);
                    $metadata = $this->enrichEventMetadataFromFreeFeeds($key, $home, $away, $startTime ?? '');
                    if (!empty($metadata['start_time'])) {
                        $startTime = $metadata['start_time'];
                    }
                    if ($startTime === null) {
                        log_message('warning', "Skipping imported event without real match date: {$home} vs {$away} ({$key})");
                        continue;
                    }
                    $leagueName = $apiEvent['sport_title'] ?? 'Fútbol';
                    
                    // Determine country
                    $country = 'Internacional';
                    if (strpos($key, '_spain') !== false) {
                        $country = 'España';
                    } elseif (strpos($key, '_england') !== false) {
                        $country = 'Inglaterra';
                    } elseif (strpos($key, '_italy') !== false) {
                        $country = 'Italia';
                    } elseif (strpos($key, '_germany') !== false) {
                        $country = 'Alemania';
                    } elseif (strpos($key, '_france') !== false) {
                        $country = 'Francia';
                    } elseif (strpos($key, '_uefa') !== false) {
                        $country = 'Europa';
                    } elseif (strpos($key, '_usa') !== false) {
                        $country = 'USA';
                    } elseif (strpos($key, '_argentina') !== false) {
                        $country = 'Argentina';
                    }

                    // Check duplicate
                    if ($this->isDuplicate($home, $away, $startTime)) {
                        $duplicatesSkipped++;
                        continue;
                    }

                    // Extract markets
                    $markets = [];
                    if (!empty($apiEvent['bookmakers']) && is_array($apiEvent['bookmakers'])) {
                        $selectedBm = null;
                        foreach ($apiEvent['bookmakers'] as $bm) {
                            if (in_array(strtolower($bm['key']), ['pinnacle', 'draftkings', 'betmgm'])) {
                                $selectedBm = $bm;
                                break;
                            }
                        }
                        if (!$selectedBm) {
                            $selectedBm = $apiEvent['bookmakers'][0];
                        }

                        if (!empty($selectedBm['markets']) && is_array($selectedBm['markets'])) {
                            foreach ($selectedBm['markets'] as $m) {
                                $mKey = strtolower($m['key']);
                                $mName = '';
                                $mType = '';
                                $oddsList = [];

                                if ($mKey === 'h2h') {
                                    $mName = 'Ganador del Partido';
                                    $mType = '1x2';
                                    foreach ($m['outcomes'] as $out) {
                                        $sel = $out['name'];
                                        if ($sel === $home) {
                                            $sel = '1';
                                        } elseif ($sel === $away) {
                                            $sel = '2';
                                        } else {
                                            $sel = 'X';
                                        }
                                        $oddsList[] = [
                                            'selection' => $sel,
                                            'odds' => (float)$out['price']
                                        ];
                                    }
                                } elseif ($mKey === 'totals') {
                                    $mName = 'Total de Goles';
                                    $mType = 'totals';
                                    foreach ($m['outcomes'] as $out) {
                                        $point = $out['point'] ?? 2.5;
                                        $name = strtolower($out['name']);
                                        $selPrefix = (strpos($name, 'over') !== false || strpos($name, 'más') !== false || strpos($name, 'mas') !== false) ? 'Over' : 'Under';
                                        $oddsList[] = [
                                            'selection' => $selPrefix . ' ' . $point,
                                            'odds' => (float)$out['price']
                                        ];
                                    }
                                } elseif ($mKey === 'btts') {
                                    $mName = 'Ambos Equipos Anotan';
                                    $mType = 'btts';
                                    foreach ($m['outcomes'] as $out) {
                                        $name = strtolower($out['name']);
                                        $sel = (in_array($name, ['yes', 'si', 'sí'])) ? 'Sí' : 'No';
                                        $oddsList[] = [
                                            'selection' => $sel,
                                            'odds' => (float)$out['price']
                                        ];
                                    }
                                }

                                if (!empty($mType) && !empty($oddsList)) {
                                    $markets[] = [
                                        'name' => $mName,
                                        'type' => $mType,
                                        'odds' => $oddsList
                                    ];
                                }
                            }
                        }
                    }

                    // Fallback to basic 1x2 odds if none found
                    if (empty($markets)) {
                        $markets[] = [
                            'name' => 'Ganador del Partido',
                            'type' => '1x2',
                            'odds' => [
                                ['selection' => '1', 'odds' => 2.10],
                                ['selection' => 'X', 'odds' => 3.20],
                                ['selection' => '2', 'odds' => 2.90],
                            ]
                        ];
                    }

                    $stagedEventModel->insert([
                        'batch_id'       => $batchId,
                        'sport_key'      => $key,
                        'league_name'    => $leagueName,
                        'league_country' => $country,
                        'home_team'      => $home,
                        'away_team'      => $away,
                        'start_time'     => $startTime,
                        'stage'          => $metadata['stage'] ?? null,
                        'group_name'     => $metadata['group_name'] ?? null,
                        'venue'          => $metadata['venue'] ?? null,
                        'venue_url'      => $metadata['venue_url'] ?? null,
                        'odds_data'      => json_encode($markets),
                        'status'         => 'pending_review'
                    ]);
                    $totalStaged++;
                }

            } catch (\Exception $e) {
                log_message('error', "Exception fetching $key: " . $e->getMessage());
            }
        }

        return [
            'batch_id'           => $batchId,
            'total_staged'       => $totalStaged,
            'duplicates_skipped' => $duplicatesSkipped
        ];
    }

    /**
     * Aprueba un evento de staging, registrándolo en la base de datos de eventos y generando sus mercados
     */
    public function approveEvent(int $stagedId, ?int $reviewedBy = null): ?int
    {
        $stagedEventModel = new StagedEventModel();
        $stagedEvent = $stagedEventModel->find($stagedId);
        
        if (!$stagedEvent || $stagedEvent['status'] !== 'pending_review') {
            return null;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Get or Create Sport (Fútbol)
        $sportModel = new SportModel();
        $sport = $sportModel->where('slug', 'futbol')->first();
        if (!$sport) {
            $sportId = $sportModel->insert([
                'name' => 'Fútbol',
                'slug' => 'futbol',
                'icon' => '⚽',
                'active' => 1
            ]);
        } else {
            $sportId = $sport['id'];
        }

        // 2. Get or Create League
        $leagueModel = new LeagueModel();
        $league = $leagueModel->where('name', $stagedEvent['league_name'])
                              ->where('sport_id', $sportId)
                              ->first();
        if (!$league) {
            $leagueId = $leagueModel->insert([
                'sport_id' => $sportId,
                'name'     => $stagedEvent['league_name'],
                'country'  => $stagedEvent['league_country'],
                'active'   => 1
            ]);
        } else {
            $leagueId = $league['id'];
        }

        // 3. Create Event
        $eventModel = new EventModel();
        $existingEvent = $eventModel->where('home_team', $stagedEvent['home_team'])
                                    ->where('away_team', $stagedEvent['away_team'])
                                    ->where('league_id', $leagueId)
                                    ->first();
        
        $eventStatus = 'pending';
        $scoreHome = isset($stagedEvent['score_home']) && is_numeric($stagedEvent['score_home']) ? (int)$stagedEvent['score_home'] : null;
        $scoreAway = isset($stagedEvent['score_away']) && is_numeric($stagedEvent['score_away']) ? (int)$stagedEvent['score_away'] : null;

        if ($scoreHome !== null && $scoreAway !== null) {
            $eventStatus = 'finished';
        }

        if (!$existingEvent) {
            $eventId = $eventModel->insert([
                'league_id'   => $leagueId,
                'home_team'   => $stagedEvent['home_team'],
                'away_team'   => $stagedEvent['away_team'],
                'start_time'  => $stagedEvent['start_time'],
                'stage'       => $stagedEvent['stage'] ?? null,
                'group_name'  => $stagedEvent['group_name'] ?? null,
                'venue'       => $stagedEvent['venue'] ?? null,
                'status'      => $eventStatus,
                'score_home'  => $scoreHome,
                'score_away'  => $scoreAway,
                'settled'     => 0
            ]);
        } else {
            $eventId = $existingEvent['id'];
            $updateData = [
                'start_time'  => $stagedEvent['start_time'],
                'stage'       => ($stagedEvent['stage'] ?? null) ?: ($existingEvent['stage'] ?? null),
                'group_name'  => ($stagedEvent['group_name'] ?? null) ?: ($existingEvent['group_name'] ?? null),
                'venue'       => ($stagedEvent['venue'] ?? null) ?: ($existingEvent['venue'] ?? null),
            ];
            if ($scoreHome !== null && $scoreAway !== null) {
                $updateData['status'] = $eventStatus;
                $updateData['score_home'] = $scoreHome;
                $updateData['score_away'] = $scoreAway;
            }
            $eventModel->update($eventId, $updateData);
        }

        // 4. Create Markets and Odds from staged_event's odds_data
        $oddsData = json_decode($stagedEvent['odds_data'], true);
        if (is_array($oddsData)) {
            $marketModel = new MarketModel();
            $oddModel = new OddModel();
            
            foreach ($oddsData as $m) {
                // Check if market already exists
                $market = $marketModel->where('event_id', $eventId)
                                      ->where('type', $m['type'])
                                      ->first();
                if (!$market) {
                    $marketId = $marketModel->insert([
                        'event_id' => $eventId,
                        'name'     => $m['name'],
                        'type'     => $m['type'],
                        'status'   => 'open'
                    ]);
                } else {
                    $marketId = $market['id'];
                }

                foreach ($m['odds'] as $o) {
                    // Check if selection exists
                    $odd = $oddModel->where('market_id', $marketId)
                                    ->where('selection', $o['selection'])
                                    ->first();
                    if (!$odd) {
                        $oddModel->insert([
                            'market_id'    => $marketId,
                            'selection'    => $o['selection'],
                            'odds_decimal' => $o['odds'],
                            'active'       => 1
                        ]);
                    } else {
                        // Update the odd if it exists
                        $oddModel->update($odd['id'], [
                            'odds_decimal' => $o['odds'],
                            'active'       => 1
                        ]);
                    }
                }
            }
        }

        // 5. Ensure missing standard markets are generated
        $standardMarketService = new StandardMarketService();
        $standardMarketService->ensureForEvent($eventId);

        // 6. Update Staged Event Status
        $stagedEventModel->update($stagedId, [
            'status'      => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'event_id'    => $eventId
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return null;
        }

        // Audit Log
        AuditLogger::log(
            $reviewedBy,
            'approve_staged_event',
            'event',
            $eventId,
            ['staged_id' => $stagedId],
            ['event_id' => $eventId, 'home_team' => $stagedEvent['home_team'], 'away_team' => $stagedEvent['away_team']]
        );

        return (int)$eventId;
    }

    /**
     * Rechaza un evento de staging, marcándolo como tal
     */
    public function rejectEvent(int $stagedId, ?int $reviewedBy = null): bool
    {
        $stagedEventModel = new StagedEventModel();
        $stagedEvent = $stagedEventModel->find($stagedId);
        
        if (!$stagedEvent || $stagedEvent['status'] !== 'pending_review') {
            return false;
        }

        $result = $stagedEventModel->update($stagedId, [
            'status'      => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            // Audit Log
            AuditLogger::log(
                $reviewedBy,
                'reject_staged_event',
                'staged_events',
                $stagedId,
                ['staged_id' => $stagedId, 'status' => 'pending_review'],
                ['status' => 'rejected']
            );
            return true;
        }

        return false;
    }

    /**
     * Aprueba todos los eventos pendientes en un lote (batch_id)
     */
    public function bulkApprove(string $batchId, ?int $reviewedBy = null): array
    {
        $stagedEventModel = new StagedEventModel();
        $pendingEvents = $stagedEventModel->where('batch_id', $batchId)
                                          ->where('status', 'pending_review')
                                          ->findAll();
        
        $approvedCount = 0;
        $failedCount = 0;
        $eventIds = [];

        foreach ($pendingEvents as $se) {
            $eventId = $this->approveEvent($se['id'], $reviewedBy);
            if ($eventId) {
                $approvedCount++;
                $eventIds[] = $eventId;
            } else {
                $failedCount++;
            }
        }

        return [
            'approved_count' => $approvedCount,
            'failed_count'   => $failedCount,
            'event_ids'      => $eventIds
        ];
    }

    /**
     * Limpia registros antiguos de staged_events ya procesados
     */
    public function cleanOldBatches(int $days = 7): int
    {
        $stagedEventModel = new StagedEventModel();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $db = \Config\Database::connect();
        $db->table('staged_events')
           ->where('created_at <', $cutoffDate)
           ->whereIn('status', ['approved', 'rejected'])
           ->delete();
           
        return $db->affectedRows();
    }

    /**
     * Obtener competiciones de football-data.org
     */
    public function getFootballDataCompetitions(): array
    {
        $apiKey = env('FOOTBALL_DATA_API_KEY') ?: getenv('FOOTBALL_DATA_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception("La API Key de Football-Data.org (FOOTBALL_DATA_API_KEY) no está configurada.");
        }

        try {
            $url = "https://api.football-data.org/v4/competitions";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Auth-Token: ' . $apiKey
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response !== false) {
                $data = json_decode($response, true);
                if (isset($data['competitions']) && is_array($data['competitions'])) {
                    $competitions = [];
                    foreach ($data['competitions'] as $comp) {
                        $competitions[] = [
                            'key'         => $comp['code'], // e.g., 'PL', 'CL'
                            'title'       => $comp['name'] ?? $comp['code'],
                            'description' => $comp['area']['name'] ?? 'Internacional',
                            'active'      => true
                        ];
                    }
                    return $competitions;
                }
            } else {
                throw new \Exception("Error al conectar con Football-Data.org. Código HTTP: " . $httpCode);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception in getFootballDataCompetitions: ' . $e->getMessage());
            throw $e;
        }

        return [];
    }

    /**
     * Trae eventos de las competiciones de football-data.org y los guarda en staging
     */
    public function fetchAndStageFootballData(array $competitionCodes): array
    {
        $apiKey = env('FOOTBALL_DATA_API_KEY') ?: getenv('FOOTBALL_DATA_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception("La API Key de Football-Data.org (FOOTBALL_DATA_API_KEY) no está configurada.");
        }

        $batchId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stagedEventModel = new StagedEventModel();
        $totalStaged = 0;
        $duplicatesSkipped = 0;

        foreach ($competitionCodes as $code) {
            try {
                // Fetch scheduled matches for this competition
                $url = "https://api.football-data.org/v4/competitions/" . urlencode($code) . "/matches?status=SCHEDULED";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'X-Auth-Token: ' . $apiKey
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200 || $response === false) {
                    log_message('error', "Football-Data API returned HTTP Code $httpCode for code $code: " . $response);
                    continue;
                }

                $data = json_decode($response, true);
                if (!isset($data['matches']) || !is_array($data['matches'])) {
                    continue;
                }

                $leagueName = $data['competition']['name'] ?? $code;
                $country = $data['competition']['area']['name'] ?? 'Internacional';

                foreach ($data['matches'] as $match) {
                    $home = $match['homeTeam']['name'] ?? null;
                    $away = $match['awayTeam']['name'] ?? null;
                    $utcDate = $match['utcDate'] ?? null;

                    if (!$home || !$away || !$utcDate) {
                        continue;
                    }

                    $startTime = $this->convertToAppTimezone($utcDate, 'UTC');

                    if ($this->isDuplicate($home, $away, $startTime)) {
                        $duplicatesSkipped++;
                        continue;
                    }

                    // Fallback to basic 1x2 odds (Football-Data doesn't provide odds natively in free tier)
                    $markets = [];
                    $markets[] = [
                        'name' => 'Ganador del Partido',
                        'type' => '1x2',
                        'odds' => [
                            ['selection' => '1', 'odds' => 2.10],
                            ['selection' => 'X', 'odds' => 3.20],
                            ['selection' => '2', 'odds' => 2.90],
                        ]
                    ];

                    $stagedEventModel->insert([
                        'batch_id'       => $batchId,
                        'sport_key'      => $code,
                        'league_name'    => $leagueName,
                        'league_country' => $country,
                        'home_team'      => $home,
                        'away_team'      => $away,
                        'start_time'     => $startTime,
                        'odds_data'      => json_encode($markets),
                        'status'         => 'pending_review'
                    ]);
                    $totalStaged++;
                }

            } catch (\Exception $e) {
                log_message('error', "Exception fetching Football-Data $code: " . $e->getMessage());
            }
        }

        return [
            'batch_id'           => $batchId,
            'total_staged'       => $totalStaged,
            'duplicates_skipped' => $duplicatesSkipped
        ];
    }

    /**
     * Trae eventos de Google Sports a través de SerpApi
     */
    public function fetchAndStageSerpApi(string $query = 'partidos de futbol hoy'): array
    {
        $apiKey = env('SERPAPI_KEY') ?: getenv('SERPAPI_KEY');
        if (empty($apiKey)) {
            throw new \Exception("La API Key de SerpApi (SERPAPI_KEY) no está configurada en .env");
        }

        $batchId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stagedEventModel = new StagedEventModel();
        $totalStaged = 0;
        $duplicatesSkipped = 0;
        $refreshedExisting = 0;

        try {
            $normalizedQuery = $this->buildSerpApiSportsQuery($query);
            $url = "https://serpapi.com/search.json?engine=google&q=" . urlencode($normalizedQuery) . "&hl=es&gl=ar&api_key=" . urlencode($apiKey);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                throw new \Exception("SerpApi devolvió un error HTTP $httpCode");
            }

            $data = json_decode($response, true);

            // Si el widget deportivo tiene pestañas, buscar la pestaña de partidos ("Partidos") para obtener la lista completa sin límites
            $siVal = null;
            if (isset($data['knowledge_graph']['tabs']) && is_array($data['knowledge_graph']['tabs'])) {
                foreach ($data['knowledge_graph']['tabs'] as $tab) {
                    $tabText = mb_strtolower($tab['text'] ?? '');
                    if (
                        str_contains($tabText, 'partido') ||
                        str_contains($tabText, 'match') ||
                        str_contains($tabText, 'juego') ||
                        str_contains($tabText, 'calendario') ||
                        str_contains($tabText, 'schedule') ||
                        str_contains($tabText, 'programac') || // programacion / programación
                        str_contains($tabText, 'fixture') ||
                        str_contains($tabText, 'jornada') ||
                        str_contains($tabText, 'resultados') ||
                        str_contains($tabText, 'directo') ||
                        str_contains($tabText, 'en vivo')
                    ) {
                        $siVal = $tab['si'] ?? null;
                        if (!$siVal && isset($tab['serpapi_link'])) {
                            $parsedUrl = parse_url($tab['serpapi_link']);
                            if (isset($parsedUrl['query'])) {
                                parse_str($parsedUrl['query'], $queryParts);
                                $siVal = $queryParts['si'] ?? null;
                            }
                        }
                        if ($siVal) {
                            break;
                        }
                    }
                }

                // Fallback: si no encontramos por palabra clave, pero los partidos iniciales son <= 6 y hay pestañas, elegir la primera pestaña con "si"
                if (!$siVal && isset($data['sports_results']['games']) && count($data['sports_results']['games']) <= 6) {
                    foreach ($data['knowledge_graph']['tabs'] as $tab) {
                        $candidateSi = $tab['si'] ?? null;
                        if (!$candidateSi && isset($tab['serpapi_link'])) {
                            $parsedUrl = parse_url($tab['serpapi_link']);
                            if (isset($parsedUrl['query'])) {
                                parse_str($parsedUrl['query'], $queryParts);
                                $candidateSi = $queryParts['si'] ?? null;
                            }
                        }
                        if ($candidateSi) {
                            $siVal = $candidateSi;
                            break;
                        }
                    }
                }
                
                if ($siVal) {
                    $siUrl = "https://serpapi.com/search.json?engine=google&q=" . urlencode($normalizedQuery) . "&hl=es&gl=ar&api_key=" . urlencode($apiKey) . "&si=" . urlencode($siVal);
                    
                    $ch2 = curl_init();
                    curl_setopt($ch2, CURLOPT_URL, $siUrl);
                    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch2, CURLOPT_TIMEOUT, 12);
                    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                    
                    $response2 = curl_exec($ch2);
                    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                    curl_close($ch2);
                    
                    if ($httpCode2 === 200 && $response2 !== false) {
                        $data2 = json_decode($response2, true);
                        if (isset($data2['sports_results']['games']) && is_array($data2['sports_results']['games'])) {
                            $data = $data2;
                            log_message('info', "SerpApi: Sincronización expandida exitosa usando parámetro 'si'. Encontrados " . count($data['sports_results']['games']) . " partidos.");
                        }
                    }
                }
            }
            
            if (!isset($data['sports_results']['games']) || !is_array($data['sports_results']['games'])) {
                return [
                    'success' => false,
                    'error' => "SerpApi: Google no mostro el widget deportivo (sports_results) para la busqueda '{$normalizedQuery}'. Proba con una busqueda mas deportiva, por ejemplo: 'partidos amistosos internacionales futbol 3 junio 2026', o usa Football-Data/ESPN para fixtures."
                ];
            }

            $leagueName = $this->serpApiText($data['sports_results']['league'] ?? $data['sports_results']['title'] ?? null, $this->guessSerpApiLeagueName($query));
            $leagueName = mb_substr($leagueName, 0, 200);
            $country = 'Internacional';

            foreach ($data['sports_results']['games'] as $match) {
                if (!isset($match['teams']) || count($match['teams']) < 2) {
                    continue;
                }

                $home = $this->serpApiText($match['teams'][0]['name'] ?? null);
                $away = $this->serpApiText($match['teams'][1]['name'] ?? null);
                $dateStr = $this->serpApiText($match['date'] ?? null, ''); // e.g. "Sat, May 25"
                $timeStr = $this->serpApiText($match['time'] ?? null, '00:00'); // if available

                if (!$home || !$away) {
                    continue;
                }

                $home = mb_substr($home, 0, 200);
                $away = mb_substr($away, 0, 200);
                $startTime = $this->parseSerpApiStartTime($match, $query);
                $stage = mb_substr($this->serpApiText($match['stage'] ?? $match['round'] ?? $match['phase'] ?? null, ''), 0, 80);
                $groupName = mb_substr($this->extractGroupName($match, $leagueName), 0, 50);
                $venue = mb_substr($this->extractVenueName($match), 0, 150);
                $venueUrl = $this->buildVenueSearchUrl($venue, $home, $away);

                // Extract scores if match is live or finished
                $scoreHome = null;
                $scoreAway = null;
                if (isset($match['teams'][0]['score']) && is_numeric($match['teams'][0]['score'])) {
                    $scoreHome = (int)$match['teams'][0]['score'];
                }
                if (isset($match['teams'][1]['score']) && is_numeric($match['teams'][1]['score'])) {
                    $scoreAway = (int)$match['teams'][1]['score'];
                }

                $markets = [];
                $markets[] = [
                    'name' => 'Ganador del Partido',
                    'type' => '1x2',
                    'odds' => [
                        ['selection' => '1', 'odds' => 2.10],
                        ['selection' => 'X', 'odds' => 3.20],
                        ['selection' => '2', 'odds' => 2.90],
                    ]
                ];

                $oddsJson = json_encode($markets);
                $existingStaged = $this->findDuplicateStaged($home, $away, $startTime);
                if ($existingStaged) {
                    $stagedEventModel->update((int) $existingStaged['id'], [
                        'batch_id'       => $batchId,
                        'sport_key'      => 'serpapi_football',
                        'league_name'    => $leagueName,
                        'league_country' => $country,
                        'home_team'      => $home,
                        'away_team'      => $away,
                        'score_home'     => $scoreHome,
                        'score_away'     => $scoreAway,
                        'start_time'     => $startTime,
                        'stage'          => $stage ?: null,
                        'group_name'     => $groupName ?: null,
                        'venue'          => $venue ?: null,
                        'venue_url'      => $venueUrl,
                        'odds_data'      => $oddsJson,
                        'status'         => 'pending_review',
                        'reviewed_by'    => null,
                        'reviewed_at'    => null,
                    ]);
                    $refreshedExisting++;
                    continue;
                }

                $existingEvent = $this->findDuplicateEvent($home, $away, $startTime);

                $stagedEventModel->insert([
                    'batch_id'       => $batchId,
                    'sport_key'      => 'serpapi_football',
                    'league_name'    => $leagueName,
                    'league_country' => $country,
                    'home_team'      => $home,
                    'away_team'      => $away,
                    'score_home'     => $scoreHome,
                    'score_away'     => $scoreAway,
                    'start_time'     => $startTime,
                    'stage'          => $stage ?: null,
                    'group_name'     => $groupName ?: null,
                    'venue'          => $venue ?: null,
                    'venue_url'      => $venueUrl,
                    'odds_data'      => $oddsJson,
                    'status'         => 'pending_review',
                    'event_id'       => $existingEvent ? (int) $existingEvent['id'] : null,
                ]);
                if ($existingEvent) {
                    $refreshedExisting++;
                } else {
                    $totalStaged++;
                }
            }

        } catch (\Exception $e) {
            log_message('error', "Exception fetching SerpApi: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Excepción: " . $e->getMessage()
            ];
        }

        return [
            'success'            => true,
            'batch_id'           => $batchId,
            'total_staged'       => $totalStaged,
            'duplicates_skipped' => $duplicatesSkipped,
            'refreshed_existing' => $refreshedExisting
        ];
    }

    private function buildSerpApiSportsQuery(string $query): string
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? $query);

        $query = preg_replace_callback('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', function (array $matches): string {
            $months = [
                1  => 'enero',
                2  => 'febrero',
                3  => 'marzo',
                4  => 'abril',
                5  => 'mayo',
                6  => 'junio',
                7  => 'julio',
                8  => 'agosto',
                9  => 'septiembre',
                10 => 'octubre',
                11 => 'noviembre',
                12 => 'diciembre',
            ];

            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            return $day . ' ' . ($months[$month] ?? $matches[2]) . ' ' . $year;
        }, $query) ?? $query;

        return $query;
    }

    private function guessSerpApiLeagueName(string $query): string
    {
        $query = trim(preg_replace('/\s+\d{1,2}\/\d{1,2}\/\d{4}\s*/', ' ', $query) ?? $query);
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? $query);

        return $query !== '' ? mb_convert_case($query, MB_CASE_TITLE, 'UTF-8') : 'Google Sports';
    }

    private function serpApiText($value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $fallback;
        }

        if (is_array($value)) {
            foreach (['name', 'title', 'league', 'text', 'label', 'displayName'] as $key) {
                if (array_key_exists($key, $value)) {
                    $text = $this->serpApiText($value[$key], '');
                    if ($text !== '') {
                        return $text;
                    }
                }
            }

            $parts = [];
            foreach ($value as $item) {
                $text = $this->serpApiText($item, '');
                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            $text = trim(implode(' ', array_unique($parts)));
            return $text !== '' ? $text : $fallback;
        }

        return $fallback;
    }

    private function enrichEventMetadataFromFreeFeeds(string $sportKey, string $home, string $away, string $startTime): array
    {
        $metadata = $this->findEspnEventMetadata($sportKey, $home, $away, $startTime);

        if (empty($metadata['venue'])) {
            $metadata['venue'] = $this->fallbackHomeVenue($sportKey, $home);
        }

        if (!empty($metadata['venue']) && empty($metadata['venue_url'])) {
            $metadata['venue_url'] = $this->buildVenueSearchUrl($metadata['venue'], $home, $away);
        }

        return array_filter($metadata, static fn($value) => $value !== null && $value !== '');
    }

    private function parseImportedStartTime($value): ?string
    {
        return $this->convertToAppTimezone($value, 'UTC');
    }

    private function findEspnEventMetadata(string $sportKey, string $home, string $away, string $startTime): array
    {
        $path = $this->espnScoreboardPath($sportKey);
        if ($path === '') {
            return [];
        }

        $baseTimestamp = strtotime($startTime) ?: time();
        foreach ([-1, 0, 1] as $offsetDays) {
            $date = date('Ymd', strtotime(($offsetDays >= 0 ? '+' : '') . $offsetDays . ' days', $baseTimestamp));
            $url = "https://site.api.espn.com/apis/site/v2/sports/{$path}/scoreboard?dates={$date}";
            $data = $this->fetchJson($url);
            if (empty($data['events']) || !is_array($data['events'])) {
                continue;
            }

            foreach ($data['events'] as $event) {
                $competition = $event['competitions'][0] ?? [];
                $competitors = $competition['competitors'] ?? [];
                if (count($competitors) < 2) {
                    continue;
                }

                $espnHome = '';
                $espnAway = '';
                foreach ($competitors as $competitor) {
                    $teamName = $competitor['team']['displayName'] ?? $competitor['team']['shortDisplayName'] ?? $competitor['team']['name'] ?? '';
                    if (($competitor['homeAway'] ?? '') === 'home') {
                        $espnHome = $teamName;
                    } else {
                        $espnAway = $teamName;
                    }
                }

                if (!$this->teamsMatch($home, $espnHome) || !$this->teamsMatch($away, $espnAway)) {
                    continue;
                }

                $venue = $this->serpApiText($competition['venue']['fullName'] ?? $competition['venue']['displayName'] ?? $competition['venue']['name'] ?? null, '');
                $eventDate = $this->serpApiText($event['date'] ?? '', '');
                $parsedDate = strtotime($eventDate);

                return [
                    'start_time' => $this->convertToAppTimezone($eventDate, 'UTC'),
                    'stage' => $this->serpApiText($event['season']['name'] ?? $event['week']['text'] ?? null, ''),
                    'group_name' => $this->extractGroupName($event),
                    'venue' => $venue,
                    'venue_url' => $this->buildVenueSearchUrl($venue, $home, $away),
                ];
            }
        }

        return [];
    }

    private function espnScoreboardPath(string $sportKey): string
    {
        $key = strtolower($sportKey);
        if (str_contains($key, 'basketball_nba') || str_contains($key, 'nba')) {
            return 'basketball/nba';
        }
        if (str_contains($key, 'soccer') || str_contains($key, 'football')) {
            return 'soccer/all';
        }

        return '';
    }

    private function fetchJson(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return [];
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }

    private function teamsMatch(string $expected, string $actual): bool
    {
        $expected = $this->normalizeTeamName($expected);
        $actual = $this->normalizeTeamName($actual);

        if ($expected === '' || $actual === '') {
            return false;
        }

        return $expected === $actual || str_contains($actual, $expected) || str_contains($expected, $actual);
    }

    private function normalizeTeamName(string $team): string
    {
        $team = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $team) ?: $team;
        $team = strtolower($team);
        $team = preg_replace('/\b(fc|cf|sc|club|de|la|el|the)\b/', ' ', $team) ?? $team;
        $team = preg_replace('/[^a-z0-9]+/', ' ', $team) ?? $team;
        return trim(preg_replace('/\s+/', ' ', $team) ?? $team);
    }

    private function fallbackHomeVenue(string $sportKey, string $home): string
    {
        if (!str_contains(strtolower($sportKey), 'nba')) {
            return '';
        }

        $venues = [
            'hawks' => 'State Farm Arena',
            'celtics' => 'TD Garden',
            'nets' => 'Barclays Center',
            'hornets' => 'Spectrum Center',
            'bulls' => 'United Center',
            'cavaliers' => 'Rocket Mortgage FieldHouse',
            'mavericks' => 'American Airlines Center',
            'nuggets' => 'Ball Arena',
            'pistons' => 'Little Caesars Arena',
            'warriors' => 'Chase Center',
            'rockets' => 'Toyota Center',
            'pacers' => 'Gainbridge Fieldhouse',
            'clippers' => 'Intuit Dome',
            'lakers' => 'Crypto.com Arena',
            'grizzlies' => 'FedExForum',
            'heat' => 'Kaseya Center',
            'bucks' => 'Fiserv Forum',
            'timberwolves' => 'Target Center',
            'pelicans' => 'Smoothie King Center',
            'knicks' => 'Madison Square Garden',
            'thunder' => 'Paycom Center',
            'magic' => 'Kia Center',
            '76ers' => 'Wells Fargo Center',
            'suns' => 'Footprint Center',
            'trail blazers' => 'Moda Center',
            'kings' => 'Golden 1 Center',
            'spurs' => 'Frost Bank Center',
            'raptors' => 'Scotiabank Arena',
            'jazz' => 'Delta Center',
            'wizards' => 'Capital One Arena',
        ];

        $normalizedHome = $this->normalizeTeamName($home);
        foreach ($venues as $team => $venue) {
            if (str_contains($normalizedHome, $team)) {
                return $venue;
            }
        }

        return '';
    }

    private function parseSerpApiStartTime(array $match, string $query = ''): string
    {
        foreach (['start_time', 'startTime', 'datetime', 'date_time', 'utcDate'] as $key) {
            $candidate = $this->serpApiText($match[$key] ?? null, '');
            if ($candidate !== '') {
                try {
                    $translated = $this->translateSerpApiDate($candidate);
                    $dt = new \DateTime($translated);
                    $dt->setTimezone(new \DateTimeZone('America/Argentina/Buenos_Aires'));
                    return $dt->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $res = $this->convertToAppTimezone($this->translateSerpApiDate($candidate));
                    if ($res !== null) {
                        return $res;
                    }
                }
            }
        }

        $dateStr = $this->serpApiText($match['date'] ?? null, '');
        $timeStr = $this->serpApiText($match['time'] ?? null, '');
        if ($timeStr === '' || preg_match('/en vivo|live|fin|final/i', $timeStr)) {
            $timeStr = '00:00';
        }

        $dateStr = $dateStr !== '' ? $dateStr : $this->extractDateFromSearchQuery($query);

        // Preprocess Spanish date formats like "Mar 2-6", "2-6" or "2/6" -> "YYYY-MM-DD"
        if (preg_match('/\b(?:lun|mar|mié|mie|jue|vie|sáb|sab|dom)?\.?\s*(\d{1,2})[-|\/](\d{1,2})\b/i', $dateStr, $m)) {
            $day = (int)$m[1];
            $month = (int)$m[2];
            $year = date('Y');
            $dateStr = "{$year}-{$month}-{$day}";
        }

        $translated = $this->translateSerpApiDate(trim($dateStr . ' ' . $timeStr));

        if (!preg_match('/\d{4}/', $translated)) {
            $translated .= ' ' . date('Y');
        }

        try {
            $dt = new \DateTime($translated);
            $dt->setTimezone(new \DateTimeZone('America/Argentina/Buenos_Aires'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $timestamp = strtotime($translated);
            if ($timestamp === false) {
                $dt = new \DateTime('today 00:00:00', new \DateTimeZone('America/Argentina/Buenos_Aires'));
                return $dt->format('Y-m-d H:i:s');
            }
            return $this->convertToAppTimezone($timestamp);
        }
    }

    private function translateSerpApiDate(string $value): string
    {
        $spanishMonths = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic', ' de '];
        $englishMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', ' '];
        $spanishDays = ['lun.', 'mar.', 'mie.', 'miÃ©.', 'jue.', 'vie.', 'sab.', 'sÃ¡b.', 'dom.', 'lun', 'mar', 'mie', 'miÃ©', 'jue', 'vie', 'sab', 'sÃ¡b', 'dom'];
        $englishDays = ['Mon', 'Tue', 'Wed', 'Wed', 'Thu', 'Fri', 'Sat', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Wed', 'Thu', 'Fri', 'Sat', 'Sat', 'Sun'];

        $value = str_ireplace($spanishDays, $englishDays, $value);
        $value = str_ireplace($spanishMonths, $englishMonths, $value);
        $value = str_ireplace(['Hoy', 'MaÃ±ana', 'Manana', 'Ayer'], ['Today', 'Tomorrow', 'Tomorrow', 'Yesterday'], $value);
        return str_ireplace(['a. m.', 'p. m.', 'a.m.', 'p.m.'], ['AM', 'PM', 'AM', 'PM'], $value);
    }

    private function extractDateFromSearchQuery(string $query): string
    {
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', $query, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            return "{$year}-{$month}-{$day}";
        }

        // Match Spanish formats like "3 de junio de 2026", "3 de junio 2026", "3 de junio"
        $spanishMonthsRegex = '(ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[a-z]*';
        if (preg_match('/\b(\d{1,2})\s+de\s+' . $spanishMonthsRegex . '\b(?:\s+(?:de\s+)?(\d{4}))?/i', $query, $matches)) {
            $day = (int)$matches[1];
            $monthStr = strtolower($matches[2]);
            $year = isset($matches[3]) ? (int)$matches[3] : (int)date('Y');
            
            $monthsMap = [
                'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
                'jul' => 7, 'ago' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12
            ];
            
            $month = $monthsMap[substr($monthStr, 0, 3)] ?? 1;
            return "{$year}-{$month}-{$day}";
        }

        return date('Y-m-d');
    }

    private function extractGroupName(array $match, string $leagueName = ''): string
    {
        $text = $this->serpApiText($match['group'] ?? $match['group_name'] ?? $match['stage'] ?? $match['round'] ?? $match['phase'] ?? $leagueName, '');
        if (preg_match('/grupo\s+([A-Z0-9]+)/iu', $text, $matches)) {
            return 'Grupo ' . strtoupper($matches[1]);
        }
        if (preg_match('/group\s+([A-Z0-9]+)/iu', $text, $matches)) {
            return 'Grupo ' . strtoupper($matches[1]);
        }

        return '';
    }

    private function extractVenueName(array $match): string
    {
        return $this->serpApiText(
            $match['venue']['name'] ?? $match['venue']['fullName'] ?? $match['venue'] ?? $match['stadium']['name'] ?? $match['stadium'] ?? $match['location'] ?? null,
            ''
        );
    }

    private function buildVenueSearchUrl(string $venue, string $home = '', string $away = ''): ?string
    {
        if (trim($venue) === '') {
            return null;
        }

        $query = trim($venue . ' estadio fachada ' . $home . ' ' . $away);
        return 'https://www.google.com/search?tbm=isch&q=' . rawurlencode($query);
    }

    private function findDuplicateEvent(string $homeTeam, string $awayTeam, string $startTime): ?array
    {
        $db = \Config\Database::connect();

        $timeWindowStart = date('Y-m-d H:i:s', strtotime($startTime . ' -2 hours'));
        $timeWindowEnd = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));

        $event = $db->table('events')
            ->where('home_team', $homeTeam)
            ->where('away_team', $awayTeam)
            ->where('start_time >=', $timeWindowStart)
            ->where('start_time <=', $timeWindowEnd)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $event ?: null;
    }

    private function findDuplicateStaged(string $homeTeam, string $awayTeam, string $startTime): ?array
    {
        $db = \Config\Database::connect();

        $timeWindowStart = date('Y-m-d H:i:s', strtotime($startTime . ' -2 hours'));
        $timeWindowEnd = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));

        $staged = $db->table('staged_events')
            ->where('home_team', $homeTeam)
            ->where('away_team', $awayTeam)
            ->where('start_time >=', $timeWindowStart)
            ->where('start_time <=', $timeWindowEnd)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $staged ?: null;
    }

    /**
     * Verifica duplicados contra la base de datos de eventos y staging
     */
    private function isDuplicate(string $homeTeam, string $awayTeam, string $startTime): bool
    {
        $db = \Config\Database::connect();
        
        $timeWindowStart = date('Y-m-d H:i:s', strtotime($startTime . ' -2 hours'));
        $timeWindowEnd = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));
        
        $existsInEvents = $db->table('events')
            ->where('home_team', $homeTeam)
            ->where('away_team', $awayTeam)
            ->where('start_time >=', $timeWindowStart)
            ->where('start_time <=', $timeWindowEnd)
            ->countAllResults() > 0;
            
        if ($existsInEvents) {
            return true;
        }
        
        $existsInStaging = $db->table('staged_events')
            ->where('home_team', $homeTeam)
            ->where('away_team', $awayTeam)
            ->where('start_time >=', $timeWindowStart)
            ->where('start_time <=', $timeWindowEnd)
            ->whereIn('status', ['pending_review', 'approved'])
            ->countAllResults() > 0;
            
        return $existsInStaging;
    }

    /**
     * Trae eventos deportivos desde la API de scoreboard de ESPN
     */
    public function fetchAndStageESPN(): array
    {
        $batchId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stagedEventModel = new StagedEventModel();
        $totalStaged = 0;
        $duplicatesSkipped = 0;

        try {
            // Se puede agregar parametro dates=YYYYMMDD-YYYYMMDD para un rango
            $url = "https://site.api.espn.com/apis/site/v2/sports/soccer/all/scoreboard";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                throw new \Exception("ESPN API devolvió un error HTTP $httpCode");
            }

            $data = json_decode($response, true);
            
            if (!isset($data['events']) || !is_array($data['events'])) {
                return [
                    'success' => false,
                    'error' => "ESPN API no retornó eventos en su respuesta."
                ];
            }

            $leaguesCache = [];
            if (isset($data['leagues']) && is_array($data['leagues'])) {
                foreach ($data['leagues'] as $lg) {
                    $leaguesCache[$lg['id'] ?? ''] = $lg['name'] ?? 'Liga ESPN';
                }
            }

            foreach ($data['events'] as $event) {
                if (!isset($event['competitions'][0]['competitors'])) {
                    continue;
                }

                $competitors = $event['competitions'][0]['competitors'];
                if (count($competitors) < 2) continue;

                $home = '';
                $away = '';

                foreach ($competitors as $comp) {
                    if (($comp['homeAway'] ?? '') === 'home') {
                        $home = $comp['team']['displayName'] ?? $comp['team']['name'] ?? '';
                    } else {
                        $away = $comp['team']['displayName'] ?? $comp['team']['name'] ?? '';
                    }
                }

                if (!$home || !$away) continue;

                $startTime = $this->convertToAppTimezone($event['date'] ?? date('c'), 'UTC');
                $competition = $event['competitions'][0] ?? [];
                $venue = mb_substr($this->serpApiText($competition['venue']['fullName'] ?? $competition['venue']['displayName'] ?? $competition['venue']['name'] ?? $event['venue']['fullName'] ?? $event['venue']['displayName'] ?? null, ''), 0, 150);
                $stage = mb_substr($this->serpApiText($event['season']['name'] ?? $event['season']['slug'] ?? $event['week']['text'] ?? null, ''), 0, 80);
                $venueUrl = $this->buildVenueSearchUrl($venue, $home, $away);

                // Prevenir duplicados
                if ($this->isDuplicate($home, $away, $startTime)) {
                    $duplicatesSkipped++;
                    continue;
                }

                // Determinar el nombre de la liga (ESPN manda el league id usualmente en season->league_id o similar)
                $leagueName = 'Internacional';
                $leagueId = $event['season']['type'] ?? null; 
                // A veces ESPN scoreboard general pone datos de liga en otros nodos, fallback a nombre estatico
                if (isset($data['leagues'][0]['name'])) {
                    $leagueName = $data['leagues'][0]['name'];
                } elseif (!empty($event['competitions'][0]['notes'][0]['headline'])) {
                    $leagueName = $event['competitions'][0]['notes'][0]['headline'];
                }
                $groupName = mb_substr($this->extractGroupName($event, $leagueName), 0, 50);

                $markets = [];
                $markets[] = [
                    'name' => 'Ganador del Partido',
                    'type' => '1x2',
                    'odds' => [
                        ['selection' => '1', 'odds' => 2.10],
                        ['selection' => 'X', 'odds' => 3.10],
                        ['selection' => '2', 'odds' => 2.80],
                    ]
                ];

                $stagedEventModel->insert([
                    'batch_id'       => $batchId,
                    'sport_key'      => 'espn_football',
                    'league_name'    => mb_substr($leagueName, 0, 100),
                    'league_country' => 'Mundo',
                    'home_team'      => mb_substr($home, 0, 100),
                    'away_team'      => mb_substr($away, 0, 100),
                    'start_time'     => $startTime,
                    'stage'          => $stage ?: null,
                    'group_name'     => $groupName ?: null,
                    'venue'          => $venue ?: null,
                    'venue_url'      => $venueUrl,
                    'odds_data'      => json_encode($markets),
                    'status'         => 'pending_review'
                ]);
                $totalStaged++;
            }

        } catch (\Exception $e) {
            log_message('error', "Exception fetching ESPN: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Excepción: " . $e->getMessage()
            ];
        }

        return [
            'success'            => true,
            'batch_id'           => $batchId,
            'total_staged'       => $totalStaged,
            'duplicates_skipped' => $duplicatesSkipped
        ];
    }

    /**
     * Normalizes and converts an external date string or timestamp to the application's timezone
     */
    private function convertToAppTimezone($value, string $defaultSrcTimezone = 'UTC'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $dt = new \DateTime();
                $dt->setTimestamp((int)$value);
            } else {
                $tz = new \DateTimeZone($defaultSrcTimezone);
                $dt = new \DateTime((string)$value, $tz);
            }

            $appTz = config('App')->appTimezone ?? 'America/Argentina/Buenos_Aires';
            $dt->setTimezone(new \DateTimeZone($appTz));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            log_message('error', 'Error en convertToAppTimezone con valor (' . $value . '): ' . $e->getMessage());
            return null;
        }
    }
}
