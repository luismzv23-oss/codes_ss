<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }

        * { box-sizing: border-box; }

        body {
            background: <?= !empty($pdfMode) ? '#fff' : '#f3f4f6' ?>;
            color: #000;
            font-family: "Courier New", monospace;
            font-size: 11px;
            margin: 0;
            padding: 14px;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 12px;
        }

        .actions button,
        .actions a {
            background: #111827;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 8px 12px;
            text-decoration: none;
        }

        .ticket {
            background: #fff;
            margin: 0 auto;
            padding: 4mm;
            width: 80mm;
        }

        .center { text-align: center; }
        .brand { font-size: 18px; font-weight: 900; letter-spacing: 0.5px; }
        .muted { color: #333; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .bold { font-weight: 900; }
        .small { font-size: 10px; }
        .selection { margin: 8px 0; }
        .teams { font-weight: 900; line-height: 1.25; }
        .status { text-transform: uppercase; }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .ticket {
                margin: 0;
                padding: 0;
                width: 74mm;
            }
        }
    </style>
</head>
<body>
    <?php if (empty($pdfMode)): ?>
        <div class="actions">
            <button onclick="window.print()">Imprimir</button>
            <a href="<?= !empty($adminMode) ? '/dashboard/bets/ticket/' . $slip['id'] . '/pdf' : '/sportsbook/ticket/' . $slip['id'] . '/pdf' ?>">Descargar PDF</a>
            <a href="<?= !empty($adminMode) ? '/dashboard/bets' : '/sportsbook/history' ?>">Volver</a>
        </div>
    <?php endif; ?>

    <main class="ticket">
        <div class="center">
            <div class="brand">CODEX SS</div>
            <div class="small">Ticket de Apuesta</div>
            <?php if (!empty($adminMode)): ?>
                <div class="small">Copia administrativa</div>
            <?php endif; ?>
            <div class="small"><?= date('d/m/Y H:i', strtotime($slip['created_at'])) ?></div>
        </div>

        <div class="line"></div>

        <div class="row">
            <span>Ticket</span>
            <span class="bold">#<?= str_pad($slip['id'], 6, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="row">
            <span>Usuario</span>
            <span><?= esc($username) ?></span>
        </div>
        <div class="row">
            <span>Estado</span>
            <span class="bold status"><?= esc($slip['status']) ?></span>
        </div>
        <div class="row">
            <span>Tipo</span>
            <span><?= count($selections) > 1 ? 'Combinada' : 'Simple' ?></span>
        </div>

        <div class="line"></div>

        <?php foreach ($selections as $index => $sel): ?>
            <section class="selection">
                <div class="small bold">SEL <?= $index + 1 ?> / <?= esc($sel['league_name']) ?></div>
                <div class="teams"><?= esc($sel['home_team']) ?> vs <?= esc($sel['away_team']) ?></div>
                <div class="small"><?= date('d/m/Y H:i', strtotime($sel['start_time'])) ?></div>
                <?php if (!empty($sel['venue'])): ?>
                    <div class="small"><?= esc($sel['venue']) ?></div>
                <?php endif; ?>
                <div class="row">
                    <span><?= esc($sel['market_name']) ?></span>
                    <span><?= esc($sel['status']) ?></span>
                </div>
                <div class="row bold">
                    <span><?= esc($sel['odd_name']) ?></span>
                    <span><?= number_format($sel['odd_at_bet_time'], 2) ?></span>
                </div>
            </section>
            <div class="line"></div>
        <?php endforeach; ?>

        <div class="row">
            <span>Importe</span>
            <span class="bold"><?= number_format($slip['stake'], 2) ?> K</span>
        </div>
        <div class="row">
            <span>Cuota total</span>
            <span class="bold"><?= number_format($slip['total_odds'], 2) ?></span>
        </div>
        <div class="row">
            <span>Pago potencial</span>
            <span class="bold"><?= number_format($slip['potential_payout'], 2) ?> K</span>
        </div>

        <div class="line"></div>

        <div class="center small">
            Conserve este comprobante.<br>
            Juego responsable.
        </div>
    </main>
</body>
</html>
