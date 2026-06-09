<?php
    $transactions = $transactions ?? [];
    $selectedType = $selectedType ?? 'all';
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $totalTransactions = $totalTransactions ?? count($transactions);

    $money = static fn ($value): string => number_format(abs((float) $value), 2) . ' K';
    
    $typeLabel = static fn (string $type): string => match ($type) {
        'deposit' => 'Depósito',
        'withdrawal' => 'Retiro',
        'bet_placed' => 'Apuesta Realizada',
        'bet_won' => 'Apuesta Ganada',
        'bet_void' => 'Apuesta Anulada',
        'cashout' => 'Cierre Anticipado (Cash Out)',
        default => ucfirst(str_replace('_', ' ', $type)),
    };

    $typeColor = static fn (string $type): string => match ($type) {
        'deposit', 'bet_won' => '#22c55e', // green
        'withdrawal', 'bet_placed' => '#ef4444', // red
        'cashout' => '#06b6d4', // cyan
        default => '#94a3b8', // slate/gray
    };

    $buildUrl = static function (array $overrides = []) use ($selectedType): string {
        $params = array_merge(['type' => $selectedType], $overrides);
        $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
        return '/sportsbook/transactions' . ($params ? '?' . http_build_query($params) : '');
    };
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Mis Transacciones') ?></title>
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
        .container { max-width: 1000px; margin: 1.5rem auto 3rem; padding: 0 1rem; }
        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
        .page-title { font-family: Outfit, sans-serif; font-size: 2rem; font-weight: 900; margin: 0; }
        .page-subtitle { color: var(--text-muted); margin: 0.25rem 0 0; }
        .filters { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.85rem; display: flex; gap: 0.65rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; }
        .tabs { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .tab { color: var(--text-muted); border: 1px solid var(--border); border-radius: 999px; padding: 0.45rem 0.75rem; text-decoration: none; font-size: 0.8rem; font-weight: 900; }
        .tab.active { background: rgba(249, 115, 22, 0.14); color: #fff; border-color: rgba(249, 115, 22, 0.45); }
        .table-card { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.8rem; padding: 0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
        th { background: rgba(0, 0, 0, 0.15); padding: 1rem; color: var(--text-muted); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255, 255, 255, 0.015); }
        .type-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.74rem; font-weight: 800; text-transform: uppercase; }
        .amount-col { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.05rem; }
        .amount-positive { color: var(--success); }
        .amount-negative { color: #fca5a5; }
        .balance-col { color: var(--text-secondary); font-weight: 600; }
        .date-col { color: var(--text-muted); font-size: 0.82rem; }
        .empty { text-align: center; padding: 4rem 1rem; color: var(--text-muted); background: var(--bg-panel); border: 1px solid var(--border); border-radius: 0.8rem; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.75rem; margin-top: 1.5rem; color: var(--text-muted); font-weight: 800; }
        
        @media (max-width: 760px) {
            .topbar { align-items: flex-start; flex-direction: column; height: auto !important; padding: 0.85rem 1.25rem !important; gap: 0.5rem; }
            .user-nav { justify-content: flex-start; width: 100%; }
            .page-head, .filters { align-items: flex-start; flex-direction: column; }
            table, thead, tbody, th, td, tr { display: block; }
            th { display: none; }
            td { position: relative; padding-left: 45%; border-bottom: 1px solid var(--border); text-align: right; }
            td:last-child { border-bottom: 1px solid var(--border); }
            tr { border-bottom: 2px solid var(--border); padding: 0.5rem 0; }
            td::before { content: attr(data-label); position: absolute; left: 1rem; width: 40%; text-align: left; font-weight: 700; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/" class="logo">Codex SS</a>
        <div class="user-nav">
            <span class="pill"><i data-lucide="wallet" style="width:16px;height:16px;color:var(--primary);"></i><?= number_format($walletBalance, 2) . ' K' ?></span>
            <span class="pill"><i data-lucide="user" style="width:16px;height:16px;color:var(--primary);"></i><?= esc(session()->get('username')) ?></span>
            <a href="/" class="btn btn-primary">Volver al Sportsbook</a>
        </div>
    </header>

    <main class="container">
        <div class="page-head">
            <div>
                <h1 class="page-title">Historial de Transacciones</h1>
                <p class="page-subtitle">Revisa todos los movimientos y operaciones en tu cuenta.</p>
            </div>
            <div class="pill"><?= (int) $totalTransactions ?> transacciones registradas</div>
        </div>

        <section class="filters">
            <nav class="tabs">
                <?php 
                    $tabOptions = [
                        'all' => 'Todas', 
                        'deposit' => 'Depósitos', 
                        'withdrawal' => 'Retiros', 
                        'bet_placed' => 'Apuestas', 
                        'bet_won' => 'Premios',
                        'cashout' => 'Cierres (Cash Out)'
                    ];
                ?>
                <?php foreach ($tabOptions as $value => $label): ?>
                    <a class="tab <?= $selectedType === $value ? 'active' : '' ?>" href="<?= esc($buildUrl(['type' => $value, 'page' => 1])) ?>"><?= esc($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </section>

        <?php if (empty($transactions)): ?>
            <section class="empty">
                <i data-lucide="receipt" style="width:48px;height:48px;margin-bottom:1rem;opacity:0.55;"></i>
                <h3>No hay transacciones registradas para este filtro.</h3>
                <p>Realiza depósitos u opera en el sportsbook para ver movimientos aquí.</p>
                <a href="/" class="btn btn-primary" style="margin-top:0.75rem;">Ver eventos para apostar</a>
            </section>
        <?php else: ?>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Cuenta Destino / Detalle</th>
                            <th style="text-align: right;">Importe</th>
                            <th style="text-align: right;">Saldo Resultante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                                $isPositive = (float)$tx['amount'] >= 0;
                                $amountText = ($isPositive ? '+' : '-') . $money($tx['amount']);
                                $amountClass = $isPositive ? 'amount-positive' : 'amount-negative';
                            ?>
                            <tr>
                                <td data-label="Fecha" class="date-col">
                                    <?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?>
                                </td>
                                <td data-label="Tipo">
                                    <span class="type-badge" style="background: <?= $typeColor($tx['type']) ?>18; color: <?= $typeColor($tx['type']) ?>; border: 1px solid <?= $typeColor($tx['type']) ?>33;">
                                        <?= esc($typeLabel($tx['type'])) ?>
                                    </span>
                                </td>
                                <td data-label="Descripción" style="font-weight: 500;">
                                    <?= esc($tx['description']) ?>
                                    <?php if ($tx['reference_id'] && str_contains($tx['type'], 'bet')): ?>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.15rem;">Ticket #<?= str_pad($tx['reference_id'], 6, '0', STR_PAD_LEFT) ?></span>
                                    <?php elseif ($tx['reference_id'] && $tx['type'] === 'withdrawal'): ?>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.15rem;">Retiro #<?= str_pad($tx['reference_id'], 6, '0', STR_PAD_LEFT) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Detalle" style="color: var(--text-muted); font-size: 0.82rem;">
                                    <?= esc($tx['target_account'] ?? '-') ?>
                                    <?php if ((float)($tx['commission'] ?? 0) > 0): ?>
                                        <span style="font-size: 0.72rem; color: var(--danger); display: block; margin-top: 0.1rem;">Comisión: <?= number_format($tx['commission'], 2) ?> K</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Importe" class="amount-col <?= $amountClass ?>" style="text-align: right;">
                                    <?= $amountText ?>
                                </td>
                                <td data-label="Saldo Resultante" class="balance-col" style="text-align: right;">
                                    <?= number_format((float) $tx['balance_after'], 2) . ' K' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a class="btn btn-ghost" href="<?= esc($buildUrl(['page' => $currentPage - 1])) ?>">Anterior</a>
                <?php endif; ?>
                <span>Página <?= (int) $currentPage ?> / <?= (int) $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a class="btn btn-ghost" href="<?= esc($buildUrl(['page' => $currentPage + 1])) ?>">Siguiente</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
