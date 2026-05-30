<?php

namespace App\Controllers;

use App\Libraries\CacheManager;
use App\Libraries\QueueManager;
use App\Services\WorldCupBracketService;
use App\Libraries\AuditLogger;
use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\TransactionModel;
use App\Models\WithdrawalRequestModel;
use App\Models\KYCVerificationModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class Dashboard extends BaseController
{
    private CacheManager $cache;

    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }

    /**
     * Set no-cache headers for HTMX fragment responses
     */
    private function htmxFragment(string $view, array $data = [])
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        return view($view, $data);
    }

    private function csvResponse(string $filename, array $headers, array $rows)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    private function transactionFilters(): array
    {
        $type = (string) ($this->request->getGet('type') ?? 'all');
        $allowedTypes = ['all', 'deposit', 'withdrawal', 'bet_placed', 'bet_won', 'bet_refunded'];
        if (! in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }

        return [
            'q' => trim((string) ($this->request->getGet('q') ?? '')),
            'type' => $type,
            'date_from' => trim((string) ($this->request->getGet('date_from') ?? '')),
            'date_to' => trim((string) ($this->request->getGet('date_to') ?? '')),
        ];
    }

    private function applyTransactionFilters($builder, array $filters)
    {
        if (($filters['type'] ?? 'all') !== 'all') {
            $builder->where('t.type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $builder->where('t.created_at >=', $filters['date_from'] . ' 00:00:00');
        }

        if (! empty($filters['date_to'])) {
            $builder->where('t.created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $txId = (int) preg_replace('/\D+/', '', $search);
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('u.email', $search)
                ->orLike('t.description', $search)
                ->orLike('t.target_account', $search);

            if ($txId > 0) {
                $builder->orWhere('t.id', $txId)
                    ->orWhere('t.reference_id', $txId);
            }

            $builder->groupEnd();
        }

        return $builder;
    }

    private function flagMarkup(?string $code): string
    {
        $code = preg_replace('/[^a-z-]/', '', strtolower((string) $code));
        if ($code === '') {
            return '';
        }

        $styles = [
            'ar' => 'linear-gradient(#74acdf 0 33%, #fff 33% 66%, #74acdf 66%)',
            'at' => 'linear-gradient(#ed2939 0 33%, #fff 33% 66%, #ed2939 66%)',
            'au' => 'linear-gradient(#012169,#012169)',
            'az' => 'linear-gradient(#00b5e2 0 33%,#ef3340 33% 66%,#509e2f 66%)',
            'ba' => 'linear-gradient(135deg,#002395 0 72%,#fecb00 72%)',
            'be' => 'linear-gradient(90deg,#000 0 33%,#fae042 33% 66%,#ed2939 66%)',
            'bo' => 'linear-gradient(#d52b1e 0 33%,#f9e300 33% 66%,#007934 66%)',
            'br' => 'linear-gradient(135deg,#009b3a 0 100%)',
            'ca' => 'linear-gradient(90deg,#d52b1e 0 25%,#fff 25% 75%,#d52b1e 75%)',
            'cd' => 'linear-gradient(135deg,#007fff 0 42%,#f7d618 42% 50%,#ce1021 50% 58%,#007fff 58%)',
            'ch' => 'linear-gradient(#d52b1e,#d52b1e)',
            'cl' => 'linear-gradient(90deg,#0039a6 0 33%,#fff 33%),linear-gradient(#fff 0 50%,#d52b1e 50%)',
            'ci' => 'linear-gradient(90deg,#f77f00 0 33%,#fff 33% 66%,#009e60 66%)',
            'co' => 'linear-gradient(#fcd116 0 50%,#003893 50% 75%,#ce1126 75%)',
            'cv' => 'linear-gradient(#003893 0 50%,#fff 50% 56%,#cf2027 56% 62%,#fff 62% 68%,#003893 68%)',
            'cy' => 'linear-gradient(#fff,#fff)',
            'cw' => 'linear-gradient(#002b7f 0 62%,#f9e814 62% 70%,#002b7f 70%)',
            'cz' => 'linear-gradient(150deg,#11457e 0 35%,transparent 35%),linear-gradient(#fff 0 50%,#d7141a 50%)',
            'de' => 'linear-gradient(#000 0 33%,#dd0000 33% 66%,#ffce00 66%)',
            'dk' => 'linear-gradient(90deg,transparent 0 30%,#fff 30% 40%,transparent 40%),linear-gradient(transparent 0 42%,#fff 42% 56%,transparent 56%),#c60c30',
            'dz' => 'linear-gradient(90deg,#006233 0 50%,#fff 50%)',
            'ec' => 'linear-gradient(#ffd100 0 50%,#034ea2 50% 75%,#ed1c24 75%)',
            'eg' => 'linear-gradient(#ce1126 0 33%,#fff 33% 66%,#000 66%)',
            'es' => 'linear-gradient(#aa151b 0 25%,#f1bf00 25% 75%,#aa151b 75%)',
            'eu' => 'radial-gradient(circle at 50% 50%,#fbbf24 0 7%,transparent 8%),#1d4ed8',
            'fr' => 'linear-gradient(90deg,#0055a4 0 33%,#fff 33% 66%,#ef4135 66%)',
            'gb-eng' => 'linear-gradient(90deg,transparent 0 42%,#ce1124 42% 58%,transparent 58%),linear-gradient(transparent 0 38%,#ce1124 38% 62%,transparent 62%),#fff',
            'gb-sct' => 'linear-gradient(35deg,transparent 0 42%,#fff 42% 58%,transparent 58%),linear-gradient(145deg,transparent 0 42%,#fff 42% 58%,transparent 58%),#0065bd',
            'gh' => 'linear-gradient(#ce1126 0 33%,#fcd116 33% 66%,#006b3f 66%)',
            'gr' => 'repeating-linear-gradient(#0d5eaf 0 11%,#fff 11% 22%)',
            'ht' => 'linear-gradient(#00209f 0 50%,#d21034 50%)',
            'iq' => 'linear-gradient(#ce1126 0 33%,#fff 33% 66%,#000 66%)',
            'ir' => 'linear-gradient(#239f40 0 33%,#fff 33% 66%,#da0000 66%)',
            'it' => 'linear-gradient(90deg,#009246 0 33%,#fff 33% 66%,#ce2b37 66%)',
            'jo' => 'linear-gradient(145deg,#ce1126 0 36%,transparent 36%),linear-gradient(#000 0 33%,#fff 33% 66%,#007a3d 66%)',
            'jp' => 'radial-gradient(circle at 50% 50%,#bc002d 0 28%,transparent 29%),#fff',
            'kr' => 'radial-gradient(circle at 50% 50%,#cd2e3a 0 22%,#0047a0 23% 34%,transparent 35%),#fff',
            'kz' => 'linear-gradient(#00afca,#00afca)',
            'ma' => 'linear-gradient(#c1272d,#c1272d)',
            'mc' => 'linear-gradient(#ce1126 0 50%,#fff 50%)',
            'mx' => 'linear-gradient(90deg,#006847 0 33%,#fff 33% 66%,#ce1126 66%)',
            'nl' => 'linear-gradient(#ae1c28 0 33%,#fff 33% 66%,#21468b 66%)',
            'no' => 'linear-gradient(90deg,transparent 0 28%,#fff 28% 36%,#00205b 36% 48%,#fff 48% 56%,transparent 56%),linear-gradient(transparent 0 34%,#fff 34% 43%,#00205b 43% 57%,#fff 57% 66%,transparent 66%),#ba0c2f',
            'nz' => 'linear-gradient(#00247d,#00247d)',
            'pa' => 'linear-gradient(90deg,#fff 0 50%,#d21034 50%),linear-gradient(#fff 0 50%,#005293 50%)',
            'pe' => 'linear-gradient(90deg,#d91023 0 33%,#fff 33% 66%,#d91023 66%)',
            'pt' => 'linear-gradient(90deg,#006600 0 40%,#ff0000 40%)',
            'py' => 'linear-gradient(#d52b1e 0 33%,#fff 33% 66%,#0038a8 66%)',
            'qa' => 'linear-gradient(90deg,#fff 0 28%,#8a1538 28%)',
            'sa' => 'linear-gradient(#006c35,#006c35)',
            'se' => 'linear-gradient(90deg,transparent 0 30%,#fecc00 30% 42%,transparent 42%),linear-gradient(transparent 0 40%,#fecc00 40% 58%,transparent 58%),#006aa7',
            'sn' => 'linear-gradient(90deg,#00853f 0 33%,#fdef42 33% 66%,#e31b23 66%)',
            'tn' => 'radial-gradient(circle at 50% 50%,#fff 0 28%,transparent 29%),#e70013',
            'tr' => 'radial-gradient(circle at 43% 50%,#fff 0 22%,transparent 23%),radial-gradient(circle at 48% 50%,#e30a17 0 18%,transparent 19%),#e30a17',
            'us' => 'repeating-linear-gradient(#b22234 0 7.7%,#fff 7.7% 15.4%)',
            'uy' => 'repeating-linear-gradient(#fff 0 11%,#0038a8 11% 22%)',
            'uz' => 'linear-gradient(#1eb5e5 0 32%,#ce1126 32% 36%,#fff 36% 64%,#ce1126 64% 68%,#009739 68%)',
            've' => 'linear-gradient(#ffcc00 0 33%,#00247d 33% 66%,#cf142b 66%)',
            'za' => 'linear-gradient(90deg,#007a4d 0 55%,#de3831 55% 72%,#002395 72%)',
        ];

        $background = $styles[$code] ?? 'linear-gradient(135deg,#64748b,#94a3b8)';

        return "<span title='" . esc(strtoupper($code)) . "' style='width:28px;height:19px;display:inline-block;flex-shrink:0;border-radius:3px;background:{$background};box-shadow:0 0 0 1px rgba(255,255,255,0.28);'></span>";
    }

    /**
     * Full page load — Dashboard Overview with cached stats
     */
    public function index()
    {
        $data = $this->getDashboardStats();
        return view('dashboard/index', array_merge($data, [
            'title' => 'Codex SS - Dashboard',
            'layout' => 'layouts/main',
            'activePage' => 'overview',
        ]));
    }

    public function overview()
    {
        $data = $this->getDashboardStats();
        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/index', array_merge($data, ['layout' => 'layouts/htmx', 'activePage' => 'overview']));
        }
        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'overview']));
    }

    private function getDashboardStats(): array
    {
        $db = \Config\Database::connect();

        // 1. Total Revenue (sum of all deposits)
        $totalDepositsRow = $db->table('transactions')
                               ->where('type', 'deposit')
                               ->selectSum('amount')
                               ->get()
                               ->getRow();
        $totalRevenue = $totalDepositsRow && $totalDepositsRow->amount !== null ? (float)$totalDepositsRow->amount : 0.00;

        // 2. Active Users (is_active = 1, deleted_at is null)
        $activeUsers = $db->table('users')
                          ->where('deleted_at', null)
                          ->where('is_active', 1)
                          ->countAllResults();

        // 3. Active Events (live, pending)
        $activeEvents = $db->table('events')
                           ->whereIn('status', ['live', 'pending'])
                           ->countAllResults();

        // 4. Uptime (static / cached)
        $systemUptime = 99.98;

        // 5. Database Status
        $dbStatus = 'Operativo';
        $dbColor = 'var(--accent-emerald)';
        try {
            $db->query('SELECT 1');
        } catch (\Throwable $t) {
            $dbStatus = 'Error';
            $dbColor = 'var(--accent-rose)';
        }

        // 6. Redis Status
        $redisStatus = $this->cache->isRedisAvailable() ? 'Operativo' : 'Desconectado';
        $redisColor = $this->cache->isRedisAvailable() ? 'var(--accent-emerald)' : 'var(--accent-amber)';

        // 7. Queue Workers Status
        $queueStatus = 'Operativo';
        $queueColor = 'var(--accent-emerald)';

        // 8. Unified Recent Activity
        $users = $db->table('users')
                    ->select('username, created_at')
                    ->orderBy('created_at', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();

        $txs = $db->table('transactions t')
                  ->join('wallets w', 'w.id = t.wallet_id')
                  ->join('users u', 'u.id = w.user_id')
                  ->select('u.username, t.amount, t.type, t.created_at')
                  ->orderBy('t.created_at', 'DESC')
                  ->limit(5)
                  ->get()
                  ->getResultArray();

        $audits = $db->table('audit_logs a')
                     ->select('a.action, a.status, a.created_at')
                     ->orderBy('a.created_at', 'DESC')
                     ->limit(5)
                     ->get()
                     ->getResultArray();

        $events = $db->table('events')
                    ->select('home_team, away_team, created_at')
                    ->orderBy('id', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();

        $activities = [];

        foreach ($users as $u) {
            $activities[] = [
                'icon' => 'user-plus',
                'color' => 'var(--primary)',
                'bg' => 'rgba(99,102,241,0.1)',
                'title' => 'Nuevo registro: ' . esc($u['username']),
                'time' => $this->timeAgo($u['created_at']),
                'timestamp' => strtotime($u['created_at'])
            ];
        }

        foreach ($txs as $t) {
            if ($t['type'] === 'deposit') {
                $activities[] = [
                    'icon' => 'wallet',
                    'color' => 'var(--accent-emerald)',
                    'bg' => 'rgba(52,211,153,0.1)',
                    'title' => 'Depósito: ' . number_format($t['amount'], 2, ',', '.') . ' K — usuario ' . esc($t['username']),
                    'time' => $this->timeAgo($t['created_at']),
                    'timestamp' => strtotime($t['created_at'])
                ];
            } elseif ($t['type'] === 'withdrawal') {
                $activities[] = [
                    'icon' => 'arrow-down-right',
                    'color' => 'var(--accent-rose)',
                    'bg' => 'rgba(251,113,133,0.1)',
                    'title' => 'Retiro: ' . number_format(abs($t['amount']), 2, ',', '.') . ' K — usuario ' . esc($t['username']),
                    'time' => $this->timeAgo($t['created_at']),
                    'timestamp' => strtotime($t['created_at'])
                ];
            } elseif ($t['type'] === 'bet_placed') {
                $activities[] = [
                    'icon' => 'ticket',
                    'color' => 'var(--accent-amber)',
                    'bg' => 'rgba(251,191,36,0.1)',
                    'title' => 'Apuesta colocada: ' . number_format(abs($t['amount']), 2, ',', '.') . ' K — usuario ' . esc($t['username']),
                    'time' => $this->timeAgo($t['created_at']),
                    'timestamp' => strtotime($t['created_at'])
                ];
            }
        }

        foreach ($audits as $a) {
            if ($a['action'] === 'login_failed') {
                $activities[] = [
                    'icon' => 'alert-triangle',
                    'color' => 'var(--accent-rose)',
                    'bg' => 'rgba(251,113,133,0.1)',
                    'title' => 'Intento de login sospechoso bloqueado',
                    'time' => $this->timeAgo($a['created_at']),
                    'timestamp' => strtotime($a['created_at'])
                ];
            }
        }

        foreach ($events as $e) {
            $evtTime = $e['created_at'] ?? date('Y-m-d H:i:s');
            $activities[] = [
                'icon' => 'trophy',
                'color' => 'var(--accent-amber)',
                'bg' => 'rgba(251,191,36,0.1)',
                'title' => 'Evento creado: ' . esc($e['home_team']) . ' vs ' . esc($e['away_team']),
                'time' => $this->timeAgo($evtTime),
                'timestamp' => strtotime($evtTime)
            ];
        }

        // Sort by timestamp DESC
        usort($activities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        // Limit to 5
        $activities = array_slice($activities, 0, 5);

        return [
            'totalRevenue' => $totalRevenue,
            'activeUsers' => $activeUsers,
            'activeEvents' => $activeEvents,
            'systemUptime' => $systemUptime,
            'dbStatus' => $dbStatus,
            'dbColor' => $dbColor,
            'redisStatus' => $redisStatus,
            'redisColor' => $redisColor,
            'queueStatus' => $queueStatus,
            'queueColor' => $queueColor,
            'activities' => $activities
        ];
    }

    private function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return 'algún tiempo';
        }
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'menos de un minuto';
        }
        $minutes = round($diff / 60);
        if ($minutes < 60) {
            return 'hace ' . $minutes . ' min' . ($minutes > 1 ? 's' : '');
        }
        $hours = round($diff / 3600);
        if ($hours < 24) {
            return 'hace ' . $hours . ' hora' . ($hours > 1 ? 's' : '');
        }
        $days = round($diff / 86400);
        if ($days < 30) {
            return 'hace ' . $days . ' día' . ($days > 1 ? 's' : '');
        }
        return date('d/m/Y', $timestamp);
    }

    /**
     * Analytics — with cached query results
     */
    public function analytics()
    {
        $data = $this->getAnalyticsStats();
        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/analytics', $data);
        }
        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'analytics']));
    }

    private function getAnalyticsStats(): array
    {
        $db = \Config\Database::connect();
        $todayStart = date('Y-m-d') . ' 00:00:00';

        // 1. Apuestas Hoy (Bet Count Today)
        $betsToday = (int) $db->table('bet_slips')
            ->where('created_at >=', $todayStart)
            ->countAllResults();

        // 2. Volumen Hoy (Total Stake Today)
        $volumeTodayRow = $db->table('bet_slips')
            ->where('created_at >=', $todayStart)
            ->selectSum('stake')
            ->get()
            ->getRow();
        $volumeToday = $volumeTodayRow && $volumeTodayRow->stake !== null ? (float)$volumeTodayRow->stake : 0.00;

        // Daily targets
        $betsTarget = 100;
        $volumeTarget = 5000.00;

        $betsPercentage = $betsTarget > 0 ? min(100, round(($betsToday / $betsTarget) * 100)) : 0;
        $volumePercentage = $volumeTarget > 0 ? min(100, round(($volumeToday / $volumeTarget) * 100)) : 0;

        // 3. Margen Promedio (Hold Margin)
        // Hold Margin = (Total Settled Stake - Total Settled Payout) / Total Settled Stake * 100
        $settledRow = $db->table('bet_slips')
            ->whereIn('status', ['won', 'lost'])
            ->select("
                COALESCE(SUM(stake), 0) as total_stake,
                COALESCE(SUM(CASE WHEN status = 'won' THEN potential_payout ELSE 0 END), 0) as total_payout
            ")
            ->get()
            ->getRow();

        $totalSettledStake = $settledRow ? (float)$settledRow->total_stake : 0.00;
        $totalSettledPayout = $settledRow ? (float)$settledRow->total_payout : 0.00;

        if ($totalSettledStake > 0) {
            $holdMargin = (($totalSettledStake - $totalSettledPayout) / $totalSettledStake) * 100;
        } else {
            $holdMargin = 0.0;
        }

        // 4. Top Eventos por Volumen
        $topEvents = $db->table('bet_selections bs')
            ->join('bet_slips b', 'b.id = bs.bet_slip_id')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->join('leagues l', 'l.id = e.league_id')
            ->select("
                e.id as event_id,
                e.home_team,
                e.away_team,
                l.name as league_name,
                e.status as event_status,
                COUNT(DISTINCT b.id) as ticket_count,
                COALESCE(SUM(b.stake), 0) as stake_sum
            ")
            ->groupBy('e.id, e.home_team, e.away_team, l.name, e.status')
            ->orderBy('stake_sum', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // Fallback: If less than 5 events have active bets, pad with other events
        if (count($topEvents) < 5) {
            $needed = 5 - count($topEvents);
            $excludeIds = array_column($topEvents, 'event_id');

            $extraEventsQuery = $db->table('events e')
                ->join('leagues l', 'l.id = e.league_id')
                ->select("
                    e.id as event_id,
                    e.home_team,
                    e.away_team,
                    l.name as league_name,
                    e.status as event_status,
                    0 as ticket_count,
                    0.00 as stake_sum
                ");

            if (!empty($excludeIds)) {
                $extraEventsQuery->whereNotIn('e.id', $excludeIds);
            }

            $extraEvents = $extraEventsQuery
                ->orderBy('e.id', 'DESC')
                ->limit($needed)
                ->get()
                ->getResultArray();

            $topEvents = array_merge($topEvents, $extraEvents);
        }

        // Format events for display
        $formattedEvents = [];
        foreach ($topEvents as $e) {
            $eventName = $e['league_name'] . ' — ' . $e['home_team'] . ' vs ' . $e['away_team'];
            
            // Map status
            $statusText = 'Desconocido';
            $statusColor = 'var(--text-muted)';
            
            switch ($e['event_status']) {
                case 'live':
                    $statusText = 'Activo';
                    $statusColor = 'var(--accent-emerald)';
                    break;
                case 'pending':
                    $statusText = 'Próximo';
                    $statusColor = 'var(--accent-amber)';
                    break;
                case 'finished':
                    $statusText = 'Finalizado';
                    $statusColor = 'var(--text-muted)';
                    break;
                case 'cancelled':
                    $statusText = 'Cancelado';
                    $statusColor = 'var(--accent-rose)';
                    break;
            }

            $formattedEvents[] = [
                'name' => $eventName,
                'tickets' => number_format($e['ticket_count']),
                'volume' => number_format($e['stake_sum'], 2, ',', '.') . ' K',
                'status_text' => $statusText,
                'status_color' => $statusColor
            ];
        }

        $riskOverviewRow = $db->table('bet_slips')
            ->select('COUNT(*) as pending_tickets, COALESCE(SUM(stake), 0) as pending_stake, COALESCE(SUM(potential_payout), 0) as pending_payout', false)
            ->where('status', 'pending')
            ->get()
            ->getRowArray();

        $pendingStake = (float) ($riskOverviewRow['pending_stake'] ?? 0);
        $pendingPayout = (float) ($riskOverviewRow['pending_payout'] ?? 0);
        $riskMultiple = $pendingStake > 0 ? $pendingPayout / $pendingStake : 0.0;

        $riskEvents = $db->table('bet_selections bs')
            ->select('e.id as event_id, e.home_team, e.away_team, l.name as league_name, COUNT(DISTINCT slip.id) as tickets, COALESCE(SUM(slip.potential_payout), 0) as exposure', false)
            ->join('bet_slips slip', 'slip.id = bs.bet_slip_id')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->join('leagues l', 'l.id = e.league_id')
            ->where('slip.status', 'pending')
            ->groupBy('e.id, e.home_team, e.away_team, l.name')
            ->orderBy('exposure', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        $riskMarkets = $db->table('bet_selections bs')
            ->select('m.id as market_id, m.name as market_name, e.home_team, e.away_team, COUNT(DISTINCT slip.id) as tickets, COALESCE(SUM(slip.potential_payout), 0) as exposure', false)
            ->join('bet_slips slip', 'slip.id = bs.bet_slip_id')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->where('slip.status', 'pending')
            ->groupBy('m.id, m.name, e.home_team, e.away_team')
            ->orderBy('exposure', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        $riskUsers = $db->table('bet_slips slip')
            ->select('u.username, u.email, COUNT(slip.id) as tickets, COALESCE(SUM(slip.stake), 0) as stake, COALESCE(SUM(slip.potential_payout), 0) as exposure', false)
            ->join('users u', 'u.id = slip.user_id')
            ->where('slip.status', 'pending')
            ->groupBy('u.id, u.username, u.email')
            ->orderBy('exposure', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        return [
            'betsToday' => $betsToday,
            'betsPercentage' => $betsPercentage,
            'volumeToday' => $volumeToday,
            'volumePercentage' => $volumePercentage,
            'holdMargin' => $holdMargin,
            'formattedEvents' => $formattedEvents,
            'riskOverview' => [
                'pending_tickets' => (int) ($riskOverviewRow['pending_tickets'] ?? 0),
                'pending_stake' => $pendingStake,
                'pending_payout' => $pendingPayout,
                'risk_multiple' => $riskMultiple,
            ],
            'riskEvents' => $riskEvents,
            'riskMarkets' => $riskMarkets,
            'riskUsers' => $riskUsers,
        ];
    }


    public function users()
    {
        $db = \Config\Database::connect();
        $users = $db->table('users u')
            ->select("
                u.id,
                u.username,
                u.email,
                u.role_id,
                u.is_active,
                u.created_at,
                u.last_login_at,
                u.locked_until,
                u.failed_login_attempts,
                r.name as role_name,
                COALESCE(w.balance, 0) as balance,
                COALESCE((
                    SELECT COUNT(bs.id)
                    FROM bet_slips bs
                    WHERE bs.user_id = u.id
                ), 0) as total_tickets,
                COALESCE((
                    SELECT COUNT(bs.id)
                    FROM bet_slips bs
                    WHERE bs.user_id = u.id AND bs.status = 'pending'
                ), 0) as pending_tickets,
                COALESCE((
                    SELECT SUM(bs.stake)
                    FROM bet_slips bs
                    WHERE bs.user_id = u.id
                ), 0) as total_stake,
                COALESCE((
                    SELECT SUM(bs.potential_payout)
                    FROM bet_slips bs
                    WHERE bs.user_id = u.id AND bs.status = 'pending'
                ), 0) as pending_exposure
            ", false)
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('wallets w', 'w.user_id = u.id', 'left')
            ->where('u.deleted_at', null)
            ->orderBy('u.role_id', 'ASC')
            ->orderBy('u.username', 'ASC')
            ->get()
            ->getResultArray();

        $internalUsers = array_values(array_filter($users, static fn ($user) => (int) $user['role_id'] === 1));
        $externalUsers = array_values(array_filter($users, static fn ($user) => (int) $user['role_id'] !== 1));

        $data = [
            'internalUsers' => $internalUsers,
            'externalUsers' => $externalUsers,
            'totalUsers' => count($users),
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/users', $data);
        }
        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'users']));
    }

    public function toggleUserActive(int $userId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        if ($userId === (int) session()->get('user_id')) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'No puede suspender su propia cuenta.']);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Usuario no encontrado.']);
        }

        $newStatus = empty($user['is_active']) ? 1 : 0;
        $userModel->update($userId, ['is_active' => $newStatus]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'user_active_changed',
            'user',
            $userId,
            ['is_active' => (int) $user['is_active']],
            ['is_active' => $newStatus]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => $newStatus ? 'Usuario reactivado.' : 'Usuario suspendido.',
        ]);
    }

    public function lockUser(int $userId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        if ($userId === (int) session()->get('user_id')) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'No puede bloquear su propia cuenta.']);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Usuario no encontrado.']);
        }

        $hours = max(1, min(720, (int) ($this->request->getPost('hours') ?? 24)));
        $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . $hours . ' hours'));

        $userModel->update($userId, [
            'locked_until' => $lockedUntil,
            'failed_login_attempts' => 0,
        ]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'user_locked',
            'user',
            $userId,
            ['locked_until' => $user['locked_until'] ?? null],
            ['locked_until' => $lockedUntil, 'hours' => $hours]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Usuario bloqueado hasta ' . date('d/m/Y H:i', strtotime($lockedUntil)) . '.',
        ]);
    }

    public function unlockUser(int $userId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Usuario no encontrado.']);
        }

        $userModel->update($userId, [
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'user_unlocked',
            'user',
            $userId,
            ['locked_until' => $user['locked_until'] ?? null, 'failed_login_attempts' => (int) ($user['failed_login_attempts'] ?? 0)],
            ['locked_until' => null, 'failed_login_attempts' => 0]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Usuario desbloqueado.',
        ]);
    }

    public function events()
    {
        $db = \Config\Database::connect();
        // Fetch leagues with event count
        $leagues = $db->table('leagues l')
                      ->select('l.*, s.icon as sport_icon, s.name as sport_name, (SELECT COUNT(id) FROM events WHERE league_id = l.id) as event_count')
                      ->join('sports s', 's.id = l.sport_id')
                      ->where('l.active', 1)
                      ->orderBy('event_count', 'DESC')
                      ->get()->getResultArray();

        $data = ['leagues' => $leagues];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/events', $data);
        }
        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'events']));
    }

    public function bets()
    {
        $db = \Config\Database::connect();
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        $status = (string) ($this->request->getGet('status') ?? 'all');
        $search = trim((string) ($this->request->getGet('q') ?? ''));

        $allowedStatuses = ['all', 'pending', 'won', 'lost', 'void', 'cashed_out'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $summary = $db->table('bet_slips')
            ->select("
                COUNT(*) as total_tickets,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_tickets,
                COALESCE(SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END), 0) as won_tickets,
                COALESCE(SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END), 0) as lost_tickets,
                COALESCE(SUM(stake), 0) as total_stake,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN potential_payout ELSE 0 END), 0) as pending_exposure,
                COALESCE(SUM(CASE WHEN status = 'won' THEN potential_payout ELSE 0 END), 0) as paid_payout
            ", false)
            ->get()
            ->getRowArray() ?? [];

        $base = $db->table('bet_slips b')
            ->select('b.*, u.username, u.email, COUNT(bs.id) as selection_count', false)
            ->join('users u', 'u.id = b.user_id', 'left')
            ->join('bet_selections bs', 'bs.bet_slip_id = b.id', 'left')
            ->groupBy('b.id, b.user_id, b.total_odds, b.stake, b.potential_payout, b.status, b.created_at, b.updated_at, u.username, u.email');

        if ($status !== 'all') {
            $base->where('b.status', $status);
        }

        if ($search !== '') {
            $ticketId = (int) preg_replace('/\D+/', '', $search);
            $escapedSearch = $db->escape('%' . $search . '%');
            $base->groupStart()
                ->like('u.username', $search)
                ->orLike('u.email', $search);

            if ($ticketId > 0) {
                $base->orWhere('b.id', $ticketId);
            }

            $base->orWhere("EXISTS (
                SELECT 1
                FROM bet_selections sx
                JOIN odds ox ON ox.id = sx.odd_id
                JOIN markets mx ON mx.id = ox.market_id
                JOIN events ex ON ex.id = mx.event_id
                JOIN leagues lx ON lx.id = ex.league_id
                WHERE sx.bet_slip_id = b.id
                AND (
                    ex.home_team LIKE {$escapedSearch}
                    OR ex.away_team LIKE {$escapedSearch}
                    OR lx.name LIKE {$escapedSearch}
                    OR mx.name LIKE {$escapedSearch}
                    OR ox.selection LIKE {$escapedSearch}
                )
            )", null, false)
                ->groupEnd();
        }

        $totalTickets = (clone $base)->countAllResults(false);
        $tickets = (clone $base)
            ->orderBy('b.created_at', 'DESC')
            ->orderBy('b.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        foreach ($tickets as &$ticket) {
            $ticket['selections'] = $db->table('bet_selections bs')
                ->select('bs.status as selection_status, bs.odd_at_bet_time, o.selection as odd_name, m.name as market_name, e.id as event_id, e.home_team, e.away_team, e.start_time, e.venue, l.name as league_name')
                ->join('odds o', 'o.id = bs.odd_id')
                ->join('markets m', 'm.id = o.market_id')
                ->join('events e', 'e.id = m.event_id')
                ->join('leagues l', 'l.id = e.league_id')
                ->where('bs.bet_slip_id', (int) $ticket['id'])
                ->orderBy('bs.id', 'ASC')
                ->get()
                ->getResultArray();
        }
        unset($ticket);

        $data = [
            'tickets' => $tickets,
            'summary' => $summary,
            'status' => $status,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($totalTickets / $perPage)),
            'totalTickets' => $totalTickets,
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/bets', $data);
        }

        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'bets']));
    }

    public function exportBets()
    {
        $db = \Config\Database::connect();
        $status = (string) ($this->request->getGet('status') ?? 'all');
        $search = trim((string) ($this->request->getGet('q') ?? ''));

        $allowedStatuses = ['all', 'pending', 'won', 'lost', 'void', 'cashed_out'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $base = $db->table('bet_slips b')
            ->select('b.*, u.username, u.email, COUNT(bs.id) as selection_count', false)
            ->join('users u', 'u.id = b.user_id', 'left')
            ->join('bet_selections bs', 'bs.bet_slip_id = b.id', 'left')
            ->groupBy('b.id, b.user_id, b.total_odds, b.stake, b.potential_payout, b.status, b.created_at, b.updated_at, u.username, u.email');

        if ($status !== 'all') {
            $base->where('b.status', $status);
        }

        if ($search !== '') {
            $ticketId = (int) preg_replace('/\D+/', '', $search);
            $escapedSearch = $db->escape('%' . $search . '%');
            $base->groupStart()
                ->like('u.username', $search)
                ->orLike('u.email', $search);

            if ($ticketId > 0) {
                $base->orWhere('b.id', $ticketId);
            }

            $base->orWhere("EXISTS (
                SELECT 1
                FROM bet_selections sx
                JOIN odds ox ON ox.id = sx.odd_id
                JOIN markets mx ON mx.id = ox.market_id
                JOIN events ex ON ex.id = mx.event_id
                JOIN leagues lx ON lx.id = ex.league_id
                WHERE sx.bet_slip_id = b.id
                AND (
                    ex.home_team LIKE {$escapedSearch}
                    OR ex.away_team LIKE {$escapedSearch}
                    OR lx.name LIKE {$escapedSearch}
                    OR mx.name LIKE {$escapedSearch}
                    OR ox.selection LIKE {$escapedSearch}
                )
            )", null, false)
                ->groupEnd();
        }

        $tickets = $base
            ->orderBy('b.created_at', 'DESC')
            ->orderBy('b.id', 'DESC')
            ->get()
            ->getResultArray();

        $rows = [];
        foreach ($tickets as $ticket) {
            $selections = $db->table('bet_selections bs')
                ->select('bs.status as selection_status, bs.odd_at_bet_time, o.selection as odd_name, m.name as market_name, e.home_team, e.away_team, l.name as league_name')
                ->join('odds o', 'o.id = bs.odd_id')
                ->join('markets m', 'm.id = o.market_id')
                ->join('events e', 'e.id = m.event_id')
                ->join('leagues l', 'l.id = e.league_id')
                ->where('bs.bet_slip_id', (int) $ticket['id'])
                ->orderBy('bs.id', 'ASC')
                ->get()
                ->getResultArray();

            $selectionText = [];
            foreach ($selections as $selection) {
                $selectionText[] = sprintf(
                    '%s: %s vs %s / %s / %s @ %s / %s',
                    $selection['league_name'] ?? '',
                    $selection['home_team'] ?? '',
                    $selection['away_team'] ?? '',
                    $selection['market_name'] ?? '',
                    $selection['odd_name'] ?? '',
                    number_format((float) ($selection['odd_at_bet_time'] ?? 0), 2, '.', ''),
                    $selection['selection_status'] ?? ''
                );
            }

            $rows[] = [
                $ticket['id'],
                $ticket['created_at'],
                $ticket['username'] ?? '',
                $ticket['email'] ?? '',
                $ticket['status'],
                (int) ($ticket['selection_count'] ?? 0) > 1 ? 'Combinada' : 'Simple',
                number_format((float) $ticket['stake'], 2, '.', ''),
                number_format((float) $ticket['total_odds'], 3, '.', ''),
                number_format((float) $ticket['potential_payout'], 2, '.', ''),
                implode(' | ', $selectionText),
            ];
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            'admin_bets_exported',
            'bet_slip',
            null,
            null,
            ['status' => $status, 'search' => $search, 'rows' => count($rows)]
        );

        return $this->csvResponse(
            'apuestas-' . date('Ymd-His') . '.csv',
            ['Ticket', 'Fecha', 'Usuario', 'Email', 'Estado', 'Tipo', 'Importe', 'Cuota total', 'Pago potencial', 'Selecciones'],
            $rows
        );
    }

    public function betTicket(int $slipId)
    {
        $ticket = $this->adminTicketData($slipId);
        if (! $ticket) {
            return redirect()->to('/dashboard/bets')->with('error', 'Ticket no encontrado.');
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            'admin_ticket_print_viewed',
            'bet_slip',
            $slipId
        );

        return view('sportsbook/ticket', [
            'title' => 'Ticket #' . str_pad((string) $ticket['slip']['id'], 6, '0', STR_PAD_LEFT),
            'slip' => $ticket['slip'],
            'selections' => $ticket['selections'],
            'username' => $ticket['username'],
            'pdfMode' => false,
            'adminMode' => true,
        ]);
    }

    public function betTicketPdf(int $slipId)
    {
        $ticket = $this->adminTicketData($slipId);
        if (! $ticket) {
            return redirect()->to('/dashboard/bets')->with('error', 'Ticket no encontrado.');
        }

        $html = view('sportsbook/ticket', [
            'title' => 'Ticket #' . str_pad((string) $ticket['slip']['id'], 6, '0', STR_PAD_LEFT),
            'slip' => $ticket['slip'],
            'selections' => $ticket['selections'],
            'username' => $ticket['username'],
            'pdfMode' => true,
            'adminMode' => true,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Courier');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper([0, 0, 226.77, 700], 'portrait');
        $dompdf->render();

        AuditLogger::log(
            (int) session()->get('user_id'),
            'admin_ticket_pdf_downloaded',
            'bet_slip',
            $slipId
        );

        $filename = 'ticket-admin-' . str_pad((string) $ticket['slip']['id'], 6, '0', STR_PAD_LEFT) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    public function voidBet(int $slipId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $reason = trim((string) ($this->request->getPost('reason') ?? 'Anulacion administrativa'));
        if ($reason === '') {
            $reason = 'Anulacion administrativa';
        }

        $db = \Config\Database::connect();
        $walletModel = new WalletModel();
        $transactionModel = new TransactionModel();

        $slip = $db->table('bet_slips')
            ->where('id', $slipId)
            ->get()
            ->getRowArray();

        if (! $slip) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Ticket no encontrado.']);
        }

        if (($slip['status'] ?? '') !== 'pending') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Solo se pueden anular tickets pendientes.']);
        }

        $wallet = $walletModel->where('user_id', (int) $slip['user_id'])->first();
        if (! $wallet) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'El usuario no tiene wallet asociada.']);
        }

        $refund = round((float) $slip['stake'], 2);
        $currentBalance = (float) $wallet['balance'];
        $newBalance = round($currentBalance + $refund, 2);

        $db->transStart();

        $db->table('bet_selections')
            ->where('bet_slip_id', $slipId)
            ->where('status', 'pending')
            ->update(['status' => 'void', 'updated_at' => date('Y-m-d H:i:s')]);

        $db->table('bet_slips')
            ->where('id', $slipId)
            ->update([
                'status' => 'void',
                'total_odds' => 1.000,
                'potential_payout' => $refund,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $walletModel->update((int) $wallet['id'], ['balance' => $newBalance]);

        $transactionModel->insert([
            'wallet_id' => (int) $wallet['id'],
            'type' => 'bet_refunded',
            'amount' => $refund,
            'balance_after' => $newBalance,
            'reference_id' => $slipId,
            'description' => 'Reintegro por anulacion administrativa Ticket #' . $slipId . ' - ' . mb_substr($reason, 0, 140),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'No se pudo anular el ticket.']);
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            'admin_bet_voided',
            'bet_slip',
            $slipId,
            ['status' => 'pending', 'wallet_balance' => $currentBalance],
            ['status' => 'void', 'refund' => $refund, 'wallet_balance' => $newBalance, 'reason' => $reason]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Ticket anulado y reintegro aplicado.',
            'refund' => $refund,
            'new_balance' => $newBalance,
        ]);
    }

    private function adminTicketData(int $slipId): ?array
    {
        $db = \Config\Database::connect();

        $slip = $db->table('bet_slips b')
            ->select('b.*, u.username, u.email')
            ->join('users u', 'u.id = b.user_id', 'left')
            ->where('b.id', $slipId)
            ->get()
            ->getRowArray();

        if (! $slip) {
            return null;
        }

        $selections = $db->table('bet_selections bs')
            ->select('bs.*, o.selection as odd_name, m.name as market_name, e.home_team, e.away_team, e.start_time, e.venue, l.name as league_name')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->join('leagues l', 'l.id = e.league_id')
            ->where('bs.bet_slip_id', $slipId)
            ->orderBy('bs.id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'slip' => $slip,
            'selections' => $selections,
            'username' => (string) ($slip['username'] ?? 'Usuario'),
        ];
    }

    public function leagueEvents($leagueId)
    {
        if (session()->get('role_id') != 1)
            return '';

        $db = \Config\Database::connect();

        // Actualizar estados de eventos dinámicamente y liquidar apuestas si corresponde
        $this->updateEventStatuses();

        $league = $db->table('leagues')->where('id', $leagueId)->get()->getRowArray();
        $champion = null;
        if (($league['name'] ?? '') === 'Copa Mundial de la FIFA 2026') {
            $champion = (new WorldCupBracketService())->champion();
        }

        $eventModel = new \App\Models\EventModel();
        $events = $eventModel->where('league_id', $leagueId)
            ->orderBy('match_number', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();

        $html = '';
        if (($league['name'] ?? '') === 'Copa Mundial de la FIFA 2026') {
            $states = (new WorldCupBracketService())->bracketActionStates();
            $button = function (string $stage, string $label, bool $accent = false) use ($states): string {
                $state = $states[$stage] ?? ['enabled' => false, 'completed' => false, 'reason' => 'Etapa no disponible.'];
                $enabled = (bool) $state['enabled'];
                $completed = (bool) $state['completed'];
                $reason = esc($state['reason']);
                $disabled = $enabled ? '' : 'disabled';
                $opacity = $enabled ? '1' : '0.48';
                $cursor = $enabled ? 'pointer' : 'not-allowed';
                $text = $completed ? $label . ' ✓' : $label;
                $background = $accent
                    ? 'linear-gradient(135deg,#f59e0b,#f97316)'
                    : 'var(--primary)';
                $color = $accent ? '#111827' : '#fff';

                return "<button {$disabled} title='{$reason}' onclick=\"runWorldCupBracket('{$stage}', this)\" style='cursor:{$cursor};opacity:{$opacity};background:{$background};color:{$color};border:none;border-radius:6px;padding:0.5rem 0.7rem;font-weight:900;font-size:0.76rem;'>{$text}</button>";
            };

            $html .= "
            <div style='display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem; padding:0.85rem; border:1px solid var(--border); border-radius:10px; background:rgba(255,255,255,0.03);'>
                {$button('16avos', '16avos')}
                {$button('8vos', '8vos')}
                {$button('4tos', 'Cuartos')}
                {$button('semis', 'Semifinales')}
                {$button('final', 'Final', true)}
                <div style='flex-basis:100%;color:var(--text-muted);font-size:0.76rem;margin-top:0.2rem;'>Cada accion valida que la fase anterior este finalizada y tenga marcadores cargados.</div>
            </div>";
        }

        if ($champion) {
            $championFlag = $this->flagMarkup($champion['flag'] ?? null);
            $championName = esc($champion['team']);
            $html .= "
            <div style='position:relative; overflow:hidden; margin-bottom:1rem; border:1px solid rgba(251,191,36,0.42); border-radius:10px; padding:1.1rem 1.25rem; background:linear-gradient(135deg, rgba(251,191,36,0.20), rgba(15,23,42,0.96) 58%, rgba(34,197,94,0.14));'>
                <div style='position:absolute; right:1rem; top:0.4rem; font-size:4.5rem; line-height:1; color:rgba(251,191,36,0.22);'>🏆</div>
                <div style='font-size:0.72rem; text-transform:uppercase; letter-spacing:0.12em; color:#fbbf24; font-weight:900;'>Felicitaciones Campeón</div>
                <div style='display:flex; align-items:center; gap:0.7rem; margin-top:0.45rem; font-family:Outfit, sans-serif; font-size:1.45rem; font-weight:900;'>
                    {$championFlag}
                    <span>{$championName}</span>
                </div>
                <div style='margin-top:0.25rem; color:var(--text-secondary); font-size:0.86rem;'>Campeón de la Copa Mundial de Fútbol 2026</div>
            </div>";
        }

        foreach ($events as $e) {
            $isActive = ($e['status'] === 'pending' || $e['status'] === 'live');
            $statusColor = $isActive ? 'var(--success)' : 'var(--danger)';
            $statusText = $isActive ? 'Activo' : 'Inactivo';
            $date = date('d M Y H:i', strtotime($e['start_time']));
            $editStartTime = date('Y-m-d\TH:i', strtotime($e['start_time']));
            $matchNumber = !empty($e['match_number']) ? '#' . esc((string) $e['match_number']) . ' · ' : '';
            $stage = esc($e['stage'] ?? 'Partido');
            $group = !empty($e['group_name']) ? ' · ' . esc($e['group_name']) : '';
            $venue = !empty($e['venue']) ? esc($e['venue']) : 'Estadio por confirmar';
            $editStage = esc((string) ($e['stage'] ?? ''), 'attr');
            $editGroup = esc((string) ($e['group_name'] ?? ''), 'attr');
            $editVenue = esc((string) ($e['venue'] ?? ''), 'attr');
            $editHomeTeam = esc((string) ($e['home_team'] ?? ''), 'attr');
            $editAwayTeam = esc((string) ($e['away_team'] ?? ''), 'attr');
            $editHomeFlag = esc((string) ($e['home_flag'] ?? ''), 'attr');
            $editAwayFlag = esc((string) ($e['away_flag'] ?? ''), 'attr');
            $editMatchNumber = esc((string) ($e['match_number'] ?? ''), 'attr');
            $homeFlag = $this->flagMarkup($e['home_flag'] ?? null);
            $awayFlag = $this->flagMarkup($e['away_flag'] ?? null);
            $homeTeam = esc($e['home_team']);
            $awayTeam = esc($e['away_team']);
            $eventId = (int) $e['id'];
            $eventStatus = esc($e['status']);
            $scoreHome = $e['score_home'] !== null ? (int) $e['score_home'] : '';
            $scoreAway = $e['score_away'] !== null ? (int) $e['score_away'] : '';
            $marketCount = (int) $db->table('markets')->where('event_id', $eventId)->countAllResults();
            $openMarketCount = (int) $db->table('markets')->where('event_id', $eventId)->where('status', 'open')->countAllResults();
            $suspendedMarketCount = max(0, $marketCount - $openMarketCount);
            $pendingExposure = (float) ($db->table('bet_slips bs')
                ->select('COALESCE(SUM(bs.potential_payout), 0) as total', false)
                ->join('bet_selections sel', 'sel.bet_slip_id = bs.id')
                ->join('odds o', 'o.id = sel.odd_id')
                ->join('markets m', 'm.id = o.market_id')
                ->where('m.event_id', $eventId)
                ->where('bs.status', 'pending')
                ->get()
                ->getRowArray()['total'] ?? 0);
            $marketRows = $db->table('markets')
                ->where('event_id', $eventId)
                ->orderBy('id', 'ASC')
                ->get(6)
                ->getResultArray();
            $marketControls = '';
            foreach ($marketRows as $marketRow) {
                $marketId = (int) $marketRow['id'];
                $marketName = esc($marketRow['name']);
                $marketStatus = esc($marketRow['status']);
                $marketBtnText = $marketRow['status'] === 'open' ? 'Suspender' : 'Reabrir';
                $marketBtnBg = $marketRow['status'] === 'open' ? 'rgba(239,68,68,0.16)' : 'rgba(34,197,94,0.16)';
                $marketBtnColor = $marketRow['status'] === 'open' ? '#fca5a5' : '#86efac';
                $odds = $db->table('odds')
                    ->where('market_id', $marketId)
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();
                $oddButtons = '';
                foreach ($odds as $odd) {
                    $oddId = (int) $odd['id'];
                    $oddActive = (int) $odd['active'] === 1 && $odd['status'] === 'pending';
                    $oddLabel = esc($odd['selection']);
                    $oddValue = number_format((float) $odd['odds_decimal'], 2, '.', '');
                    $oddOpacity = $oddActive ? '1' : '0.45';
                    $oddBorder = $oddActive ? 'var(--border)' : 'rgba(239,68,68,0.35)';
                    $oddButtons .= "
                        <div id='odd-control-{$oddId}' style='opacity:{$oddOpacity};border:1px solid {$oddBorder};background:rgba(255,255,255,0.035);border-radius:6px;padding:0.28rem;display:flex;align-items:center;gap:0.28rem;max-width:210px;'>
                            <button onclick='toggleOdd({$oddId}, this)' title='Alternar cuota' style='border:none;background:transparent;color:var(--text-primary);font-size:0.68rem;font-weight:850;cursor:pointer;max-width:78px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;'>{$oddLabel}</button>
                            <input id='odd-input-{$oddId}' type='number' step='0.01' min='1.01' value='{$oddValue}' style='width:58px;background:var(--bg-primary);border:1px solid var(--border);border-radius:5px;color:var(--text-primary);padding:0.2rem 0.25rem;font-size:0.68rem;font-weight:800;text-align:center;'>
                            <button onclick='updateOddValue({$oddId}, this)' title='Guardar cuota' style='border:none;background:rgba(99,102,241,0.18);color:#c4b5fd;border-radius:5px;padding:0.22rem 0.34rem;font-size:0.66rem;font-weight:900;cursor:pointer;'>OK</button>
                        </div>";
                }

                $marketControls .= "
                    <div style='border-top:1px solid var(--border);padding:0.55rem 0;'>
                        <div style='display:flex;align-items:center;justify-content:space-between;gap:0.6rem;margin-bottom:0.45rem;'>
                            <div style='min-width:0;'>
                                <div style='font-size:0.76rem;font-weight:850;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;'>{$marketName}</div>
                                <div id='market-status-{$marketId}' style='font-size:0.67rem;color:var(--text-muted);text-transform:uppercase;'>{$marketStatus}</div>
                            </div>
                            <button onclick='toggleMarket({$marketId}, this)' style='border:none;background:{$marketBtnBg};color:{$marketBtnColor};border-radius:5px;padding:0.28rem 0.45rem;font-size:0.68rem;font-weight:900;cursor:pointer;'>{$marketBtnText}</button>
                        </div>
                        <div style='display:flex;flex-wrap:wrap;gap:0.32rem;'>{$oddButtons}</div>
                    </div>";
            }
            if ($e['status'] === 'finished' && $scoreHome !== '' && $scoreAway !== '') {
                // Partido finalizado CON marcador real obtenido de la API
                $finishControls = "<div style='font-size:0.82rem;font-weight:900;color:var(--success);background:rgba(34,197,94,0.12);border-radius:8px;padding:0.48rem 0.65rem;'>Finalizado {$scoreHome}-{$scoreAway}</div>";
            } else {
                // Partido aún no finalizado O finalizado pero sin marcador (API falló) — inputs manuales
                $btnText = $e['status'] === 'finished' ? 'Fijar Marcador' : 'Finalizar';
                $warningMsg = $e['status'] === 'finished' ? "<div style='font-size:0.65rem;color:#fbbf24;margin-bottom:0.2rem;'>⏳ API no encontró marcador, ingresarlo manual:</div>" : "";
                
                $finishControls = "
                    <div>
                        {$warningMsg}
                        <div class='event-admin-score'>
                            <input id='score-home-{$eventId}' type='number' min='0' value='{$scoreHome}'>
                            <span style='color:var(--text-muted);font-weight:800;'>-</span>
                            <input id='score-away-{$eventId}' type='number' min='0' value='{$scoreAway}'>
                            <button type='button' onclick='window.doFinishEvent(event, {$eventId}, this)' style='cursor:pointer;font-size:0.72rem;font-weight:900;color:#fff;background:var(--primary);padding:0.38rem 0.58rem;border-radius:5px;border:none;'>{$btnText}</button>
                        </div>
                    </div>";
            }

            $html .= "
            <div class='event-admin-card'>
                <div class='event-admin-head'>
                    <div style='min-width:0;'>
                        <div style='font-size:0.72rem; color:var(--text-muted); font-weight:800; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.45rem;'>{$matchNumber}{$stage}{$group}</div>
                        <div class='event-admin-teams'>
                            <div class='event-admin-team'>
                                {$homeFlag}
                                <span>{$homeTeam}</span>
                            </div>
                            <span style='color:var(--text-muted); font-size:0.74rem; font-weight:900;'>VS</span>
                            <div class='event-admin-team away'>
                                <span>{$awayTeam}</span>
                                {$awayFlag}
                            </div>
                        </div>
                        <div class='event-admin-meta'>{$date} &bull; {$venue} &bull; Estado actual: {$eventStatus}</div>
                    </div>
                    <div class='event-admin-actions'>
                        {$finishControls}
                        <button onclick='toggleEvent({$eventId}, this)' style='cursor:pointer; font-size:0.75rem; font-weight:850; color:{$statusColor}; background:{$statusColor}18; padding:0.46rem 0.7rem; border-radius:7px; border:none;'>
                            {$statusText}
                        </button>
                    </div>
                </div>
                <div class='event-admin-pills'>
                    <span id='market-count-{$eventId}' class='event-admin-pill'>{$marketCount} mercados</span>
                    <span class='event-admin-pill' style='color:#86efac;background:rgba(34,197,94,0.12);'>{$openMarketCount} abiertos</span>
                    <span class='event-admin-pill' style='color:#fca5a5;background:rgba(239,68,68,0.12);'>{$suspendedMarketCount} suspendidos</span>
                    <span class='event-admin-pill' style='color:#fbbf24;background:rgba(251,191,36,0.12);'>Expo. $" . number_format($pendingExposure, 2) . "</span>
                    <button onclick='generateMarkets({$eventId}, this)' style='cursor:pointer;font-size:0.72rem;font-weight:900;color:#0f172a;background:linear-gradient(135deg,#34d399,#22c55e);padding:0.32rem 0.58rem;border-radius:6px;border:none;'>Generar mercados</button>
                </div>
                <div class='event-admin-details'>
                    <details>
                        <summary>Control de mercados y cuotas</summary>
                        <div class='event-admin-form' style='grid-template-columns:repeat(2,minmax(0,1fr));'>
                            <input id='market-{$eventId}-name' placeholder='Nombre del mercado'>
                            <input id='market-{$eventId}-type' placeholder='Tipo interno'>
                            <input id='market-{$eventId}-selections' placeholder='Selecciones separadas por coma'>
                            <input id='market-{$eventId}-odds' placeholder='Cuotas separadas por coma'>
                            <button onclick='createEventMarket({$eventId}, this)' style='cursor:pointer;border:none;border-radius:6px;background:linear-gradient(135deg,#34d399,#22c55e);color:#0f172a;font-size:0.76rem;font-weight:900;padding:0.5rem;'>Crear mercado</button>
                        </div>
                        <div style='margin-top:0.7rem;'>{$marketControls}</div>
                    </details>
                    <details>
                        <summary>Editar partido</summary>
                        <div class='event-admin-form' style='grid-template-columns:repeat(2,minmax(0,1fr));'>
                            <input id='event-{$eventId}-home-team' value='{$editHomeTeam}' placeholder='Equipo local'>
                            <input id='event-{$eventId}-away-team' value='{$editAwayTeam}' placeholder='Equipo visitante'>
                            <input id='event-{$eventId}-home-flag' value='{$editHomeFlag}' placeholder='Bandera local'>
                            <input id='event-{$eventId}-away-flag' value='{$editAwayFlag}' placeholder='Bandera visitante'>
                            <input id='event-{$eventId}-stage' value='{$editStage}' placeholder='Fase'>
                            <input id='event-{$eventId}-group' value='{$editGroup}' placeholder='Grupo'>
                            <input id='event-{$eventId}-venue' value='{$editVenue}' placeholder='Estadio'>
                            <input id='event-{$eventId}-start-time' type='datetime-local' value='{$editStartTime}'>
                            <input id='event-{$eventId}-match-number' type='number' min='1' value='{$editMatchNumber}' placeholder='Nro. partido'>
                            <button onclick='saveEventDetails({$eventId}, this)' style='cursor:pointer;border:none;border-radius:6px;background:var(--primary);color:#fff;font-size:0.76rem;font-weight:900;padding:0.5rem;'>Guardar cambios</button>
                        </div>
                    </details>
                </div>
            </div>";
        }

        if (empty($html)) {
            $html = "<div style='padding: 1rem; color:var(--text-muted);'>No hay eventos en este torneo.</div>";
        }

        return $this->response->setBody($html);
    }

    public function updateEvent($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $eventModel = new \App\Models\EventModel();
        $event = $eventModel->find($id);
        if (! $event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado'])->setStatusCode(404);
        }

        if (($event['status'] ?? '') === 'finished') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se puede editar un partido finalizado.',
            ])->setStatusCode(422);
        }

        $homeTeam = trim((string) $this->request->getPost('home_team'));
        $awayTeam = trim((string) $this->request->getPost('away_team'));
        $startTime = trim((string) $this->request->getPost('start_time'));
        $venue = trim((string) $this->request->getPost('venue'));

        if ($homeTeam === '' || $awayTeam === '' || $startTime === '' || $venue === '') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Equipo local, visitante, fecha y estadio son obligatorios.',
            ])->setStatusCode(422);
        }

        $timestamp = strtotime($startTime);
        if ($timestamp === false) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Fecha y hora invalidas.',
            ])->setStatusCode(422);
        }

        $matchNumber = $this->request->getPost('match_number');
        $payload = [
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'home_flag' => trim((string) $this->request->getPost('home_flag')),
            'away_flag' => trim((string) $this->request->getPost('away_flag')),
            'stage' => trim((string) $this->request->getPost('stage')) ?: null,
            'group_name' => trim((string) $this->request->getPost('group_name')) ?: null,
            'venue' => $venue,
            'start_time' => date('Y-m-d H:i:s', $timestamp),
            'match_number' => is_numeric($matchNumber) && (int) $matchNumber > 0 ? (int) $matchNumber : null,
        ];

        $before = array_intersect_key($event, $payload);
        $eventModel->update($id, $payload);
        CacheManager::getInstance()->forget('sports_feed_full');

        AuditLogger::log(
            (int) session()->get('user_id'),
            'event_details_updated',
            'event',
            (int) $id,
            $before,
            $payload
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Partido actualizado.',
            'event' => $payload,
        ]);
    }

    public function createEvent($leagueId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $db = \Config\Database::connect();
        $league = $db->table('leagues')->where('id', $leagueId)->get()->getRowArray();
        if (! $league) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Torneo no encontrado'])->setStatusCode(404);
        }

        $homeTeam = trim((string) $this->request->getPost('home_team'));
        $awayTeam = trim((string) $this->request->getPost('away_team'));
        $startTime = trim((string) $this->request->getPost('start_time'));
        $venue = trim((string) $this->request->getPost('venue'));

        if ($homeTeam === '' || $awayTeam === '' || $startTime === '' || $venue === '') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Equipo local, visitante, fecha y estadio son obligatorios.',
            ])->setStatusCode(422);
        }

        $timestamp = strtotime($startTime);
        if ($timestamp === false) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Fecha y hora invalidas.',
            ])->setStatusCode(422);
        }

        $matchNumber = $this->request->getPost('match_number');
        if (! is_numeric($matchNumber) || (int) $matchNumber <= 0) {
            $maxMatch = $db->table('events')
                ->select('COALESCE(MAX(match_number), 0) as max_match', false)
                ->where('league_id', $leagueId)
                ->get()
                ->getRowArray();
            $matchNumber = ((int) ($maxMatch['max_match'] ?? 0)) + 1;
        }

        $payload = [
            'league_id' => (int) $leagueId,
            'stage' => trim((string) $this->request->getPost('stage')) ?: 'Prepartido',
            'group_name' => trim((string) $this->request->getPost('group_name')) ?: null,
            'match_number' => (int) $matchNumber,
            'home_team' => $homeTeam,
            'home_flag' => trim((string) $this->request->getPost('home_flag')),
            'away_team' => $awayTeam,
            'away_flag' => trim((string) $this->request->getPost('away_flag')),
            'start_time' => date('Y-m-d H:i:s', $timestamp),
            'venue' => $venue,
            'status' => 'pending',
            'settled' => 0,
            'score_home' => null,
            'score_away' => null,
        ];

        $eventModel = new \App\Models\EventModel();
        $eventId = $eventModel->insert($payload, true);
        CacheManager::getInstance()->forget('sports_feed_full');

        AuditLogger::log(
            (int) session()->get('user_id'),
            'event_created',
            'event',
            (int) $eventId,
            null,
            $payload
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Partido creado.',
            'event_id' => (int) $eventId,
        ]);
    }

    public function toggleEventStatus($id)
    {
        // Require admin role
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado']);
        }

        $eventModel = new \App\Models\EventModel();
        $event = $eventModel->find($id);

        if (!$event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado']);
        }

        // Toggle logic: If cancelled -> pending. If pending/live -> cancelled.
        // Finished events should probably stay finished, but for admin override we can allow toggle to cancelled.
        $newStatus = ($event['status'] === 'cancelled') ? 'pending' : 'cancelled';

        $eventModel->update($id, ['status' => $newStatus]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'event_status_changed',
            'event',
            (int) $id,
            ['status' => $event['status']],
            ['status' => $newStatus]
        );

        // Notify cache if needed
        $cache = CacheManager::getInstance();
        $cache->forget('sports_feed_full');

        return $this->response->setJSON(['status' => 'success', 'new_status' => $newStatus]);
    }

    /**
     * Obtener marcadores reales desde The Odds API (solo se ejecuta cuando el admin presiona el botón).
     */
    public function fetchScores()
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $db = \Config\Database::connect();

        // Buscar eventos finalizados sin marcador
        $eventsWithoutScore = $db->table('events e')
            ->select('e.id, e.home_team, e.away_team, l.api_sport_key')
            ->join('leagues l', 'l.id = e.league_id', 'left')
            ->where('e.status', 'finished')
            ->where('e.score_home IS NULL', null, false)
            ->get()->getResultArray();

        if (empty($eventsWithoutScore)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'No hay eventos pendientes de marcador.',
                'updated' => 0,
                'pending' => 0
            ]);
        }

        $fetcher = new \App\Services\ScoreFetcherService();
        $updated = 0;
        $pending = 0;
        $results = [];

        foreach ($eventsWithoutScore as $ev) {
            $score = null;
            if (!empty($ev['api_sport_key'])) {
                $score = $fetcher->fetchScoreForEvent($ev, $ev['api_sport_key']);
            }

            if ($score) {
                [$home, $away] = explode('-', $score);
                $db->table('events')->where('id', $ev['id'])->update([
                    'score_home' => (int)$home,
                    'score_away' => (int)$away
                ]);
                $updated++;
                $results[] = "{$ev['home_team']} vs {$ev['away_team']}: {$home}-{$away} ✓";
            } else {
                $pending++;
                $results[] = "{$ev['home_team']} vs {$ev['away_team']}: no disponible aún";
            }
        }

        // Ejecutar liquidación para los que ahora tienen marcador
        if ($updated > 0) {
            try {
                $settlementService = new \App\Services\SettlementService();
                $settlementService->settleEvents();
            } catch (\Exception $e) {
                log_message('error', 'Error en liquidación tras fetch de scores: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "{$updated} marcadores obtenidos, {$pending} pendientes.",
            'updated' => $updated,
            'pending' => $pending,
            'results' => $results
        ]);
    }

    public function finishEvent($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $scoreHome = $this->request->getPost('score_home');
        $scoreAway = $this->request->getPost('score_away');

        if (!is_numeric($scoreHome) || !is_numeric($scoreAway) || (int) $scoreHome < 0 || (int) $scoreAway < 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Marcador invalido.'])->setStatusCode(422);
        }

        $eventModel = new \App\Models\EventModel();
        $event = $eventModel->find($id);

        if (!$event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado'])->setStatusCode(404);
        }

        $eventModel->update($id, [
            'score_home' => (int) $scoreHome,
            'score_away' => (int) $scoreAway,
            'status' => 'finished',
            'settled' => 0,
        ]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'event_finished',
            'event',
            (int) $id,
            [
                'score_home' => $event['score_home'] ?? null,
                'score_away' => $event['score_away'] ?? null,
                'status' => $event['status'] ?? null,
            ],
            [
                'score_home' => (int) $scoreHome,
                'score_away' => (int) $scoreAway,
                'status' => 'finished',
            ]
        );

        $updatedEvent = $eventModel->find($id);
        $settled = false;
        if ($updatedEvent) {
            $settled = (new \App\Services\SettlementService())->settleEvent($updatedEvent);
        }

        $bracket = (new WorldCupBracketService())->advanceKnockoutRoundsIfReady();

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $settled ? 'Partido finalizado y apuestas liquidadas.' : 'Partido finalizado. Liquidacion pendiente.',
            'bracket_completed' => $bracket['completed'],
            'bracket_message' => $bracket['reason'],
        ]);
    }

    public function generateEventMarkets($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $eventModel = new \App\Models\EventModel();
        $event = $eventModel->find($id);
        if (!$event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado'])->setStatusCode(404);
        }

        if ($event['status'] === 'finished' && (int) ($event['settled'] ?? 0) === 1) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se pueden generar mercados en un partido ya liquidado.',
            ])->setStatusCode(422);
        }

        $result = (new \App\Services\StandardMarketService())->ensureForEvent((int) $id);
        $marketCount = (int) \Config\Database::connect()
            ->table('markets')
            ->where('event_id', $id)
            ->countAllResults();

        AuditLogger::log(
            (int) session()->get('user_id'),
            'event_markets_generated',
            'event',
            (int) $id,
            null,
            [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'market_count' => $marketCount,
            ]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $result['message'],
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'market_count' => $marketCount,
        ]);
    }

    public function generateLeagueMarkets($leagueId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $db = \Config\Database::connect();
        $league = $db->table('leagues')->where('id', $leagueId)->get()->getRowArray();
        if (! $league) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Torneo no encontrado'])->setStatusCode(404);
        }

        $events = $db->table('events')
            ->select('id')
            ->where('league_id', $leagueId)
            ->groupStart()
                ->where('status !=', 'finished')
                ->orWhere('settled', 0)
            ->groupEnd()
            ->get()
            ->getResultArray();

        $service = new \App\Services\StandardMarketService();
        $created = 0;
        $skipped = 0;
        $processed = 0;

        foreach ($events as $event) {
            $result = $service->ensureForEvent((int) $event['id']);
            $created += (int) $result['created'];
            $skipped += (int) $result['skipped'];
            $processed++;
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            'league_markets_generated',
            'league',
            (int) $leagueId,
            null,
            [
                'processed' => $processed,
                'created' => $created,
                'skipped' => $skipped,
            ]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Mercados generados para {$processed} partidos. Nuevos: {$created}. Existentes: {$skipped}.",
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function createEventMarket($eventId)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $eventModel = new \App\Models\EventModel();
        $event = $eventModel->find($eventId);
        if (! $event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado'])->setStatusCode(404);
        }

        if (($event['status'] ?? '') === 'finished') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se puede crear un mercado en un partido finalizado.',
            ])->setStatusCode(422);
        }

        $name = trim((string) $this->request->getPost('name'));
        $type = trim((string) $this->request->getPost('type'));
        $selectionText = trim((string) $this->request->getPost('selections'));
        $oddsText = trim((string) $this->request->getPost('odds'));

        if ($name === '' || $selectionText === '' || $oddsText === '') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Nombre, selecciones y cuotas son obligatorios.',
            ])->setStatusCode(422);
        }

        $selections = array_values(array_filter(array_map('trim', explode(',', $selectionText)), static fn ($value) => $value !== ''));
        $oddsValues = array_values(array_filter(array_map('trim', explode(',', $oddsText)), static fn ($value) => $value !== ''));
        if (count($selections) < 2 || count($selections) !== count($oddsValues)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Debe cargar al menos dos selecciones y la misma cantidad de cuotas.',
            ])->setStatusCode(422);
        }

        $oddsPayload = [];
        foreach ($selections as $index => $selection) {
            $oddValue = str_replace(',', '.', $oddsValues[$index]);
            if (! is_numeric($oddValue)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Todas las cuotas deben ser numericas.',
                ])->setStatusCode(422);
            }

            $decimal = round((float) $oddValue, 2);
            if ($decimal < 1.01 || $decimal > 1000) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Las cuotas deben estar entre 1.01 y 1000.',
                ])->setStatusCode(422);
            }

            $oddsPayload[] = [
                'selection' => mb_substr($selection, 0, 120),
                'odds_decimal' => $decimal,
            ];
        }

        $type = $type !== '' ? strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $type)) : strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $name));
        $type = trim($type, '_') ?: 'custom_market';

        $db = \Config\Database::connect();
        $exists = $db->table('markets')
            ->where('event_id', $eventId)
            ->where('type', $type)
            ->countAllResults();
        if ($exists > 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ya existe un mercado con ese tipo interno en este partido.',
            ])->setStatusCode(422);
        }

        $db->transStart();
        $db->table('markets')->insert([
            'event_id' => (int) $eventId,
            'name' => mb_substr($name, 0, 120),
            'type' => mb_substr($type, 0, 80),
            'status' => 'open',
        ]);
        $marketId = (int) $db->insertID();

        foreach ($oddsPayload as $odd) {
            $db->table('odds')->insert([
                'market_id' => $marketId,
                'selection' => $odd['selection'],
                'odds_decimal' => $odd['odds_decimal'],
                'active' => in_array($event['status'], ['pending', 'live'], true) ? 1 : 0,
                'status' => 'pending',
            ]);
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No se pudo crear el mercado.'])->setStatusCode(500);
        }

        CacheManager::getInstance()->forget('sports_feed_full');
        AuditLogger::log(
            (int) session()->get('user_id'),
            'market_created',
            'market',
            $marketId,
            null,
            [
                'event_id' => (int) $eventId,
                'name' => $name,
                'type' => $type,
                'odds' => $oddsPayload,
            ]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Mercado creado.',
            'market_id' => $marketId,
            'odd_count' => count($oddsPayload),
        ]);
    }

    public function toggleMarketStatus($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $marketModel = new \App\Models\MarketModel();
        $market = $marketModel->find($id);
        if (! $market) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Mercado no encontrado'])->setStatusCode(404);
        }

        $newStatus = $market['status'] === 'open' ? 'suspended' : 'open';
        $marketModel->update($id, ['status' => $newStatus]);

        \Config\Database::connect()
            ->table('odds')
            ->where('market_id', $id)
            ->whereIn('status', ['pending'])
            ->update(['active' => $newStatus === 'open' ? 1 : 0]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'market_status_changed',
            'market',
            (int) $id,
            ['status' => $market['status']],
            ['status' => $newStatus]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'new_status' => $newStatus,
            'message' => $newStatus === 'open' ? 'Mercado reabierto.' : 'Mercado suspendido.',
        ]);
    }

    public function suspendEventMarkets($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $db = \Config\Database::connect();
        $event = $db->table('events')->where('id', $id)->get()->getRowArray();
        if (! $event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado'])->setStatusCode(404);
        }

        $markets = $db->table('markets')->select('id')->where('event_id', $id)->where('status', 'open')->get()->getResultArray();
        $marketIds = array_map(static fn ($row) => (int) $row['id'], $markets);

        if (empty($marketIds)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'El evento no tenia mercados abiertos.',
                'suspended' => 0,
            ]);
        }

        $db->transStart();
        $db->table('markets')->whereIn('id', $marketIds)->update(['status' => 'suspended']);
        $db->table('odds')->whereIn('market_id', $marketIds)->where('status', 'pending')->update(['active' => 0]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No se pudieron suspender los mercados.'])->setStatusCode(500);
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            'event_markets_suspended',
            'event',
            (int) $id,
            ['open_market_ids' => $marketIds],
            ['status' => 'suspended', 'suspended_count' => count($marketIds)]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Mercados del evento suspendidos.',
            'suspended' => count($marketIds),
        ]);
    }

    public function toggleOddStatus($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $oddModel = new \App\Models\OddModel();
        $odd = $oddModel->find($id);
        if (! $odd) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cuota no encontrada'])->setStatusCode(404);
        }

        if (! in_array($odd['status'], ['pending', 'void'], true)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se puede cambiar una cuota ya liquidada.',
            ])->setStatusCode(422);
        }

        $newActive = (int) $odd['active'] === 1 ? 0 : 1;
        $oddModel->update($id, [
            'active' => $newActive,
            'status' => $newActive === 1 ? 'pending' : 'void',
        ]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            'odd_status_changed',
            'odd',
            (int) $id,
            ['active' => (int) $odd['active'], 'status' => $odd['status']],
            ['active' => $newActive, 'status' => $newActive === 1 ? 'pending' : 'void']
        );

        return $this->response->setJSON([
            'status' => 'success',
            'active' => $newActive,
            'new_status' => $newActive === 1 ? 'pending' : 'void',
            'message' => $newActive === 1 ? 'Cuota reabierta.' : 'Cuota suspendida.',
        ]);
    }

    public function updateOdd($id)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $oddModel = new \App\Models\OddModel();
        $odd = $oddModel->find($id);
        if (! $odd) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Cuota no encontrada'])->setStatusCode(404);
        }

        if (! in_array($odd['status'], ['pending', 'void'], true)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No se puede editar una cuota ya liquidada.',
            ])->setStatusCode(422);
        }

        $value = $this->request->getPost('odds_decimal');
        if (! is_numeric($value)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'La cuota debe ser numerica.',
            ])->setStatusCode(422);
        }

        $newValue = round((float) $value, 2);
        if ($newValue < 1.01 || $newValue > 1000) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'La cuota debe estar entre 1.01 y 1000.',
            ])->setStatusCode(422);
        }

        $oldValue = round((float) $odd['odds_decimal'], 2);
        $oddModel->update($id, ['odds_decimal' => $newValue]);
        CacheManager::getInstance()->forget('sports_feed_full');

        AuditLogger::log(
            (int) session()->get('user_id'),
            'odd_value_changed',
            'odd',
            (int) $id,
            ['odds_decimal' => $oldValue],
            ['odds_decimal' => $newValue]
        );

        return $this->response->setJSON([
            'status' => 'success',
            'odds_decimal' => number_format($newValue, 2, '.', ''),
            'message' => 'Cuota actualizada.',
        ]);
    }

    public function worldCupBracket(string $stage)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado'])->setStatusCode(403);
        }

        $service = new WorldCupBracketService();
        $method = match ($stage) {
            '16avos' => 'bracket16avos',
            '8vos' => 'bracket8vos',
            '4tos', '4vos' => 'bracket4tos',
            'semis' => 'bracketSemifinales',
            'final' => 'bracketFinal',
            default => null,
        };

        if ($method === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Etapa invalida.'])->setStatusCode(404);
        }

        $result = $service->{$method}();

        return $this->response->setJSON([
            'status' => $result['completed'] ? 'success' : 'error',
            'message' => $result['reason'],
            'completed' => $result['completed'],
        ])->setStatusCode($result['completed'] ? 200 : 422);
    }

    public function transactions()
    {
        $db = \Config\Database::connect();
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        $filters = $this->transactionFilters();

        $base = $db->table('transactions t')
            ->join('wallets w', 'w.id = t.wallet_id')
            ->join('users u', 'u.id = w.user_id')
            ->select('t.*, u.username, u.email, w.currency');
        $this->applyTransactionFilters($base, $filters);

        $totalTransactions = (clone $base)->countAllResults();
        $transactions = (clone $base)
            ->orderBy('t.created_at', 'DESC')
            ->orderBy('t.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $summaryBase = $db->table('transactions t')
            ->join('wallets w', 'w.id = t.wallet_id')
            ->join('users u', 'u.id = w.user_id')
            ->select('t.type, COUNT(*) as count, COALESCE(SUM(t.amount), 0) as total', false);
        $this->applyTransactionFilters($summaryBase, $filters);
        $summaryRows = $summaryBase->groupBy('t.type')->get()->getResultArray();

        $summaryByType = [];
        foreach ($summaryRows as $row) {
            $summaryByType[$row['type']] = [
                'count' => (int) $row['count'],
                'total' => (float) $row['total'],
            ];
        }

        $totalDeposits = (float) ($summaryByType['deposit']['total'] ?? 0);
        $totalWithdrawals = abs((float) ($summaryByType['withdrawal']['total'] ?? 0));
        $totalBets = abs((float) ($summaryByType['bet_placed']['total'] ?? 0));
        $totalPayouts = (float) ($summaryByType['bet_won']['total'] ?? 0);
        $netCollected = $totalBets - $totalPayouts;

        $byUser = $db->table('transactions t')
            ->join('wallets w', 'w.id = t.wallet_id')
            ->join('users u', 'u.id = w.user_id')
            ->select("
                u.id,
                u.username,
                u.email,
                COUNT(t.id) as transaction_count,
                COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) as deposits,
                COALESCE(SUM(CASE WHEN t.type = 'withdrawal' THEN ABS(t.amount) ELSE 0 END), 0) as withdrawals,
                COALESCE(SUM(CASE WHEN t.type = 'bet_placed' THEN ABS(t.amount) ELSE 0 END), 0) as bets,
                COALESCE(SUM(CASE WHEN t.type = 'bet_won' THEN t.amount ELSE 0 END), 0) as payouts
            ")
            ->groupBy('u.id, u.username, u.email');
        $this->applyTransactionFilters($byUser, $filters);
        $byUser = $byUser
            ->orderBy('bets', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $byDay = $db->table('transactions t')
            ->join('wallets w', 'w.id = t.wallet_id')
            ->join('users u', 'u.id = w.user_id')
            ->select("
                DATE(t.created_at) as tx_date,
                COUNT(t.id) as transaction_count,
                COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) as deposits,
                COALESCE(SUM(CASE WHEN t.type = 'bet_placed' THEN ABS(t.amount) ELSE 0 END), 0) as bets,
                COALESCE(SUM(CASE WHEN t.type = 'bet_won' THEN t.amount ELSE 0 END), 0) as payouts
            ")
            ->groupBy('DATE(t.created_at)');
        $this->applyTransactionFilters($byDay, $filters);
        $byDay = $byDay
            ->orderBy('tx_date', 'DESC')
            ->limit(14)
            ->get()
            ->getResultArray();

        $byEvent = $db->table('bet_selections bs')
            ->join('bet_slips b', 'b.id = bs.bet_slip_id')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->join('leagues l', 'l.id = e.league_id')
            ->select("
                e.id,
                e.home_team,
                e.away_team,
                l.name as league_name,
                COUNT(DISTINCT b.id) as ticket_count,
                COUNT(bs.id) as selection_count,
                COALESCE(SUM(b.stake), 0) as stake_sum,
                COALESCE(SUM(CASE WHEN b.status = 'won' THEN b.potential_payout ELSE 0 END), 0) as payout_sum
            ")
            ->groupBy('e.id, e.home_team, e.away_team, l.name')
            ->orderBy('stake_sum', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $cashierUsers = $db->table('users u')
            ->select('u.id, u.username, u.email, u.is_active, COALESCE(w.balance, 0) as balance', false)
            ->join('wallets w', 'w.user_id = u.id', 'left')
            ->where('u.deleted_at', null)
            ->where('u.role_id !=', 1)
            ->orderBy('u.username', 'ASC')
            ->get()
            ->getResultArray();

        $data = [
            'transactions' => $transactions,
            'totalTransactions' => $totalTransactions,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($totalTransactions / $perPage)),
            'summaryByType' => $summaryByType,
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'totalBets' => $totalBets,
            'totalPayouts' => $totalPayouts,
            'netCollected' => $netCollected,
            'byUser' => $byUser,
            'byDay' => $byDay,
            'byEvent' => $byEvent,
            'cashierUsers' => $cashierUsers,
            'filters' => $filters,
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/transactions', $data);
        }
        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'transactions']));
    }

    public function withdrawals()
    {
        $db = \Config\Database::connect();
        $status = (string) ($this->request->getGet('status') ?? 'pending');
        $allowed = ['all', 'pending', 'approved', 'rejected'];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $base = $db->table('withdrawal_requests wr')
            ->select('wr.*, u.username, u.email, u.document_type, u.document_number, w.balance as current_balance, admin.username as processed_by_name')
            ->join('users u', 'u.id = wr.user_id')
            ->join('wallets w', 'w.id = wr.wallet_id')
            ->join('users admin', 'admin.id = wr.processed_by', 'left');

        if ($status !== 'all') {
            $base->where('wr.status', $status);
        }

        $requests = $base
            ->orderBy('wr.created_at', 'DESC')
            ->orderBy('wr.id', 'DESC')
            ->get()
            ->getResultArray();

        $summaryRows = $db->table('withdrawal_requests')
            ->select('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total', false)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $summary = [
            'pending' => ['count' => 0, 'total' => 0],
            'approved' => ['count' => 0, 'total' => 0],
            'rejected' => ['count' => 0, 'total' => 0],
        ];
        foreach ($summaryRows as $row) {
            $summary[$row['status']] = ['count' => (int) $row['count'], 'total' => (float) $row['total']];
        }

        $data = [
            'withdrawals' => $requests,
            'withdrawalStatus' => $status,
            'withdrawalSummary' => $summary,
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/withdrawals', $data);
        }

        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'withdrawals']));
    }

    public function kyc()
    {
        $db = \Config\Database::connect();
        $status = (string) ($this->request->getGet('status') ?? 'pending');
        $allowed = ['all', 'pending', 'approved', 'rejected'];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $base = $db->table('kyc_verifications k')
            ->select('k.*, u.username, u.email, u.country, u.birthdate, u.kyc_status, admin.username as verified_by_name')
            ->join('users u', 'u.id = k.user_id')
            ->join('users admin', 'admin.id = k.verified_by', 'left');

        if ($status !== 'all') {
            $base->where('k.status', $status);
        }

        $records = $base
            ->orderBy('k.created_at', 'DESC')
            ->orderBy('k.id', 'DESC')
            ->get()
            ->getResultArray();

        $summaryRows = $db->table('kyc_verifications')
            ->select('status, COUNT(*) as count', false)
            ->groupBy('status')
            ->get()
            ->getResultArray();
        $summary = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($summaryRows as $row) {
            $summary[$row['status']] = (int) $row['count'];
        }

        $data = [
            'kycRecords' => $records,
            'kycStatus' => $status,
            'kycSummary' => $summary,
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/kyc', $data);
        }

        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'kyc']));
    }

    public function approveKyc(int $kycId)
    {
        return $this->processKyc($kycId, 'approved');
    }

    public function rejectKyc(int $kycId)
    {
        return $this->processKyc($kycId, 'rejected');
    }

    private function processKyc(int $kycId, string $decision)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $note = trim((string) ($this->request->getPost('note') ?? ''));
        $kycModel = new KYCVerificationModel();
        $record = $kycModel->find($kycId);

        if (! $record) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'KYC no encontrado.']);
        }

        $data = [
            'status' => $decision,
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => (int) session()->get('user_id'),
            'notes' => $note !== '' ? mb_substr($note, 0, 255) : null,
            'rejection_reason' => $decision === 'rejected' ? ($note !== '' ? mb_substr($note, 0, 255) : 'Documento no aprobado') : null,
        ];

        $kycModel->update($kycId, $data);
        (new UserModel())->update((int) $record['user_id'], ['kyc_status' => $decision]);

        AuditLogger::log(
            (int) session()->get('user_id'),
            $decision === 'approved' ? 'kyc_approved' : 'kyc_rejected',
            'kyc_verification',
            $kycId,
            ['status' => $record['status']],
            ['status' => $decision, 'note' => $note, 'user_id' => (int) $record['user_id']]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => $decision === 'approved' ? 'KYC aprobado.' : 'KYC rechazado.',
        ]);
    }

    public function approveWithdrawal(int $requestId)
    {
        return $this->processWithdrawal($requestId, 'approved');
    }

    public function rejectWithdrawal(int $requestId)
    {
        return $this->processWithdrawal($requestId, 'rejected');
    }

    private function processWithdrawal(int $requestId, string $decision)
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $adminNote = trim((string) ($this->request->getPost('admin_note') ?? ''));
        $requestModel = new WithdrawalRequestModel();
        $walletModel = new WalletModel();
        $txModel = new TransactionModel();
        $db = \Config\Database::connect();

        $request = $requestModel->find($requestId);
        if (! $request) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Solicitud no encontrada.']);
        }

        if (($request['status'] ?? '') !== 'pending') {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'La solicitud ya fue procesada.']);
        }

        $owner = $db->table('users')
            ->select('id, username, document_number')
            ->where('id', (int) $request['user_id'])
            ->get()
            ->getRowArray();

        $requestDocument = preg_replace('/\D+/', '', (string) ($request['account_document'] ?? ''));
        $ownerDocument = preg_replace('/\D+/', '', (string) ($owner['document_number'] ?? ''));
        if ($decision === 'approved') {
            if ((int) ($request['own_account_confirmed'] ?? 0) !== 1 || $requestDocument === '' || $ownerDocument === '' || $requestDocument !== $ownerDocument) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'No se puede aprobar: la cuenta destino no esta validada como cuenta propia del apostador.',
                ]);
            }
        }

        $wallet = $walletModel->find((int) $request['wallet_id']);
        if (! $wallet) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Wallet no encontrada.']);
        }

        $amount = round((float) $request['amount'], 2);
        $currentBalance = (float) $wallet['balance'];

        $db->transStart();

        $newBalance = $currentBalance;
        if ($decision === 'rejected') {
            $newBalance = round($currentBalance + $amount, 2);
            $walletModel->update((int) $wallet['id'], ['balance' => $newBalance]);
        } else {
            $txModel->insert([
                'wallet_id' => (int) $wallet['id'],
                'type' => 'withdrawal',
                'amount' => -$amount,
                'balance_after' => $currentBalance,
                'reference_id' => $requestId,
                'description' => 'Retiro aprobado Solicitud #' . $requestId,
                'target_account' => $request['target_account'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $requestModel->update($requestId, [
            'status' => $decision,
            'admin_note' => $adminNote !== '' ? mb_substr($adminNote, 0, 255) : null,
            'processed_by' => (int) session()->get('user_id'),
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'No se pudo procesar la solicitud.']);
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            $decision === 'approved' ? 'withdrawal_approved' : 'withdrawal_rejected',
            'withdrawal_request',
            $requestId,
            ['status' => 'pending', 'wallet_balance' => $currentBalance],
            [
                'status' => $decision,
                'amount' => $amount,
                'wallet_balance' => $newBalance,
                'admin_note' => $adminNote,
                'target_account' => $request['target_account'] ?? null,
                'account_holder' => $request['account_holder'] ?? null,
                'account_document' => $request['account_document'] ?? null,
                'own_account_confirmed' => (int) ($request['own_account_confirmed'] ?? 0) === 1,
            ]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => $decision === 'approved' ? 'Retiro aprobado.' : 'Retiro rechazado y saldo devuelto.',
        ]);
    }

    public function exportTransactions()
    {
        $db = \Config\Database::connect();
        $filters = $this->transactionFilters();
        $transactions = $db->table('transactions t')
            ->select('t.*, u.username, u.email, w.currency')
            ->join('wallets w', 'w.id = t.wallet_id')
            ->join('users u', 'u.id = w.user_id');
        $this->applyTransactionFilters($transactions, $filters);
        $transactions = $transactions
            ->orderBy('t.created_at', 'DESC')
            ->orderBy('t.id', 'DESC')
            ->get()
            ->getResultArray();

        $rows = [];
        foreach ($transactions as $tx) {
            $rows[] = [
                $tx['id'],
                $tx['created_at'],
                $tx['username'] ?? '',
                $tx['email'] ?? '',
                $tx['type'],
                number_format((float) $tx['amount'], 2, '.', ''),
                number_format((float) ($tx['commission'] ?? 0), 2, '.', ''),
                number_format((float) $tx['balance_after'], 2, '.', ''),
                $tx['currency'] ?? '',
                $tx['reference_id'] ?? '',
                $tx['target_account'] ?? '',
                $tx['description'] ?? '',
            ];
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            'admin_transactions_exported',
            'transaction',
            null,
            null,
            ['rows' => count($rows), 'filters' => $filters]
        );

        return $this->csvResponse(
            'transacciones-' . date('Ymd-His') . '.csv',
            ['ID', 'Fecha', 'Usuario', 'Email', 'Tipo', 'Monto', 'Comision', 'Saldo posterior', 'Moneda', 'Referencia', 'Cuenta destino', 'Descripcion'],
            $rows
        );
    }

    public function walletAdjustment()
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $rules = [
            'user_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[deposit,withdrawal]',
            'amount' => 'required|decimal|greater_than[0]',
            'description' => 'permit_empty|max_length[255]',
            'commission' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'target_account' => 'permit_empty|max_length[120]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $userId = (int) $this->request->getPost('user_id');
        $type = (string) $this->request->getPost('type');
        $amount = round((float) $this->request->getPost('amount'), 2);
        $commission = round((float) ($this->request->getPost('commission') ?? 0), 2);
        $description = trim((string) ($this->request->getPost('description') ?? ''));
        $targetAccount = trim((string) ($this->request->getPost('target_account') ?? ''));

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (! $user || (int) $user['role_id'] === 1) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Usuario apostador no encontrado.']);
        }

        if (empty($user['is_active'])) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'No se puede operar sobre un usuario inactivo.']);
        }

        $db = \Config\Database::connect();
        $walletModel = new WalletModel();
        $transactionModel = new TransactionModel();

        $db->transStart();

        $wallet = $walletModel->where('user_id', $userId)->first();
        if (! $wallet) {
            $walletModel->insert([
                'user_id' => $userId,
                'balance' => 0,
                'currency' => 'ARS',
            ]);
            $wallet = $walletModel->where('user_id', $userId)->first();
        }

        $currentBalance = (float) $wallet['balance'];
        $signedAmount = $type === 'withdrawal' ? -$amount : $amount;
        $newBalance = round($currentBalance + $signedAmount, 2);

        if ($newBalance < 0) {
            $db->transRollback();
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Saldo insuficiente. Saldo actual: ' . number_format($currentBalance, 2),
            ]);
        }

        $walletModel->update((int) $wallet['id'], ['balance' => $newBalance]);
        $transactionModel->insert([
            'wallet_id' => (int) $wallet['id'],
            'type' => $type,
            'amount' => $signedAmount,
            'balance_after' => $newBalance,
            'reference_id' => null,
            'description' => $description !== '' ? $description : ($type === 'deposit' ? 'Recarga manual de administrador' : 'Retiro manual de administrador'),
            'commission' => $commission,
            'target_account' => $targetAccount !== '' ? $targetAccount : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'No se pudo registrar el movimiento.']);
        }

        AuditLogger::log(
            (int) session()->get('user_id'),
            $type === 'deposit' ? 'admin_wallet_deposit' : 'admin_wallet_withdrawal',
            'wallet',
            (int) $wallet['id'],
            ['balance' => $currentBalance],
            [
                'user_id' => $userId,
                'amount' => $signedAmount,
                'balance' => $newBalance,
                'commission' => $commission,
                'target_account' => $targetAccount,
            ]
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => $type === 'deposit' ? 'Recarga registrada.' : 'Retiro registrado.',
            'new_balance' => $newBalance,
        ]);
    }

    public function audit()
    {
        $db = \Config\Database::connect();
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $base = $db->table('audit_logs a')
            ->select('a.*, u.username')
            ->join('users u', 'u.id = a.user_id', 'left');

        $total = (clone $base)->countAllResults(false);
        $logs = (clone $base)
            ->orderBy('a.created_at', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $data = [
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'totalLogs' => $total,
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/audit', $data);
        }

        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'audit']));
    }

    public function settings()
    {
        // Programmatic migration check & execution
        $migrations = \Config\Services::migrations();
        try {
            $migrations->latest();
        } catch (\Throwable $t) {
            log_message('error', 'Migration error: ' . $t->getMessage());
        }

        $settingModel = new \App\Models\SystemSettingModel();
        $settings = $settingModel->getAllSettings();

        $data = ['settings' => $settings];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/settings', $data);
        }
        return view('dashboard/index', array_merge($data, ['layout' => 'layouts/main', 'activePage' => 'settings']));
    }

    public function updateSettings()
    {
        if (session()->get('role_id') != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Acceso denegado.']);
        }

        $settingModel = new \App\Models\SystemSettingModel();

        // 1. Save text settings
        $fields = [
            'platform_name' => $this->request->getPost('platform_name'),
            'base_url' => $this->request->getPost('base_url'),
            'timezone' => $this->request->getPost('timezone'),
            
            // Security
            'security_2fa' => $this->request->getPost('security_2fa') ? '1' : '0',
            'security_lockout' => $this->request->getPost('security_lockout') ? '1' : '0',
            'security_sessions' => $this->request->getPost('security_sessions') ? '1' : '0',
            
            // Notifications
            'notify_email' => $this->request->getPost('notify_email') ? '1' : '0',
            'notify_security' => $this->request->getPost('notify_security') ? '1' : '0',
            'notify_marketing' => $this->request->getPost('notify_marketing') ? '1' : '0',

            // Bank details
            'bank_name' => $this->request->getPost('bank_name'),
            'bank_holder' => $this->request->getPost('bank_holder'),
            'bank_cbu_cvu' => $this->request->getPost('bank_cbu_cvu'),
            'bank_alias' => $this->request->getPost('bank_alias'),

            // Mercado Pago details
            'mp_qr_account' => $this->request->getPost('mp_qr_account'),
            'mp_access_token' => $this->request->getPost('mp_access_token'),
            'mp_public_key' => $this->request->getPost('mp_public_key'),
            'mp_card_enabled' => $this->request->getPost('mp_card_enabled') ? '1' : '0',

            // Risk controls
            'risk_min_stake' => $this->request->getPost('risk_min_stake'),
            'risk_max_stake' => $this->request->getPost('risk_max_stake'),
            'risk_max_payout' => $this->request->getPost('risk_max_payout'),
            'risk_max_user_daily_stake' => $this->request->getPost('risk_max_user_daily_stake'),
            'risk_max_event_exposure' => $this->request->getPost('risk_max_event_exposure'),
            'risk_max_market_exposure' => $this->request->getPost('risk_max_market_exposure'),
        ];

        foreach ([
            'risk_min_stake',
            'risk_max_stake',
            'risk_max_payout',
            'risk_max_user_daily_stake',
            'risk_max_event_exposure',
            'risk_max_market_exposure',
        ] as $riskField) {
            if ($fields[$riskField] !== null && (float) $fields[$riskField] < 0) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Los limites de riesgo no pueden ser negativos.',
                ]);
            }
        }

        // Custom validation for CBU (must be 22 digits) if provided
        if (!empty($fields['bank_cbu_cvu'])) {
            $cbuClean = preg_replace('/\D/', '', $fields['bank_cbu_cvu']);
            if (strlen($cbuClean) !== 22) {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'El CBU/CVU bancario debe tener exactamente 22 dígitos numéricos.'
                ]);
            }
            $fields['bank_cbu_cvu'] = $cbuClean; // store cleaned digits
        }

        foreach ($fields as $key => $val) {
            if ($val !== null) {
                $settingModel->setSetting($key, $val);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Configuraciones guardadas correctamente.'
        ]);
    }

    private function rankRows(array $rows, string $memberField, string $scoreField): array
    {
        $ranked = [];
        $rank = 1;
        foreach ($rows as $row) {
            $ranked[] = [
                'rank' => $rank++,
                'member' => (string) ($row[$memberField] ?? 'Sin nombre'),
                'score' => round((float) ($row[$scoreField] ?? 0), 2),
            ];
        }

        return $ranked;
    }

    private function getRealRankingData(): array
    {
        $db = \Config\Database::connect();

        $topBettorsRows = $db->table('bet_slips b')
            ->select('u.username, COUNT(b.id) as tickets, COALESCE(SUM(b.stake), 0) as total_stake', false)
            ->join('users u', 'u.id = b.user_id', 'left')
            ->groupBy('u.id, u.username')
            ->having('total_stake >', 0)
            ->orderBy('total_stake', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $topWinnersRows = $db->table('bet_slips b')
            ->select("
                u.username,
                COALESCE(SUM(CASE WHEN b.status = 'won' THEN b.potential_payout ELSE 0 END), 0)
                - COALESCE(SUM(b.stake), 0) as profit
            ", false)
            ->join('users u', 'u.id = b.user_id', 'left')
            ->groupBy('u.id, u.username')
            ->orderBy('profit', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $hotEventsRows = $db->table('bet_selections bs')
            ->select("
                e.id as event_id,
                e.home_team,
                e.away_team,
                COUNT(DISTINCT b.id) as tickets,
                COALESCE(SUM(b.stake), 0) as total_stake
            ", false)
            ->join('bet_slips b', 'b.id = bs.bet_slip_id')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->groupBy('e.id, e.home_team, e.away_team')
            ->having('total_stake >', 0)
            ->orderBy('total_stake', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        foreach ($hotEventsRows as &$eventRow) {
            $eventRow['event_name'] = trim((string) ($eventRow['home_team'] ?? '')) . ' vs ' . trim((string) ($eventRow['away_team'] ?? ''));
        }
        unset($eventRow);

        return [
            'topBettors' => $this->rankRows($topBettorsRows, 'username', 'total_stake'),
            'topWinners' => $this->rankRows($topWinnersRows, 'username', 'profit'),
            'hotEvents' => $this->rankRows($hotEventsRows, 'event_name', 'total_stake'),
        ];
    }

    /**
     * Rankings & Cache Monitor — powered by real betting data.
     */
    public function rankings()
    {
        $queue = new QueueManager();
        $realRankings = $this->getRealRankingData();

        $data = [
            'topBettors' => $realRankings['topBettors'],
            'topWinners' => $realRankings['topWinners'],
            'hotEvents' => $realRankings['hotEvents'],
            'cacheStats' => $this->cache->getStats(),
            'queueStats' => $queue->getStats(),
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->htmxFragment('dashboard/partials/rankings', $data);
        }
        return view('dashboard/index', array_merge($data, [
            'layout' => 'layouts/main',
            'activePage' => 'rankings'
        ]));
    }
}
