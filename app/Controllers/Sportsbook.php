<?php

namespace App\Controllers;

use App\Models\SportModel;
use App\Models\LeagueModel;
use App\Models\EventModel;
use App\Models\MarketModel;
use App\Models\OddModel;
use App\Models\WithdrawalRequestModel;
use App\Models\KYCVerificationModel;
use App\Models\UserModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class Sportsbook extends BaseController
{
    public function index()
    {
        return $this->renderIndex();
    }

    public function seoIndex()
    {
        return $this->renderIndex();
    }

    public function seoSport(string $sportSlug)
    {
        $sport = $this->findSportBySlug($sportSlug);
        if (! $sport) {
            return redirect()->to('/apuestas-deportivas')->with('error', 'Deporte no encontrado.');
        }

        return $this->renderIndex((int) $sport['id']);
    }

    public function seoLeague(string $sportSlug, string $countrySlug, string $leagueSlug)
    {
        $league = $this->findLeagueBySlugs($sportSlug, $countrySlug, $leagueSlug);
        if (! $league) {
            return redirect()->to('/apuestas-deportivas')->with('error', 'Liga no encontrada.');
        }

        return $this->renderIndex(null, (int) $league['id']);
    }

    public function seoEvent(string $sportSlug, string $countrySlug, string $leagueSlug, string $eventSlug)
    {
        $league = $this->findLeagueBySlugs($sportSlug, $countrySlug, $leagueSlug);
        if (! $league) {
            return redirect()->to('/apuestas-deportivas')->with('error', 'Liga no encontrada.');
        }

        $events = (new EventModel())
            ->where('league_id', (int) $league['id'])
            ->whereIn('status', ['pending', 'live'])
            ->findAll();

        foreach ($events as $event) {
            $candidate = $this->slugify($event['home_team'] . '-' . $event['away_team']);
            if ($candidate === $eventSlug || $candidate . '-' . $event['id'] === $eventSlug) {
                return $this->event((int) $event['id']);
            }
        }

        return $this->renderIndex(null, (int) $league['id']);
    }

    public function liveSchedule()
    {
        return $this->renderIndex(null, null, 'live');
    }

    public function bettingRules()
    {
        return view('sportsbook/trust_page', [
            'title' => 'Reglas de apuestas - Codex SS',
            'heading' => 'Reglas de apuestas',
            'intro' => 'Condiciones operativas para aceptar, liquidar y anular apuestas deportivas en Codex SS.',
            'sections' => [
                ['title' => 'Aceptacion de apuestas', 'items' => [
                    'Las cuotas pueden cambiar antes de confirmar el ticket. Si cambian, se solicita aceptacion expresa.',
                    'No se aceptan apuestas en eventos en vivo ni eventos que comienzan dentro de los proximos 30 minutos.',
                    'Una seleccion puede quedar suspendida si el mercado, la cuota o el evento dejan de estar disponibles.',
                ]],
                ['title' => 'Liquidacion', 'items' => [
                    'Los tickets se liquidan segun el resultado oficial cargado por el operador.',
                    'Los mercados anulados se tratan como void y devuelven el importe correspondiente segun el tipo de apuesta.',
                    'Las apuestas combinadas requieren que todas las selecciones validas acierten para pagar.',
                ]],
                ['title' => 'Bet Builder', 'items' => [
                    'Las selecciones del mismo evento se validan por compatibilidad.',
                    'Las combinaciones correlacionadas aplican un ajuste de cuota antes de confirmar el ticket.',
                ]],
            ],
        ]);
    }

    public function responsibleGaming()
    {
        return view('sportsbook/trust_page', [
            'title' => 'Juego responsable - Codex SS',
            'heading' => 'Juego responsable',
            'intro' => 'Herramientas y criterios para mantener una experiencia de juego controlada, transparente y solo para mayores de 18 anos.',
            'sections' => [
                ['title' => 'Mayores de 18', 'items' => [
                    'El uso de la plataforma esta restringido a personas mayores de edad.',
                    'La verificacion KYC puede ser requerida antes de retiros u operaciones sensibles.',
                ]],
                ['title' => 'Limites y autocontrol', 'items' => [
                    'El sistema respeta importes minimos y maximos configurados por riesgo.',
                    'Solicita soporte si necesitas pausar la cuenta, limitar actividad o revisar movimientos.',
                ]],
                ['title' => 'Autoexclusion', 'items' => [
                    'La autoexclusion debe bloquear nuevos tickets, depositos y promociones durante el periodo solicitado.',
                    'Las solicitudes quedan auditadas para seguimiento operativo.',
                ]],
            ],
        ]);
    }

    public function terms()
    {
        return view('sportsbook/trust_page', [
            'title' => 'Terminos y condiciones - Codex SS',
            'heading' => 'Terminos y condiciones',
            'intro' => 'Marco general de uso para cuentas, fondos, tickets, privacidad operativa y obligaciones del usuario.',
            'sections' => [
                ['title' => 'Cuenta', 'items' => [
                    'Cada usuario debe mantener datos reales y actualizados.',
                    'La cuenta puede ser limitada si se detectan operaciones inconsistentes, fraude o incumplimiento de reglas.',
                ]],
                ['title' => 'Fondos', 'items' => [
                    'Los depositos y retiros quedan registrados en la billetera y en el historial transaccional.',
                    'Los retiros requieren KYC aprobado y cuenta destino propia.',
                ]],
                ['title' => 'Jurisdiccion', 'items' => [
                    'La oferta debe limitarse a la jurisdiccion habilitada por el operador.',
                    'La geolocalizacion y los controles regulatorios deben validarse antes de produccion.',
                ]],
            ],
        ]);
    }

    public function support()
    {
        return view('sportsbook/trust_page', [
            'title' => 'Soporte - Codex SS',
            'heading' => 'Centro de soporte',
            'intro' => 'Canales y temas habituales para resolver dudas de cuenta, pagos, KYC, tickets y juego responsable.',
            'sections' => [
                ['title' => 'Temas frecuentes', 'items' => [
                    'Estado de KYC y retiros pendientes.',
                    'Revision de tickets, cuotas cambiadas o mercados anulados.',
                    'Limites de cuenta, seguridad y solicitudes de autoexclusion.',
                ]],
                ['title' => 'Datos utiles', 'items' => [
                    'Inclui tu usuario, numero de ticket o referencia de transaccion al contactar soporte.',
                    'Nunca compartas contrasenas ni codigos de seguridad.',
                ]],
            ],
        ]);
    }

    private function renderIndex(?int $seoSportId = null, ?int $seoLeagueId = null, ?string $forcedTab = null)
    {
        $sportModel  = new SportModel();
        $leagueModel = new LeagueModel();
        $marketModel = new MarketModel();
        $oddModel    = new OddModel();

        // Obtener deportes y ligas
        $sports  = $sportModel->where('active', 1)->findAll();
        $leagues = $leagueModel->where('active', 1)->findAll();

        // Obtener billetera si está logueado
        $walletBalance = 0.00;
        if (session()->get('isLoggedIn')) {
            $walletModel = new \App\Models\WalletModel();
            $wallet = $walletModel->where('user_id', session()->get('user_id'))->first();
            if ($wallet) {
                $walletBalance = (float)$wallet['balance'];
            }
        }

        $activeTab = $forcedTab ?: ($this->request->getGet('tab') ?: 'todos');
        $search = trim((string) $this->request->getGet('q'));
        $selectedLeagueId = $seoLeagueId ?: (int) ($this->request->getGet('league_id') ?: 0);
        $selectedSportId = $seoSportId ?: (int) ($this->request->getGet('sport_id') ?: 0);
        $db = \Config\Database::connect();

        // Actualizar estados de eventos dinámicamente y liquidar apuestas si corresponde
        $this->updateEventStatuses();

        // Obtener eventos pendientes o en vivo con contexto de deporte/liga para filtros y busqueda global.
        $eventQuery = $db->table('events e')
            ->select('e.*, l.name as league_name, l.country as league_country, s.name as sport_name, s.id as sport_id')
            ->join('leagues l', 'l.id = e.league_id')
            ->join('sports s', 's.id = l.sport_id')
            ->where('l.active', 1)
            ->where('s.active', 1)
            ->whereIn('e.status', ['pending', 'live']);

        if ($activeTab === 'live') {
            $eventQuery->where('e.status', 'live');
        } elseif ($activeTab === 'prepartido') {
            $eventQuery->where('e.status', 'pending');
        } elseif ($activeTab === 'en_breve') {
            $eventQuery->where('e.status', 'pending')
                ->where('e.start_time >=', date('Y-m-d H:i:s'))
                ->where('e.start_time <=', date('Y-m-d H:i:s', strtotime('+24 hours')));
        }

        if ($selectedLeagueId > 0) {
            $eventQuery->where('e.league_id', $selectedLeagueId);
        } elseif ($selectedSportId > 0) {
            $eventQuery->where('s.id', $selectedSportId);
        }

        if ($search !== '') {
            $eventQuery->groupStart()
                ->like('e.home_team', $search)
                ->orLike('e.away_team', $search)
                ->orLike('e.venue', $search)
                ->orLike('l.name', $search)
                ->orLike('l.country', $search)
                ->orLike('s.name', $search)
                ->groupEnd();
        }

        if ($activeTab === 'populares') {
            $eventQuery->orderBy('e.league_id', 'ASC')->orderBy('e.start_time', 'ASC');
        } else {
            $eventQuery->orderBy('e.start_time', 'ASC');
        }

        $eventsRaw = $eventQuery->get()->getResultArray();
        $champion = null;
        $selectedLeagueName = null;
        if ($selectedLeagueId > 0) {
            foreach ($leagues as $l) {
                if ((int) $l['id'] === $selectedLeagueId) {
                    $selectedLeagueName = $l['name'];
                    break;
                }
            }

            if ($selectedLeagueName === 'Copa Mundial de la FIFA 2026') {
                $champion = (new \App\Services\WorldCupBracketService())->champion();
            }
        }
        
        // Estructurar los eventos para la vista (Anidar Markets y Odds)
        $events = [];
        foreach ($eventsRaw as $e) {
            $leagueName = $e['league_name'] ?? '';

            $eventData = [
                'id'         => $e['id'],
                'home_flag'  => $e['home_flag'] ?? '',
                'home_team'  => $e['home_team'],
                'away_flag'  => $e['away_flag'] ?? '',
                'away_team'  => $e['away_team'],
                'start_time' => $e['start_time'],
                'venue'      => $e['venue'] ?? '',
                'league'     => $leagueName,
                'country'    => $e['league_country'] ?? '',
                'sport'      => $e['sport_name'] ?? '',
                'status'     => $e['status'],
                'markets'    => []
            ];

            $markets = $marketModel->where('event_id', $e['id'])
                ->where('status', 'open')
                ->findAll();
            foreach ($markets as $m) {
                $odds = $oddModel->where('market_id', $m['id'])
                    ->where('active', 1)
                    ->where('status', 'pending')
                    ->findAll();
                
                $marketData = [
                    'name' => $m['name'],
                    'type' => $m['type'],
                    'odds' => $odds
                ];
                $eventData['markets'][] = $marketData;
            }
            $events[] = $eventData;
        }

        $settingsModel = new \App\Models\SystemSettingModel();
        $settings = $settingsModel->getAllSettings();
        $minStake = (float)($settings['risk_min_stake'] ?? 100);
        $maxStake = (float)($settings['risk_max_stake'] ?? 100000);

        return view('sportsbook/index', [
            'title'   => 'Codex SS - Apuestas Deportivas (Live Feed)',
            'sports'  => $sports,
            'leagues' => $leagues,
            'events'  => $events,
            'champion' => $champion,
            'walletBalance' => $walletBalance,
            'activeTab' => $activeTab,
            'search' => $search,
            'selectedSportId' => $selectedSportId,
            'selectedLeagueId' => $selectedLeagueId,
            'minStake' => $minStake,
            'maxStake' => $maxStake,
        ]);
    }

    private function findSportBySlug(string $slug): ?array
    {
        foreach ((new SportModel())->where('active', 1)->findAll() as $sport) {
            if (($sport['slug'] ?? '') === $slug || $this->slugify($sport['name'] ?? '') === $slug) {
                return $sport;
            }
        }

        return null;
    }

    private function findLeagueBySlugs(string $sportSlug, string $countrySlug, string $leagueSlug): ?array
    {
        $db = \Config\Database::connect();
        $leagues = $db->table('leagues l')
            ->select('l.*, s.slug as sport_slug, s.name as sport_name')
            ->join('sports s', 's.id = l.sport_id')
            ->where('l.active', 1)
            ->where('s.active', 1)
            ->get()
            ->getResultArray();

        foreach ($leagues as $league) {
            $matchesSport = ($league['sport_slug'] ?? '') === $sportSlug
                || $this->slugify($league['sport_name'] ?? '') === $sportSlug;
            $matchesCountry = $this->slugify($league['country'] ?? '') === $countrySlug;
            $matchesLeague = $this->slugify($league['name'] ?? '') === $leagueSlug;

            if ($matchesSport && $matchesCountry && $matchesLeague) {
                return $league;
            }
        }

        return null;
    }

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    public function event($id)
    {
        $sportModel  = new SportModel();
        $leagueModel = new LeagueModel();
        $marketModel = new MarketModel();
        $oddModel    = new OddModel();

        $sports  = $sportModel->where('active', 1)->findAll();
        $leagues = $leagueModel->where('active', 1)->findAll();

        $walletBalance = 0.00;
        if (session()->get('isLoggedIn')) {
            $walletModel = new \App\Models\WalletModel();
            $wallet = $walletModel->where('user_id', session()->get('user_id'))->first();
            if ($wallet) {
                $walletBalance = (float)$wallet['balance'];
            }
        }

        $db = \Config\Database::connect();

        // Actualizar estados de eventos dinámicamente y liquidar apuestas si corresponde
        $this->updateEventStatuses();

        $event = $db->table('events e')
            ->select('e.*, l.name as league_name, l.country as league_country, s.name as sport_name')
            ->join('leagues l', 'l.id = e.league_id')
            ->join('sports s', 's.id = l.sport_id')
            ->where('e.id', $id)
            ->get()
            ->getRowArray();

        if (!$event) {
            return redirect()->to('/')->with('error', 'Evento no encontrado.');
        }

        $markets = [];
        $marketsRaw = $marketModel->where('event_id', $event['id'])->orderBy('id', 'ASC')->findAll();
        foreach ($marketsRaw as $market) {
            $odds = $oddModel->where('market_id', $market['id'])
                ->orderBy('id', 'ASC')
                ->findAll();

            $markets[] = [
                'id' => $market['id'],
                'name' => $market['name'],
                'type' => $market['type'],
                'status' => $market['status'],
                'odds' => $odds,
            ];
        }

        $settingsModel = new \App\Models\SystemSettingModel();
        $settings = $settingsModel->getAllSettings();
        $minStake = (float)($settings['risk_min_stake'] ?? 100);
        $maxStake = (float)($settings['risk_max_stake'] ?? 100000);

        return view('sportsbook/event', [
            'title' => $event['home_team'] . ' vs ' . $event['away_team'] . ' - Codex SS',
            'sports' => $sports,
            'leagues' => $leagues,
            'event' => $event,
            'markets' => $markets,
            'walletBalance' => $walletBalance,
            'minStake' => $minStake,
            'maxStake' => $maxStake,
        ]);
    }

    /**
     * Endpoint para procesar el boleto de apuestas (Fase 9: Checkout)
     */
    public function placeBet()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Debes iniciar sesión para apostar.']);
        }

        $userId = session()->get('user_id');
        $json = $this->request->getJSON(true);

        if (!$json || empty($json['selections']) || empty($json['stake'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Boleto inválido o vacío.']);
        }

        $stake = (float) $json['stake'];
        if ($stake <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'El importe debe ser mayor a 0.']);
        }

        $compliance = (new \App\Services\ComplianceService())->validateStake((int) $userId, $stake);
        if (! $compliance['allowed']) {
            return $this->response->setJSON(['status' => 'error', 'message' => $compliance['message']]);
        }

        $walletModel = new \App\Models\WalletModel();
        $txModel = new \App\Models\TransactionModel();
        $betSlipModel = new \App\Models\BetSlipModel();
        $betSelectionModel = new \App\Models\BetSelectionModel();
        $oddModel = new OddModel();

        // Obtener o crear billetera
        $wallet = $walletModel->where('user_id', $userId)->first();
        if (!$wallet) {
            // Regalo de bienvenida en ARS
            $walletModel->insert(['user_id' => $userId, 'balance' => 50000.00, 'currency' => 'ARS']);
            $wallet = $walletModel->where('user_id', $userId)->first();
            $txModel->insert([
                'wallet_id' => $wallet['id'],
                'type' => 'deposit',
                'amount' => 50000.00,
                'balance_after' => 50000.00,
                'description' => 'Bono de Bienvenida'
            ]);
        }

        // Validar Saldo
        if ((float) $wallet['balance'] < $stake) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Saldo insuficiente. Tu saldo es '.$wallet['balance'].' K']);
        }

        // Iniciar Transacción de Base de Datos
        $db = \Config\Database::connect();
        
        // Actualizar estados de eventos dinámicamente y liquidar apuestas si corresponde
        $this->updateEventStatuses();

        $oddIds = array_map('intval', array_column($json['selections'], 'id'));
        if (empty($oddIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No hay cuotas validas en el boleto.']);
        }

        $currentOdds = $db->table('odds o')
            ->select('o.id, o.selection, o.odds_decimal, o.active, o.status as odd_status, m.name as market_name, m.status as market_status, e.home_team, e.away_team')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->whereIn('o.id', $oddIds)
            ->get()
            ->getResultArray();

        $currentById = [];
        foreach ($currentOdds as $odd) {
            $currentById[(int) $odd['id']] = $odd;
        }

        $changes = [];
        foreach ($json['selections'] as $selection) {
            $oddId = (int) ($selection['id'] ?? 0);
            if (! isset($currentById[$oddId])) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Una cuota seleccionada ya no existe.']);
            }

            $current = $currentById[$oddId];
            $isAvailable = (int) $current['active'] === 1
                && $current['odd_status'] === 'pending'
                && $current['market_status'] === 'open';

            if (! $isAvailable) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Una o mas cuotas seleccionadas estan suspendidas o cerradas.',
                ]);
            }

            $clientOdd = (float) ($selection['odds'] ?? 0);
            $serverOdd = (float) $current['odds_decimal'];
            if (abs($clientOdd - $serverOdd) >= 0.005) {
                $changes[] = [
                    'id' => $oddId,
                    'teams' => $current['home_team'] . ' vs ' . $current['away_team'],
                    'market' => $current['market_name'],
                    'selection' => $current['selection'],
                    'old_odds' => round($clientOdd, 2),
                    'new_odds' => round($serverOdd, 2),
                ];
            }
        }

        if (! empty($changes) && empty($json['accept_odds_changes'])) {
            return $this->response->setJSON([
                'status' => 'odds_changed',
                'message' => 'Una o mas cuotas cambiaron antes de confirmar la apuesta.',
                'changes' => $changes,
            ]);
        }

        $db->transStart();

        // 1. Descontar Saldo
        $newBalance = (float) $wallet['balance'] - $stake;
        $walletModel->update($wallet['id'], ['balance' => $newBalance]);

        // 2. Calcular cuota real usando BetBuilderService para aplicar descuentos por correlación o detectar incompatibilidades.
        $betBuilderService = new \App\Services\BetBuilderService();
        $builderResult = $betBuilderService->calculateCombinedOdds($oddIds);

        if (!$builderResult['valid']) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $builderResult['message']]);
        }

        $totalOdds = $builderResult['odds'];
        $potentialPayout = $stake * $totalOdds;

        $riskResult = (new \App\Services\RiskControlService())->validateBet($userId, $stake, $potentialPayout, $oddIds);
        if (! $riskResult['valid']) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $riskResult['message']]);
        }

        // Determinar si es Bet Builder (si hay al menos un evento con más de 1 selección)
        $isBuilder = 0;
        $maxCorrelationDiscount = 0.00;
        foreach ($builderResult['details'] as $detail) {
            if ($detail['correlation_factor'] > 0) {
                $isBuilder = 1;
            }
            if ($detail['correlation_factor'] > $maxCorrelationDiscount) {
                $maxCorrelationDiscount = $detail['correlation_factor'];
            }
        }

        // 3. Crear Bet Slip
        $betSlipId = $betSlipModel->insert([
            'user_id' => $userId,
            'stake' => $stake,
            'total_odds' => $totalOdds,
            'potential_payout' => $potentialPayout,
            'status' => 'pending',
            'is_builder' => $isBuilder,
            'correlation_discount' => $maxCorrelationDiscount * 100 // Almacenado como porcentaje (ej: 18.00)
        ]);

        // 4. Crear Selecciones del Boleto
        foreach ($json['selections'] as $sel) {
            $dbOdd = $oddModel->find($sel['id']);
            $betSelectionModel->insert([
                'bet_slip_id' => $betSlipId,
                'odd_id' => $dbOdd['id'],
                'odd_at_bet_time' => $dbOdd['odds_decimal'],
                'status' => 'pending'
            ]);
        }

        // 5. Registrar Transacción en Historial
        $txModel->insert([
            'wallet_id' => $wallet['id'],
            'type' => 'bet_placed',
            'amount' => -$stake,
            'balance_after' => $newBalance,
            'reference_id' => $betSlipId,
            'description' => ($isBuilder ? 'Apuestas Creadas (Bet Builder)' : (count($json['selections']) > 1 ? 'Apuesta Combinada' : 'Apuesta Simple')) . ' Ticket #' . $betSlipId
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Error al procesar la apuesta.']);
        }

        return $this->response->setJSON([
            'status' => 'success', 
            'message' => '¡Apuesta aceptada exitosamente!',
            'ticket_id' => $betSlipId,
            'new_balance' => $newBalance
        ]);
    }

    /**
     * Endpoint para calcular cuotas combinadas con correlación al vuelo
     */
    public function calculateBuilder()
    {
        $json = $this->request->getJSON(true);
        if (!$json || empty($json['selections'])) {
            return $this->response->setJSON(['status' => 'success', 'odds' => 0.00, 'message' => 'Sin selecciones.']);
        }

        $oddIds = array_column($json['selections'], 'id');
        
        $betBuilderService = new \App\Services\BetBuilderService();
        $result = $betBuilderService->calculateCombinedOdds($oddIds);

        if (!$result['valid']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $result['message']
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'odds' => $result['odds'],
            'details' => $result['details']
        ]);
    }

    /**
     * Endpoint para ver el historial de apuestas
     */
    public function history()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        $walletModel = new \App\Models\WalletModel();
        $wallet = $walletModel->where('user_id', $userId)->first();
        $walletBalance = $wallet ? (float)$wallet['balance'] : 0.00;

        $db = \Config\Database::connect();

        $status = (string) ($this->request->getGet('status') ?? 'all');
        $search = trim((string) ($this->request->getGet('q') ?? ''));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $allowedStatuses = ['all', 'pending', 'won', 'lost', 'void', 'cashed_out'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $summaryRow = $db->table('bet_slips')
            ->select("
                COUNT(*) as total_tickets,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_tickets,
                COALESCE(SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END), 0) as won_tickets,
                COALESCE(SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END), 0) as lost_tickets,
                COALESCE(SUM(stake), 0) as total_stake,
                COALESCE(SUM(CASE WHEN status = 'won' THEN potential_payout ELSE 0 END), 0) as paid_payout,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN potential_payout ELSE 0 END), 0) as pending_payout
            ", false)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray() ?? [];

        $base = $db->table('bet_slips b')
            ->select('b.*')
            ->where('b.user_id', $userId);

        if ($status !== 'all') {
            $base->where('b.status', $status);
        }

        if ($search !== '') {
            $base->groupStart()
                ->where('b.id', (int) preg_replace('/\D+/', '', $search))
                ->orWhere("EXISTS (
                    SELECT 1
                    FROM bet_selections bs
                    JOIN odds o ON o.id = bs.odd_id
                    JOIN markets m ON m.id = o.market_id
                    JOIN events e ON e.id = m.event_id
                    JOIN leagues l ON l.id = e.league_id
                    WHERE bs.bet_slip_id = b.id
                    AND (
                        e.home_team LIKE " . $db->escape('%' . $search . '%') . "
                        OR e.away_team LIKE " . $db->escape('%' . $search . '%') . "
                        OR l.name LIKE " . $db->escape('%' . $search . '%') . "
                        OR m.name LIKE " . $db->escape('%' . $search . '%') . "
                    )
                )", null, false)
                ->groupEnd();
        }

        $totalTickets = (clone $base)->countAllResults(false);
        $slipsRaw = (clone $base)
            ->orderBy('b.created_at', 'DESC')
            ->orderBy('b.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $history = [];
        foreach ($slipsRaw as $slip) {
            $selectionsRaw = $db->table('bet_selections bs')
                                ->select('bs.*, o.selection as odd_name, m.name as market_name, e.home_team, e.away_team, e.start_time, e.venue, l.name as league_name')
                                ->join('odds o', 'o.id = bs.odd_id')
                                ->join('markets m', 'm.id = o.market_id')
                                ->join('events e', 'e.id = m.event_id')
                                ->join('leagues l', 'l.id = e.league_id')
                                ->where('bs.bet_slip_id', $slip['id'])
                                ->get()->getResultArray();

            $history[] = [
                'slip' => $slip,
                'selections' => $selectionsRaw
            ];
        }

        return view('sportsbook/history', [
            'title'   => 'Mis Apuestas - Codex SS',
            'walletBalance' => $walletBalance,
            'history' => $history,
            'summary' => $summaryRow,
            'status' => $status,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($totalTickets / $perPage)),
            'totalTickets' => $totalTickets,
        ]);
    }

    public function cashOut($slipId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Debes iniciar sesion.']);
        }

        $userId = (int) session()->get('user_id');
        $compliance = (new \App\Services\ComplianceService())->validateUserCanOperate($userId, 'cashout');
        if (! $compliance['allowed']) {
            return $this->response->setJSON(['status' => 'error', 'message' => $compliance['message']]);
        }

        $db = \Config\Database::connect();
        $slip = $db->table('bet_slips')
            ->where('id', (int) $slipId)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (! $slip) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Ticket no encontrado.']);
        }

        $cashOut = new \App\Services\CashOutService();
        $allowed = $cashOut->canCashOut($slip);
        if (! $allowed['allowed']) {
            return $this->response->setJSON(['status' => 'error', 'message' => $allowed['message']]);
        }

        $value = $cashOut->quote($slip);
        if ($value <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cash-out no disponible para este ticket.']);
        }

        $walletModel = new \App\Models\WalletModel();
        $txModel = new \App\Models\TransactionModel();
        $wallet = $walletModel->where('user_id', $userId)->first();
        if (! $wallet) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Billetera no encontrada.']);
        }

        $newBalance = round((float) $wallet['balance'] + $value, 2);

        $db->transStart();
        $db->table('bet_slips')->where('id', (int) $slipId)->where('status', 'pending')->update([
            'status' => 'cashed_out',
            'cashout_value' => $value,
            'cashed_out_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('bet_selections')->where('bet_slip_id', (int) $slipId)->where('status', 'pending')->update([
            'status' => 'void',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $walletModel->update((int) $wallet['id'], ['balance' => $newBalance]);
        $txModel->insert([
            'wallet_id' => (int) $wallet['id'],
            'type' => 'bet_cashout',
            'amount' => $value,
            'balance_after' => $newBalance,
            'reference_id' => (int) $slipId,
            'description' => 'Cash-out Ticket #' . (int) $slipId,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No se pudo procesar el cash-out.']);
        }

        \App\Libraries\AuditLogger::log($userId, 'ticket_cashed_out', 'bet_slip', (int) $slipId, ['status' => $slip['status']], [
            'cashout_value' => $value,
            'balance' => $newBalance,
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Cash-out procesado correctamente.',
            'cashout_value' => $value,
            'new_balance' => $newBalance,
        ]);
    }

    public function ticket($slipId)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $ticket = $this->ticketData((int) $slipId);
        if (!$ticket) {
            return redirect()->to('/sportsbook/history')->with('error', 'Ticket no encontrado.');
        }

        return view('sportsbook/ticket', [
            'title' => 'Ticket #' . str_pad($ticket['slip']['id'], 6, '0', STR_PAD_LEFT),
            'slip' => $ticket['slip'],
            'selections' => $ticket['selections'],
            'username' => session()->get('username'),
            'pdfMode' => false,
        ]);
    }

    public function ticketPdf($slipId)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $ticket = $this->ticketData((int) $slipId);
        if (!$ticket) {
            return redirect()->to('/sportsbook/history')->with('error', 'Ticket no encontrado.');
        }

        $html = view('sportsbook/ticket', [
            'title' => 'Ticket #' . str_pad($ticket['slip']['id'], 6, '0', STR_PAD_LEFT),
            'slip' => $ticket['slip'],
            'selections' => $ticket['selections'],
            'username' => session()->get('username'),
            'pdfMode' => true,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Courier');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper([0, 0, 226.77, 700], 'portrait');
        $dompdf->render();

        $filename = 'ticket-' . str_pad($ticket['slip']['id'], 6, '0', STR_PAD_LEFT) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    private function ticketData(int $slipId): ?array
    {
        $userId = session()->get('user_id');
        $db = \Config\Database::connect();

        $slip = $db->table('bet_slips')
                   ->where('id', $slipId)
                   ->where('user_id', $userId)
                   ->get()
                   ->getRowArray();

        if (!$slip) {
            return null;
        }

        $selections = $db->table('bet_selections bs')
                         ->select('bs.*, o.selection as odd_name, m.name as market_name, e.home_team, e.away_team, e.start_time, e.venue, l.name as league_name')
                         ->join('odds o', 'o.id = bs.odd_id')
                         ->join('markets m', 'm.id = o.market_id')
                         ->join('events e', 'e.id = m.event_id')
                         ->join('leagues l', 'l.id = e.league_id')
                         ->where('bs.bet_slip_id', $slipId)
                         ->get()
                         ->getResultArray();

        return [
            'slip' => $slip,
            'selections' => $selections,
        ];
    }

    /**
     * Endpoint para depositar saldo en la billetera
     */
    public function deposit()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
        }

        $userId = session()->get('user_id');
        $json = $this->request->getJSON(true);

        if (!$json || empty($json['amount']) || (float)$json['amount'] <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Monto inválido.']);
        }

        $amount = (float)$json['amount'];

        $compliance = (new \App\Services\ComplianceService())->validateDeposit((int) $userId, $amount);
        if (! $compliance['allowed']) {
            return $this->response->setJSON(['status' => 'error', 'message' => $compliance['message']]);
        }

        $walletModel = new \App\Models\WalletModel();
        $txModel = new \App\Models\TransactionModel();
        
        $db = \Config\Database::connect();
        $db->transStart();

        $wallet = $walletModel->where('user_id', $userId)->first();
        
        $commission = $amount * 0.10;
        $netAmount = $amount - $commission;
        $methodName = $json['method'] ?? 'Billetera Codex SS';

        // Retrieve settings for target accounts
        $settingModel = new \App\Models\SystemSettingModel();
        $settings = $settingModel->getAllSettings();

        $targetAccount = 'Caja Interna (Manual)';
        if (strpos($methodName, 'Transferencia') !== false || strpos($methodName, 'DEBIN') !== false) {
            $targetAccount = $settings['bank_cbu_cvu'] ?? 'Galicia CBU 0070001230004567891234';
        } else if (strpos($methodName, 'Mercado Pago') !== false) {
            $targetAccount = $settings['mp_qr_account'] ?? 'Mercado Pago Principal';
        } else {
            $targetAccount = $methodName;
        }

        if (!$wallet) {
            $walletModel->insert(['user_id' => $userId, 'balance' => $netAmount, 'currency' => 'ARS']);
            $wallet = $walletModel->where('user_id', $userId)->first();
            $newBalance = $netAmount;
        } else {
            $newBalance = (float)$wallet['balance'] + $netAmount;
            $walletModel->update($wallet['id'], ['balance' => $newBalance]);
        }

        $txModel->insert([
            'wallet_id'      => $wallet['id'],
            'type'           => 'deposit',
            'amount'         => $amount,
            'balance_after'  => $newBalance,
            'description'    => 'Depósito de Usuario (' . $methodName . ')',
            'commission'     => $commission,
            'target_account' => $targetAccount
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Error al procesar el depósito.']);
        }

        return $this->response->setJSON([
            'status' => 'success', 
            'message' => '¡Depósito exitoso!',
            'new_balance' => $newBalance
        ]);
    }

    public function withdrawalRequest()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
        }

        $userModel = new UserModel();
        $user = $userModel->find((int) session()->get('user_id'));
        if (($user['kyc_status'] ?? 'pending') !== 'approved') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Debes tener KYC aprobado para solicitar retiros.',
                'kyc_required' => true,
            ]);
        }

        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $amount = round((float) ($payload['amount'] ?? 0), 2);
        $targetAccount = trim((string) ($payload['target_account'] ?? ''));
        $accountHolder = trim((string) ($payload['account_holder'] ?? ''));
        $accountDocument = preg_replace('/\D+/', '', (string) ($payload['account_document'] ?? ''));
        $ownAccountConfirmed = (bool) ($payload['own_account_confirmed'] ?? false);
        $note = trim((string) ($payload['note'] ?? ''));

        if ($amount <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'El monto del retiro debe ser mayor a 0.']);
        }

        if ($amount < 1000) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'El retiro mínimo es 1000 K.']);
        }

        if ($targetAccount === '' || mb_strlen($targetAccount) < 5) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Indica una cuenta destino válida.']);
        }

        if ($accountHolder === '' || mb_strlen($accountHolder) < 3) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Indica el titular de la cuenta destino.']);
        }

        $userDocument = preg_replace('/\D+/', '', (string) ($user['document_number'] ?? ''));
        if ($accountDocument === '' || $userDocument === '' || $accountDocument !== $userDocument) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'El documento del titular de la cuenta debe coincidir con tu KYC aprobado.',
            ]);
        }

        if (! $ownAccountConfirmed) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Debes confirmar que la cuenta destino es propia.']);
        }

        $userId = (int) session()->get('user_id');
        $db = \Config\Database::connect();
        $walletModel = new \App\Models\WalletModel();
        $requestModel = new WithdrawalRequestModel();

        $wallet = $walletModel->where('user_id', $userId)->first();
        if (!$wallet || (float) $wallet['balance'] < $amount) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Saldo insuficiente para solicitar el retiro.']);
        }

        $currentBalance = (float) $wallet['balance'];
        $newBalance = round($currentBalance - $amount, 2);

        $db->transStart();
        $walletModel->update((int) $wallet['id'], ['balance' => $newBalance]);
        $requestId = $requestModel->insert([
            'user_id' => $userId,
            'wallet_id' => (int) $wallet['id'],
            'amount' => $amount,
            'target_account' => mb_substr($targetAccount, 0, 160),
            'account_holder' => mb_substr($accountHolder, 0, 160),
            'account_document' => mb_substr($accountDocument, 0, 50),
            'own_account_confirmed' => 1,
            'status' => 'pending',
            'user_note' => $note !== '' ? mb_substr($note, 0, 255) : null,
        ]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No se pudo registrar la solicitud de retiro.']);
        }

        \App\Libraries\AuditLogger::log(
            $userId,
            'withdrawal_requested',
            'withdrawal_request',
            (int) $requestId,
            ['balance' => $currentBalance],
            [
                'amount' => $amount,
                'balance' => $newBalance,
                'target_account' => $targetAccount,
                'account_holder' => $accountHolder,
                'account_document' => $accountDocument,
                'own_account_confirmed' => true,
            ]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Solicitud de retiro registrada. Quedará pendiente de aprobación.',
            'request_id' => $requestId,
            'new_balance' => $newBalance,
        ]);
    }

    public function kyc()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $userModel = new UserModel();
        $kycModel = new KYCVerificationModel();
        $user = $userModel->find((int) session()->get('user_id'));
        $record = $kycModel->where('user_id', (int) session()->get('user_id'))->first();

        return view('sportsbook/kyc', [
            'title' => 'Verificacion KYC - Codex SS',
            'user' => $user,
            'kyc' => $record,
        ]);
    }

    public function responsibleLimits()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $userId = (int) session()->get('user_id');
        $walletModel = new \App\Models\WalletModel();
        $wallet = $walletModel->where('user_id', $userId)->first();

        return view('sportsbook/responsible_limits', [
            'title' => 'Limites responsables - Codex SS',
            'limits' => (new \App\Services\ComplianceService())->limitsForUser($userId),
            'walletBalance' => $wallet ? (float) $wallet['balance'] : 0.00,
        ]);
    }

    public function saveResponsibleLimits()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $userId = (int) session()->get('user_id');
        (new \App\Services\ComplianceService())->updateLimits($userId, $this->request->getPost());

        \App\Libraries\AuditLogger::log($userId, 'responsible_limits_updated', 'user', $userId, null, $this->request->getPost());

        return redirect()->to('/sportsbook/responsible-limits')->with('success', 'Limites responsables actualizados.');
    }

    public function selfExclusion()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $days = (int) ($this->request->getPost('days') ?? 0);
        $reason = trim((string) ($this->request->getPost('reason') ?? ''));
        if ($days < 1) {
            return redirect()->to('/sportsbook/responsible-limits')->with('error', 'Indica una duracion valida para la autoexclusion.');
        }

        $userId = (int) session()->get('user_id');
        (new \App\Services\ComplianceService())->selfExclude($userId, $days, $reason);

        \App\Libraries\AuditLogger::log($userId, 'self_exclusion_requested', 'user', $userId, null, [
            'days' => $days,
            'reason' => $reason,
        ]);

        return redirect()->to('/auth/logout');
    }

    public function submitKyc()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $rules = [
            'document_type' => 'required|in_list[dni,passport,license]',
            'document_number' => 'required|min_length[6]|max_length[50]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userId = (int) session()->get('user_id');
        $documentType = (string) $this->request->getPost('document_type');
        $documentNumber = trim((string) $this->request->getPost('document_number'));

        $kycModel = new KYCVerificationModel();
        $existing = $kycModel->where('user_id', $userId)->first();
        $data = [
            'user_id' => $userId,
            'status' => 'pending',
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'rejection_reason' => null,
            'verified_at' => null,
            'verified_by' => null,
        ];

        if ($existing) {
            $kycModel->update((int) $existing['id'], $data);
        } else {
            $kycModel->insert($data);
        }

        (new UserModel())->update($userId, [
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'kyc_status' => 'pending',
        ]);

        \App\Libraries\AuditLogger::log($userId, 'kyc_submitted', 'user', $userId, null, [
            'document_type' => $documentType,
            'document_number' => $documentNumber,
        ]);

        return redirect()->to('/sportsbook/kyc')->with('success', 'Verificacion enviada. Queda pendiente de revision.');
    }
}
