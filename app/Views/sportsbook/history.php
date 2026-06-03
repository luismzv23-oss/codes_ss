<?php
    $history = $history ?? [];
    $summary = $summary ?? [];
    $status = $status ?? 'all';
    $search = $search ?? '';
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $totalTickets = $totalTickets ?? count($history);

    $money = static fn ($value): string => number_format((float) $value, 2) . ' K';
    $statusText = static fn (string $value): string => match ($value) {
        'pending' => 'En juego',
        'won' => 'Ganada',
        'lost' => 'Perdida',
        'void' => 'Anulada',
        'cashed_out' => 'Cash out',
        default => ucfirst($value),
    };
    $statusClass = static fn (string $value): string => match ($value) {
        'won' => 'status-won',
        'lost' => 'status-lost',
        'void', 'cashed_out' => 'status-void',
        default => 'status-pending',
    };
    $buildUrl = static function (array $overrides = []) use ($status, $search): string {
        $params = array_merge(['status' => $status, 'q' => $search], $overrides);
        $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
        return '/sportsbook/history' . ($params ? '?' . http_build_query($params) : '');
    };
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Mis Apuestas') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg-body: #09111f;
            --bg-panel: #152033;
            --bg-soft: #1e2b40;
            --primary: #f97316;
            --text-main: #f8fafc;
            --text-muted: #93a4bd;
            --border: rgba(148, 163, 184, 0.18);
            --success: #22c55e;
            --danger: #fb7185;
            --pending: #fbbf24;
            --cyan: #38bdf8;
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, sans-serif; background: var(--bg-body); color: var(--text-main); }
        .topbar { height: 64px; background: var(--bg-panel); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; position: sticky; top: 0; z-index: 50; }
        .logo { font-family: Outfit, sans-serif; font-weight: 900; font-size: 1.55rem; color: var(--primary); text-decoration: none; }
        .user-nav { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; justify-content: flex-end; }
        .pill { background: rgba(255,255,255,0.06); border: 1px solid var(--border); border-radius: 0.6rem; padding: 0.45rem 0.75rem; font-weight: 800; font-size: 0.86rem; display: inline-flex; align-items: center; gap: 0.35rem; }
        .btn { border: 1px solid var(--border); border-radius: 0.55rem; color: var(--text-main); cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 800; padding: 0.55rem 0.8rem; text-decoration: none; background: rgba(255,255,255,0.06); }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-ghost:hover, .btn:hover { background: rgba(255,255,255,0.1); }
        .container { max-width: 1120px; margin: 1.5rem auto 3rem; padding: 0 1rem; }
        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .page-title { font-family: Outfit, sans-serif; font-size: 2rem; font-weight: 900; margin: 0; }
        .page-subtitle { color: var(--text-muted); margin: 0.25rem 0 0; }
        .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
        .kpi { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.9rem; }
        .kpi-label { color: var(--text-muted); font-size: 0.72rem; font-weight: 900; text-transform: uppercase; }
        .kpi-value { font-family: Outfit, sans-serif; font-size: 1.35rem; font-weight: 900; margin-top: 0.25rem; }
        .filters { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.85rem; display: flex; gap: 0.65rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; }
        .tabs { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .tab { color: var(--text-muted); border: 1px solid var(--border); border-radius: 999px; padding: 0.45rem 0.75rem; text-decoration: none; font-size: 0.8rem; font-weight: 900; }
        .tab.active { background: rgba(249, 115, 22, 0.14); color: #fff; border-color: rgba(249, 115, 22, 0.45); }
        .search { display: flex; align-items: center; gap: 0.45rem; background: var(--bg-soft); border: 1px solid var(--border); border-radius: 0.6rem; padding: 0.45rem 0.65rem; min-width: min(100%, 340px); }
        .search input { background: none; border: 0; color: var(--text-main); outline: none; width: 100%; font: inherit; }
        .ticket-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.8rem; padding: 1rem; margin-bottom: 1rem; }
        .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.85rem; margin-bottom: 0.75rem; }
        .ticket-id { font-family: Outfit, sans-serif; font-size: 1.1rem; font-weight: 900; }
        .ticket-date { color: var(--text-muted); font-size: 0.78rem; margin-top: 0.15rem; }
        .actions { display: flex; gap: 0.45rem; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
        .status-badge { padding: 0.32rem 0.65rem; border-radius: 999px; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; }
        .status-pending { background: rgba(251, 191, 36, 0.14); color: var(--pending); }
        .status-won { background: rgba(34, 197, 94, 0.14); color: var(--success); }
        .status-lost { background: rgba(251, 113, 133, 0.14); color: var(--danger); }
        .status-void { background: rgba(148, 163, 184, 0.14); color: var(--text-muted); }
        .selection-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; align-items: center; padding: 0.75rem 0; border-bottom: 1px dashed var(--border); }
        .selection-item:last-child { border-bottom: none; }
        .team-names { font-weight: 900; margin-bottom: 0.2rem; }
        .market-info, .league-info { font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; }
        .odd-info { text-align: right; min-width: 170px; }
        .odd-selection { font-weight: 900; color: var(--primary); }
        .odd-value { display: inline-flex; justify-content: center; min-width: 54px; color: #fff; background: rgba(255,255,255,0.1); padding: 0.25rem 0.45rem; border-radius: 0.35rem; font-weight: 900; margin-left: 0.4rem; }
        .ticket-footer { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; background: rgba(0,0,0,0.18); padding: 0.85rem; border-radius: 0.6rem; margin-top: 0.8rem; }
        .footer-item { min-width: 0; }
        .footer-label { display: block; font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 900; margin-bottom: 0.2rem; }
        .footer-value { font-family: Outfit, sans-serif; font-weight: 900; font-size: 1.05rem; }
        .empty { text-align: center; padding: 4rem 1rem; color: var(--text-muted); background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.8rem; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.75rem; margin-top: 1rem; color: var(--text-muted); font-weight: 800; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        @media (max-width: 760px) {
            .topbar, .page-head, .ticket-header { align-items: flex-start; flex-direction: column; }
            .kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .selection-item, .ticket-footer { grid-template-columns: 1fr; }
            .odd-info { text-align: left; min-width: 0; }
            .actions { justify-content: flex-start; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/" class="logo">Codex SS</a>
        <div class="user-nav">
            <span class="pill"><i data-lucide="wallet" style="width:16px;height:16px;"></i><?= $money($walletBalance) ?></span>
            <span class="pill"><i data-lucide="user" style="width:16px;height:16px;"></i><?= esc(session()->get('username')) ?></span>
            <a href="/" class="btn btn-primary">Volver al Sportsbook</a>
        </div>
    </header>

    <main class="container">
        <div class="page-head">
            <div>
                <h1 class="page-title">Mis Apuestas</h1>
                <p class="page-subtitle">Historial, estado y comprobantes de tus tickets.</p>
            </div>
            <div class="pill"><?= (int) $totalTickets ?> tickets encontrados</div>
        </div>

        <section class="kpis">
            <div class="kpi">
                <div class="kpi-label">Apostado total</div>
                <div class="kpi-value"><?= $money($summary['total_stake'] ?? 0) ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Tickets abiertos</div>
                <div class="kpi-value" style="color:var(--pending);"><?= (int) ($summary['pending_tickets'] ?? 0) ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Premios cobrados</div>
                <div class="kpi-value" style="color:var(--success);"><?= $money($summary['paid_payout'] ?? 0) ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Pago potencial</div>
                <div class="kpi-value" style="color:var(--cyan);"><?= $money($summary['pending_payout'] ?? 0) ?></div>
            </div>
        </section>

        <section class="filters">
            <nav class="tabs">
                <?php foreach (['all' => 'Todos', 'pending' => 'En juego', 'won' => 'Ganadas', 'lost' => 'Perdidas', 'void' => 'Anuladas'] as $value => $label): ?>
                    <a class="tab <?= $status === $value ? 'active' : '' ?>" href="<?= esc($buildUrl(['status' => $value, 'page' => 1])) ?>"><?= esc($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="search" method="get" action="/sportsbook/history">
                <?php if ($status !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= esc($status) ?>">
                <?php endif; ?>
                <i data-lucide="search" style="width:16px;height:16px;color:var(--text-muted);"></i>
                <input name="q" value="<?= esc($search) ?>" placeholder="Buscar ticket, equipo, liga o mercado">
            </form>
        </section>

        <?php if (empty($history)): ?>
            <section class="empty">
                <i data-lucide="ticket" style="width:48px;height:48px;margin-bottom:1rem;opacity:0.55;"></i>
                <h3>No hay tickets para estos filtros.</h3>
                <p>Volvi al Sportsbook o cambia la busqueda para ver mas apuestas.</p>
                <a href="/" class="btn btn-primary" style="margin-top:0.75rem;">Ver eventos</a>
            </section>
        <?php else: ?>
            <?php foreach ($history as $ticket): ?>
                <?php
                    $slip = $ticket['slip'];
                    $ticketType = count($ticket['selections']) > 1 ? 'Combinada' : 'Simple';
                ?>
                <article class="ticket-card">
                    <div class="ticket-header">
                        <div>
                            <div class="ticket-id">Ticket #<?= str_pad((string) $slip['id'], 6, '0', STR_PAD_LEFT) ?></div>
                            <div class="ticket-date"><?= date('d/m/Y H:i', strtotime($slip['created_at'])) ?> - <?= esc($ticketType) ?></div>
                        </div>
                        <div class="actions">
                            <a class="btn btn-primary" href="/sportsbook/ticket/<?= (int) $slip['id'] ?>" target="_blank" rel="noopener">
                                <i data-lucide="printer" style="width:15px;height:15px;"></i> Imprimir 80mm
                            </a>
                            <a class="btn btn-ghost" href="/sportsbook/ticket/<?= (int) $slip['id'] ?>/pdf">
                                <i data-lucide="file-down" style="width:15px;height:15px;"></i> PDF 80mm
                            </a>
                            
                            <?php if ($slip['status'] === 'pending'): ?>
                                <div x-data="cashOutComponent(<?= $slip['id'] ?>)" x-init="startPolling()" style="display:inline-block;">
                                    <template x-if="value > 0">
                                        <button @click="doCashOut()" class="btn btn-primary" style="background:var(--cyan); border-color:var(--cyan);" :disabled="loading">
                                            <i data-lucide="coins" style="width:15px;height:15px;" :class="loading ? 'spin' : ''"></i>
                                            <span x-text="loading ? 'Procesando...' : 'Cash Out: ' + money(value)"></span>
                                        </button>
                                    </template>
                                </div>
                            <?php endif; ?>

                            <span class="status-badge <?= $statusClass($slip['status']) ?>"><?= esc($statusText($slip['status'])) ?></span>
                        </div>
                    </div>

                    <?php foreach ($ticket['selections'] as $sel): ?>
                        <div class="selection-item">
                            <div>
                                <div class="league-info"><?= esc($sel['league_name'] ?? '-') ?></div>
                                <div class="team-names"><?= esc($sel['home_team']) ?> vs <?= esc($sel['away_team']) ?></div>
                                <div class="market-info">
                                    <?= esc($sel['market_name']) ?>
                                    <?php if (!empty($sel['start_time'])): ?>
                                        - <?= date('d/m/Y H:i', strtotime($sel['start_time'])) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($sel['venue'])): ?>
                                        - <?= esc($sel['venue']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="odd-info">
                                <span class="status-badge <?= $statusClass($sel['status']) ?>" style="margin-right:0.4rem;"><?= esc($statusText($sel['status'])) ?></span>
                                <span class="odd-selection"><?= esc($sel['odd_name']) ?></span>
                                <span class="odd-value"><?= number_format((float) $sel['odd_at_bet_time'], 2) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="ticket-footer">
                        <div class="footer-item">
                            <span class="footer-label">Importe apostado</span>
                            <span class="footer-value"><?= $money($slip['stake']) ?></span>
                        </div>
                        <div class="footer-item">
                            <span class="footer-label">Cuota total</span>
                            <span class="footer-value"><?= number_format((float) $slip['total_odds'], 2) ?></span>
                        </div>
                        <div class="footer-item">
                            <span class="footer-label"><?= $slip['status'] === 'won' ? 'Premio pagado' : 'Pago potencial' ?></span>
                            <span class="footer-value" style="color:<?= $slip['status'] === 'won' ? 'var(--success)' : 'var(--cyan)' ?>;"><?= $money($slip['potential_payout']) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <nav class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a class="btn btn-ghost" href="<?= esc($buildUrl(['page' => $currentPage - 1])) ?>">Anterior</a>
                <?php endif; ?>
                <span>Pagina <?= (int) $currentPage ?> / <?= (int) $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a class="btn btn-ghost" href="<?= esc($buildUrl(['page' => $currentPage + 1])) ?>">Siguiente</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
        function cashOutComponent(slipId) {
            return {
                slipId: slipId,
                value: 0,
                loading: false,
                pollInterval: null,
                money: function(val) { return parseFloat(val).toFixed(2) + ' K'; },
                async checkQuote() {
                    try {
                        const res = await fetch('/sportsbook/cashout/quote/' + this.slipId);
                        const data = await res.json();
                        if (data.status === 'success' && data.value > 0) {
                            this.value = data.value;
                        } else {
                            this.value = 0;
                        }
                    } catch(e) {
                        // silent fail
                    }
                },
                startPolling() {
                    this.checkQuote();
                    this.pollInterval = setInterval(() => this.checkQuote(), 8000);
                },
                async doCashOut() {
                    if(!confirm('¿Estás seguro de cerrar esta apuesta por ' + this.money(this.value) + '?')) return;
                    this.loading = true;
                    try {
                        const res = await fetch('/sportsbook/cashout/' + this.slipId, { method: 'POST' });
                        const data = await res.json();
                        if(data.status === 'success') {
                            alert('Cash Out procesado. Nuevo saldo: ' + this.money(data.new_balance));
                            window.location.reload();
                        } else {
                            alert(data.message);
                            this.loading = false;
                        }
                    } catch(e) {
                        alert('Error de conexión.');
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
