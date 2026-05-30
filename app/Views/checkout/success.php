<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acreditación Exitosa - Codex SS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-dark: #0b0f19;
            --bg-panel: #151c2c;
            --primary: #f97316;
            --primary-hover: #ea580c;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --success: #10b981;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-image:
                radial-gradient(ellipse at top right, rgba(249, 115, 22, 0.07), transparent 45%),
                radial-gradient(ellipse at bottom left, rgba(16, 185, 129, 0.05), transparent 45%);
            padding: 1.5rem;
        }

        .success-panel {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            text-align: center;
            max-width: 480px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.75rem;
            backdrop-filter: blur(12px);
        }

        .success-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.12);
            border: 2px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-size: 3rem;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
            animation: pulse-success 2s infinite, bounceIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes pulse-success {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
        }
        h1 span {
            color: var(--success);
        }

        .amount-display {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 1rem 2rem;
            font-weight: 800;
            font-size: 1.8rem;
            color: #a7f3d0;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.02em;
            margin: 0.5rem 0;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }

        .details-list {
            width: 100%;
            background: rgba(11, 15, 25, 0.4);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        .detail-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .detail-value {
            color: var(--text-main);
            font-weight: 700;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .status-badge.success {
            background: rgba(16, 185, 129, 0.15);
            color: #a7f3d0;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-badge.pending {
            background: rgba(249, 115, 22, 0.15);
            color: #ffedd5;
            border: 1px solid rgba(249, 115, 22, 0.3);
        }

        .btn-home {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.2);
            width: 100%;
            justify-content: center;
        }
        .btn-home:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.3);
        }

        .countdown-text {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .countdown-text span {
            color: var(--primary);
            font-weight: 700;
        }
    </style>
</head>
<body>

    <div class="success-panel">
        
        <?php if ($status === 'approved'): ?>
            <div class="success-icon">
                <i data-lucide="check" style="width: 48px; height: 48px;"></i>
            </div>
            <h1>¡Depósito <span>Aprobado</span>!</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.4;">
                Los créditos han sido acreditados de forma inmediata y segura en tu cuenta de Codex SS.
            </p>
            <div class="amount-display">
                <?= number_format($amount * 0.90, 2, ',', '.') ?> K
            </div>
        <?php else: ?>
            <div class="success-icon" style="background: rgba(249, 115, 22, 0.12); border-color: var(--primary); color: var(--primary); box-shadow: 0 0 20px rgba(249, 115, 22, 0.2); animation: none;">
                <i data-lucide="clock" style="width: 48px; height: 48px;"></i>
            </div>
            <h1>Transacción en <span>Proceso</span></h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.4;">
                El pago está siendo procesado. El saldo se acreditará automáticamente una vez confirmado.
            </p>
            <div class="amount-display" style="color: #ffedd5; background: rgba(249,115,22,0.08); border-color: rgba(249,115,22,0.2);">
                <?= number_format($amount * 0.90, 2, ',', '.') ?> K
            </div>
        <?php endif; ?>

        <div class="details-list">
            <div class="detail-row">
                <span class="detail-label">Carga Bruta</span>
                <span class="detail-value"><?= number_format($amount, 2, ',', '.') ?> K</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Comisión Interna (10%)</span>
                <span class="detail-value" style="color: var(--danger); font-weight: 600;">- <?= number_format($amount * 0.10, 2, ',', '.') ?> K</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Acreditado</span>
                <span class="detail-value" style="color: var(--success); font-weight: 800;"><?= number_format($amount * 0.90, 2, ',', '.') ?> K</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">ID de Operación</span>
                <span class="detail-value"><?= esc($payment_id ?: 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Estado</span>
                <span class="status-badge <?= $status === 'approved' ? 'success' : 'pending' ?>">
                    <?= $status === 'approved' ? 'Aprobado' : 'Pendiente' ?>
                </span>
            </div>
        </div>

        <a href="/" class="btn-home">
            <i data-lucide="home" style="width: 18px; height: 18px;"></i>
            Volver a la Página Principal
        </a>

        <div class="countdown-text">
            Redireccionando en <span id="countdown">5</span> segundos...
        </div>

    </div>

    <script>
        lucide.createIcons();
        
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '/';
            }
        }, 1000);
    </script>
</body>
</html>
