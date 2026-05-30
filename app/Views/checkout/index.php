<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasarela de Pago Segura - Codex SS</title>
    <meta name="description" content="Portal de Pago Seguro - MercadoPago & DEBIN Integrados">

    <!-- Fonts & Libraries -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap"
        rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>

    <style>
        :root {
            --bg-dark: #0b0f19;
            --bg-panel: #151c2c;
            --primary: #f97316;
            /* Betsson orange */
            --primary-hover: #ea580c;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --success: #10b981;
            --danger: #ef4444;
            --mp-blue: #009ee3;
            --debin-green: #00a884;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image:
                radial-gradient(ellipse at top right, rgba(249, 115, 22, 0.07), transparent 45%),
                radial-gradient(ellipse at bottom left, rgba(0, 158, 227, 0.05), transparent 45%);
        }

        /* Header styling */
        header {
            height: 70px;
            background: rgba(21, 28, 44, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary), #fb923c);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: white;
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .logo-text span {
            color: var(--primary);
        }

        .header-amount {
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.2);
            border-radius: 999px;
            padding: 0.5rem 1.25rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: #ffedd5;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Container Layout */
        main {
            flex: 1;
            max-width: 1100px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }

        @media (max-width: 868px) {
            main {
                grid-template-columns: 1fr;
                margin: 1rem auto;
            }
        }

        /* Left Side: Methods Menu */
        .methods-sidebar {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .method-tab {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .method-tab:hover {
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .method-tab.active {
            border-color: var(--primary);
            background: rgba(249, 115, 22, 0.04);
            box-shadow: 0 4px 20px rgba(249, 115, 22, 0.05);
        }

        .method-tab.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary);
        }

        .method-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        .method-tab.active .method-icon {
            background: rgba(249, 115, 22, 0.15);
            border-color: rgba(249, 115, 22, 0.3);
            color: var(--primary);
        }

        .method-info h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.15rem;
            color: var(--text-main);
        }

        .method-info p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Right Side: Active Workspace Card */
        .workspace-panel {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            min-height: 480px;
            position: relative;
        }

        /* Forms Layout & Common inputs */
        .form-group {
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        input,
        select {
            background: rgba(11, 15, 25, 0.5);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.85rem 1rem;
            color: var(--text-main);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
            background: rgba(11, 15, 25, 0.8);
        }

        /* Animated Credit Card */
        .card-container {
            perspective: 1000px;
            margin: 0.5rem auto 2rem;
            width: 100%;
            max-width: 350px;
            height: 200px;
        }

        .card-inner {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-inner.flipped {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
        }

        .card-face::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .card-front {
            /* Front side styles */
        }

        .card-back {
            transform: rotateY(180deg);
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-chip {
            width: 40px;
            height: 30px;
            background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
            border-radius: 6px;
            position: relative;
        }

        .card-brand {
            font-size: 1.25rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            font-style: italic;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-number-display {
            font-family: 'Outfit', monospace;
            font-size: 1.35rem;
            letter-spacing: 0.08em;
            color: #f8fafc;
            margin: 1rem 0;
            text-align: center;
        }

        .card-holder-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .card-holder-name {
            font-weight: 600;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-holder-expiry {
            font-weight: 600;
        }

        .card-magnetic-strip {
            height: 35px;
            background: #000;
            width: 100%;
        }

        .card-signature-cvv {
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-signature-strip {
            flex: 1;
            height: 30px;
            background: #e2e8f0;
            border-radius: 4px;
            margin-right: 1rem;
        }

        .card-cvv-display {
            background: #fff;
            color: #000;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.85rem;
            width: 40px;
            text-align: center;
        }

        /* QR Code Layout */
        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 1.5rem;
            padding: 1rem 0;
        }

        .qr-frame {
            padding: 1.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            display: inline-block;
            position: relative;
        }

        .qr-img {
            width: 180px;
            height: 180px;
            display: block;
        }

        .qr-logo-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: #009ee3;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            color: white;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
        }

        .qr-countdown {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pulse-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-ring 1.5s infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* DEBIN bank grid */
        .bank-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .bank-btn {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .bank-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
            color: var(--text-main);
        }

        .bank-btn.selected {
            background: rgba(0, 168, 132, 0.1);
            border-color: var(--debin-green);
            color: #a7f3d0;
            box-shadow: 0 0 10px rgba(0, 168, 132, 0.1);
        }

        /* Custom buttons */
        .btn-confirm {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.2);
            margin-top: auto;
        }

        .btn-confirm:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.3);
        }

        .btn-confirm:disabled {
            background: #475569;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* Overlay Spinner / Processing */
        .processing-overlay {
            position: absolute;
            inset: 0;
            background: rgba(21, 28, 44, 0.95);
            border-radius: 20px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            text-align: center;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .processing-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Success screen */
        .success-checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }

            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }

            70% {
                transform: scale(0.9);
                opacity: 0.9;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Cancel return link */
        .return-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .return-link:hover {
            color: var(--text-main);
        }
    </style>
</head>

<body x-data="checkoutApp()">

    <!-- Header -->
    <header>
        <div class="logo-section">
            <div class="logo-icon">CS</div>
            <div class="logo-text">Codex<span>SS</span></div>
        </div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <a href="/" class="return-link">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Volver a Apuestas
            </a>
            <div class="header-amount">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Total
                    Carga:</span>
                <?= number_format($amount, 2, ',', '.') ?> K
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <main>
        <!-- Left panel: payment methods tabs -->
        <section class="methods-sidebar">

            <!--
            <div class="method-tab" :class="{ 'active': activeMethod === 'mp_card' }" @click="activeMethod = 'mp_card'">
                <div class="method-icon"><i data-lucide="credit-card"></i></div>
                <div class="method-info">
                    <h3>Mercado Pago</h3>
                    <p>Tarjeta de Crédito / Débito</p>
                </div>
            </div>
            -->

            <?php $hasMpAccount = !empty($mpAccount); ?>
            <?php if ($hasMpAccount): ?>
                <div class="method-tab" :class="{ 'active': activeMethod === 'mp_qr' }" @click="activeMethod = 'mp_qr'">
                    <div class="method-icon"><i data-lucide="qr-code"></i></div>
                    <div class="method-info">
                        <h3>Mercado Pago QR</h3>
                        <p>Escaneo rápido & Pago instantáneo</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="method-tab disabled" style="opacity: 0.5; cursor: not-allowed;"
                    title="Método no configurado por el administrador">
                    <div class="method-icon"><i data-lucide="qr-code" style="color: var(--text-muted);"></i></div>
                    <div class="method-info">
                        <h3>Mercado Pago QR <span
                                style="font-size: 0.65rem; color: var(--danger); font-weight: 700; text-transform: uppercase; margin-left: 0.25rem;">(Inactivo)</span>
                        </h3>
                        <p>Cuenta no configurada</p>
                    </div>
                </div>
            <?php endif; ?>
            <!--
            <div class="method-tab" :class="{ 'active': activeMethod === 'debin' }" @click="activeMethod = 'debin'">
                <div class="method-icon"><i data-lucide="landmark"></i></div>
                <div class="method-info">
                    <h3>DEBIN</h3>
                    <p>Débito inmediato en cuenta</p>
                </div>
            </div>
            -->
        </section>

        <!-- Right panel: Active workspace content -->
        <section class="workspace-panel">

            <!-- PROCESSING OVERLAY -->
            <div class="processing-overlay" x-show="paymentState === 'processing'" style="display: none;">
                <div class="processing-spinner"></div>
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Procesando Pago</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Conectando de forma segura con los
                        servidores de pago...</p>
                </div>
            </div>

            <!-- SUCCESS OVERLAY -->
            <div class="processing-overlay" x-show="paymentState === 'success'" style="display: none;">
                <div class="success-checkmark">
                    <i data-lucide="check"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--success); margin-bottom: 0.5rem;">
                        ¡Acreditación Exitosa!</h2>
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 0.5rem;">
                        Se han acreditado <strong><?= number_format($amount * 0.90, 2, ',', '.') ?> K</strong> en tu
                        cuenta.
                    </p>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">(Comisión del 10%
                        descontada)</div>
                    <p style="font-size: 0.8rem; color: var(--primary);">Redireccionando a apuestas en 3 segundos...</p>
                </div>
            </div>

            <!-- TAB 1: MERCADO PAGO CARD -->
            <div x-show="activeMethod === 'mp_card'" style="display: flex; flex-direction: column; flex: 1;">
                <h2
                    style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="credit-card" style="color: var(--mp-blue);"></i>
                    Pagar con Mercado Pago Tarjeta
                </h2>

                <!-- Real Card Payment Brick Container -->
                <div x-show="isRealMpCard" id="cardPaymentBrick_container" style="margin-bottom: 1.5rem; width: 100%;">
                </div>

                <!-- Fallback / Simulated Form -->
                <div x-show="!isRealMpCard" style="display: flex; flex-direction: column; flex: 1;">
                    <!-- 3D Card Display -->
                    <div class="card-container">
                        <div class="card-inner" :class="{ 'flipped': focusedField === 'cvv' }">
                            <!-- Front Face -->
                            <div class="card-face card-front">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div class="card-chip"></div>
                                    <div class="card-brand">
                                        <i data-lucide="wallet" style="width:16px;height:16px;"></i>
                                        <span x-text="detectCardBrand()">MercadoPago</span>
                                    </div>
                                </div>
                                <div class="card-number-display" x-text="cardNumberFormatted || '•••• •••• •••• ••••'">
                                    •••• •••• •••• ••••</div>
                                <div class="card-holder-info">
                                    <div>
                                        <div style="color: var(--text-muted); font-size: 0.6rem; margin-bottom: 2px;">
                                            Titular de Tarjeta</div>
                                        <div class="card-holder-name" x-text="cardName || 'JUAN PEREZ'">JUAN PEREZ</div>
                                    </div>
                                    <div>
                                        <div style="color: var(--text-muted); font-size: 0.6rem; margin-bottom: 2px;">
                                            Vence</div>
                                        <div class="card-holder-expiry" x-text="cardExpiry || 'MM/AA'">MM/AA</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Back Face -->
                            <div class="card-face card-back">
                                <div class="card-magnetic-strip"></div>
                                <div class="card-signature-cvv">
                                    <div class="card-signature-strip"></div>
                                    <div class="card-cvv-display" x-text="cardCvv || '•••'">•••</div>
                                </div>
                                <div
                                    style="padding: 0 1.5rem; font-size: 0.5rem; color: var(--text-muted); text-align: center; text-transform: uppercase;">
                                    Firma del titular autorizada para operar en línea.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Inputs -->
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div class="form-group">
                            <label>Número de Tarjeta</label>
                            <input type="text" maxlength="19" placeholder="4517 0000 0000 0000" x-model="cardNumber"
                                @input="formatCardNumber" @focus="focusedField = 'number'" />
                        </div>

                        <div class="form-group">
                            <label>Nombre en la Tarjeta</label>
                            <input type="text" placeholder="Ej. Juan Pérez" x-model="cardName"
                                @focus="focusedField = 'name'" />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Fecha de Vencimiento</label>
                                <input type="text" maxlength="5" placeholder="MM/AA" x-model="cardExpiry"
                                    @input="formatExpiry" @focus="focusedField = 'expiry'" />
                            </div>
                            <div class="form-group">
                                <label>Código de Seguridad (CVV)</label>
                                <input type="password" maxlength="4" placeholder="123" x-model="cardCvv"
                                    @focus="focusedField = 'cvv'" @blur="focusedField = null" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Cuotas</label>
                            <select>
                                <option>1 cuota de <?= number_format($amount, 2, ',', '.') ?> K (Sin Interés)</option>
                                <option>3 cuotas de <?= number_format($amount / 3 * 1.1, 2, ',', '.') ?> K (Con Interés)
                                </option>
                                <option>6 cuotas de <?= number_format($amount / 6 * 1.25, 2, ',', '.') ?> K (Con
                                    Interés)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Breakdown Summary Box -->
                    <div
                        style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 10px; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.8rem; display: flex; flex-direction: column; gap: 0.35rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Carga Bruta:</span>
                            <span style="font-weight: 600;"><?= number_format($amount, 2, ',', '.') ?> K</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Comisión (10%):</span>
                            <span style="color: var(--danger);">-<?= number_format($amount * 0.10, 2, ',', '.') ?>
                                K</span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 0.35rem; font-weight: 800;">
                            <span>Créditos a recibir:</span>
                            <span style="color: var(--success);"><?= number_format($amount * 0.90, 2, ',', '.') ?>
                                K</span>
                        </div>
                    </div>

                    <button class="btn-confirm" :disabled="!validateCardForm()"
                        @click="submitPayment('Mercado Pago Tarjeta')">
                        <i data-lucide="shield-check"></i>
                        Pagar <?= number_format($amount, 2, ',', '.') ?> K
                    </button>
                </div>
            </div>

            <!-- TAB 2: MERCADO PAGO QR -->
            <div x-show="activeMethod === 'mp_qr'" style="display: none; flex-direction: column; flex: 1;">
                <h2
                    style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="qr-code" style="color: var(--mp-blue);"></i>
                    Pagar con QR Mercado Pago
                </h2>

                <?php if ($hasMpAccount): ?>
                    <div class="qr-layout"
                        style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 2rem; margin-top: 1rem; align-items: start;">
                        <!-- Left Column: Scan & QR -->
                        <div
                            style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem;">
                            <p style="font-size: 0.8rem; color: var(--text-muted); max-width: 280px; line-height: 1.4;">
                                Escanea el código QR desde la aplicación de Mercado Pago o tu banco adherido para realizar
                                el pago.
                            </p>

                            <!-- QR Frame -->
                            <div class="qr-frame"
                                style="padding: 1rem; background: white; border-radius: 12px; position: relative; display: inline-block;">
                                <img src="<?= $qrBase64 ?>" class="qr-img"
                                    style="object-fit: contain; width: 150px; height: 150px; display: block;"
                                    alt="Código QR de Pago">
                            </div>

                            <div class="qr-countdown"
                                style="font-size: 0.95rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                                <div class="pulse-indicator"></div>
                                <span>Esperando confirmación: <span x-text="formatTime(qrTimer)">05:00</span></span>
                            </div>
                        </div>

                        <!-- Right Column: Details & Simulation -->
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <!-- Recipient Details -->
                            <div
                                style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                                <span
                                    style="font-size: 0.65rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Cuenta
                                    de Destino (Mercado Pago)</span>
                                <span
                                    style="font-size: 0.95rem; font-weight: 700; color: #ffedd5;"><?= esc($mpAccount) ?></span>
                            </div>

                            <?php if ($isRealMp): ?>
                                <div
                                    style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; background: rgba(0, 158, 227, 0.05); border: 1px solid rgba(0, 158, 227, 0.15); padding: 0.75rem 1rem; border-radius: 12px;">
                                    ¿No tienes lector QR? Puedes pagar directamente usando este <a
                                        href="<?= esc($mpPreferenceUrl) ?>" target="_blank"
                                        style="color: var(--primary); text-decoration: underline; font-weight: 700;">Enlace de
                                        Pago Oficial</a>.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div
                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; text-align: center; gap: 1.5rem; padding: 2rem;">
                        <div
                            style="width: 64px; height: 64px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; color: var(--danger);">
                            <i data-lucide="alert-triangle" style="width: 32px; height: 32px;"></i>
                        </div>
                        <div>
                            <h3
                                style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">
                                Método No Habilitado</h3>
                            <p style="font-size: 0.85rem; color: var(--text-muted); max-width: 400px; line-height: 1.5;">
                                El método de pago por QR de Mercado Pago no está habilitado porque el administrador no ha
                                configurado la cuenta receptora.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: DEBIN -->
            <div x-show="activeMethod === 'debin'" style="display: none; flex-direction: column; flex: 1;">
                <h2
                    style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="landmark" style="color: var(--debin-green);"></i>
                    Pagar con DEBIN o Transferencia Bancaria
                </h2>

                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.4;">
                    Seleccione su entidad bancaria, introduzca su CBU o Alias de cuenta y autorice el débito inmediato
                    desde su Homebanking.
                </p>

                <!-- Bank Transfer Details Card -->
                <div
                    style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div
                        style="display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 8px; background: rgba(0, 168, 132, 0.15); display: flex; align-items: center; justify-content: center; color: var(--debin-green);">
                            <i data-lucide="info" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);">Datos de
                                Transferencia Bancaria Directa</h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted);">Transfiere el monto exacto y tu
                                saldo se acreditará al confirmar.</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span
                                style="font-size: 0.65rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Banco
                                / Entidad</span>
                            <span
                                style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);"><?= esc($settings['bank_name'] ?? 'Banco Galicia') ?></span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span
                                style="font-size: 0.65rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Titular
                                de la Cuenta</span>
                            <span
                                style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);"><?= esc($settings['bank_holder'] ?? 'Codex SS S.A.') ?></span>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.25rem;">
                        <!-- CBU Row -->
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; background: rgba(11, 15, 25, 0.4); padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border);">
                            <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                <span
                                    style="font-size: 0.6rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">CBU
                                    / CVU</span>
                                <span
                                    style="font-size: 0.85rem; font-family: monospace; font-weight: 600; color: var(--text-main); letter-spacing: 0.02em;"><?= esc($settings['bank_cbu_cvu'] ?? '0070001230004567891234') ?></span>
                            </div>
                            <button type="button"
                                @click="copyToClipboard('<?= esc($settings['bank_cbu_cvu'] ?? '0070001230004567891234') ?>', 'cbu')"
                                class="bank-btn"
                                style="padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.75rem; margin: 0; background: rgba(255,255,255,0.05); color: var(--text-main);"
                                :style="copiedField === 'cbu' ? 'background: rgba(16, 185, 129, 0.15); border-color: var(--success); color: #a7f3d0;' : ''">
                                <span style="display: flex; align-items: center; gap: 0.25rem;">
                                    <template x-if="copiedField === 'cbu'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5" />
                                        </svg>
                                    </template>
                                    <template x-if="copiedField !== 'cbu'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                                        </svg>
                                    </template>
                                    <span x-text="copiedField === 'cbu' ? 'Copiado' : 'Copiar'">Copiar</span>
                                </span>
                            </button>
                        </div>

                        <!-- Alias Row -->
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; background: rgba(11, 15, 25, 0.4); padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border);">
                            <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                <span
                                    style="font-size: 0.6rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Alias
                                    de Cuenta</span>
                                <span
                                    style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);"><?= esc($settings['bank_alias'] ?? 'codex.ss.transfer') ?></span>
                            </div>
                            <button type="button"
                                @click="copyToClipboard('<?= esc($settings['bank_alias'] ?? 'codex.ss.transfer') ?>', 'alias')"
                                class="bank-btn"
                                style="padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.75rem; margin: 0; background: rgba(255,255,255,0.05); color: var(--text-main);"
                                :style="copiedField === 'alias' ? 'background: rgba(16, 185, 129, 0.15); border-color: var(--success); color: #a7f3d0;' : ''">
                                <span style="display: flex; align-items: center; gap: 0.25rem;">
                                    <template x-if="copiedField === 'alias'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5" />
                                        </svg>
                                    </template>
                                    <template x-if="copiedField !== 'alias'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                                        </svg>
                                    </template>
                                    <span x-text="copiedField === 'alias' ? 'Copiado' : 'Copiar'">Copiar</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Breakdown Summary Box -->
                    <div
                        style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 10px; padding: 0.75rem; margin-top: 0.5rem; margin-bottom: 0.5rem; font-size: 0.8rem; display: flex; flex-direction: column; gap: 0.35rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Carga Bruta:</span>
                            <span style="font-weight: 600;"><?= number_format($amount, 2, ',', '.') ?> K</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Comisión (10%):</span>
                            <span style="color: var(--danger);">-<?= number_format($amount * 0.10, 2, ',', '.') ?>
                                K</span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 0.35rem; font-weight: 800;">
                            <span>Créditos a recibir:</span>
                            <span style="color: var(--success);"><?= number_format($amount * 0.90, 2, ',', '.') ?>
                                K</span>
                        </div>
                    </div>

                    <button class="btn-confirm"
                        style="margin-top: 0.5rem; background: linear-gradient(135deg, var(--debin-green), #059669); box-shadow: 0 4px 15px rgba(0, 168, 132, 0.2);"
                        @click="submitPayment('Transferencia Directa Bancaria')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-send">
                            <line x1="22" x2="11" y1="2" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>
                        Confirmar Transferencia Realizada
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0 1.25rem 0;">
                    <div style="flex: 1; height: 1px; background: var(--border);"></div>
                    <span
                        style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.1em;">Ó
                        Pagar Vía DEBIN</span>
                    <div style="flex: 1; height: 1px; background: var(--border);"></div>
                </div>

                <!-- Bank Selection Grid -->
                <label style="margin-bottom: 0.5rem; display: block;">Entidad Bancaria</label>
                <div class="bank-grid">
                    <button class="bank-btn" :class="{ 'selected': selectedBank === 'Galicia' }"
                        @click="selectedBank = 'Galicia'">
                        🏦 Galicia
                    </button>
                    <button class="bank-btn" :class="{ 'selected': selectedBank === 'Santander' }"
                        @click="selectedBank = 'Santander'">
                        🏦 Santander
                    </button>
                    <button class="bank-btn" :class="{ 'selected': selectedBank === 'BBVA' }"
                        @click="selectedBank = 'BBVA'">
                        🏦 BBVA
                    </button>
                    <button class="bank-btn" :class="{ 'selected': selectedBank === 'Macro' }"
                        @click="selectedBank = 'Macro'">
                        🏦 Macro
                    </button>
                    <button class="bank-btn" :class="{ 'selected': selectedBank === 'Nación' }"
                        @click="selectedBank = 'Nación'">
                        🏦 Nación
                    </button>
                    <button class="bank-btn" :class="{ 'selected': selectedBank === 'Brubank' }"
                        @click="selectedBank = 'Brubank'">
                        🏦 Brubank
                    </button>
                </div>

                <div class="form-group">
                    <label>Alias o CBU/CVU de Cuenta (22 dígitos)</label>
                    <input type="text" placeholder="Ej. alias.mercado.pago o 0070001230004567891234"
                        x-model="debinAccount" @input="validateDebinAccount" />
                    <span style="font-size: 0.7rem;"
                        :style="debinAccountValid ? 'color: var(--success);' : 'color: var(--text-muted);'"
                        x-text="debinAccountMessage">Alias o CBU no verificado</span>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>CUIT / CUIL del Titular</label>
                    <input type="text" maxlength="13" placeholder="Ej. 20-35678901-9" x-model="debinCuit"
                        @input="formatCuit" />
                </div>

                <!-- Breakdown Summary Box -->
                <div
                    style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 10px; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.8rem; display: flex; flex-direction: column; gap: 0.35rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Carga Bruta:</span>
                        <span style="font-weight: 600;"><?= number_format($amount, 2, ',', '.') ?> K</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Comisión (10%):</span>
                        <span style="color: var(--danger);">-<?= number_format($amount * 0.10, 2, ',', '.') ?> K</span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 0.35rem; font-weight: 800;">
                        <span>Créditos a recibir:</span>
                        <span style="color: var(--success);"><?= number_format($amount * 0.90, 2, ',', '.') ?> K</span>
                    </div>
                </div>

                <button class="btn-confirm"
                    style="background: var(--debin-green); box-shadow: 0 4px 15px rgba(0, 168, 132, 0.2);"
                    :disabled="!validateDebinForm()" @click="submitPayment('DEBIN ' + selectedBank)">
                    <i data-lucide="check-circle-2"></i>
                    Generar & Autorizar DEBIN
                </button>
            </div>

        </section>
    </main>

    <!-- Script Application Logic -->
    <script>
        function checkoutApp() {
            return {
                amount: <?= $amount ?>,
                activeMethod: 'mp_qr',
                paymentState: 'idle', // idle, processing, success

                // Real MP fields
                isRealMp: <?= $isRealMp ? 'true' : 'false' ?>,
                mpPreferenceId: '<?= esc($mpPreferenceId) ?>',
                mpPreferenceUrl: '<?= esc($mpPreferenceUrl) ?>',
                pollingInterval: null,
                isRealMpCard: <?= ($mpCardEnabled === '1' && !empty($mpPublicKey)) ? 'true' : 'false' ?>,
                mpPublicKey: '<?= esc($mpPublicKey) ?>',
                email: '<?= esc($email) ?>',
                mpInstance: null,
                cardBrickController: null,

                // Mercado Pago Card fields
                cardNumber: '',
                cardNumberFormatted: '',
                cardName: '',
                cardExpiry: '',
                cardCvv: '',
                focusedField: null,

                // Mercado Pago QR fields
                qrTimer: 300, // 5 minutes in seconds
                qrInterval: null,

                // DEBIN fields
                selectedBank: '',
                debinAccount: '',
                debinAccountValid: false,
                debinAccountMessage: 'Introduce tu CBU o Alias',
                debinCuit: '',
                copiedField: '',

                // CBU / Alias copy helper
                copyToClipboard(text, field) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.copiedField = field;
                        setTimeout(() => {
                            if (this.copiedField === field) {
                                this.copiedField = '';
                            }
                        }, 2000);
                    }).catch(err => {
                        console.error('Error copying text: ', err);
                    });
                },

                async init() {
                    lucide.createIcons();

                    // Start QR code countdown timer
                    this.qrInterval = setInterval(() => {
                        if (this.qrTimer > 0) {
                            this.qrTimer--;
                        } else {
                            this.qrTimer = 300; // Reset
                        }
                    }, 1000);

                    // Auto select first bank in list
                    this.selectedBank = 'Galicia';

                    // Start polling for real MP payment status
                    if (this.isRealMp && this.mpPreferenceId) {
                        this.startPolling();
                    }

                    if (this.isRealMpCard && this.mpPublicKey) {
                        await this.initCardBrick();
                    }
                },

                // Card brand detection
                detectCardBrand() {
                    const cleanNum = this.cardNumber.replace(/\D/g, '');
                    if (cleanNum.startsWith('4')) return 'Visa';
                    if (/^(5[1-5]|222[1-9]|22[3-9]|2[3-6]|27[0-1]|2720)/.test(cleanNum)) return 'Mastercard';
                    if (/^3[47]/.test(cleanNum)) return 'Amex';
                    return 'MercadoPago';
                },

                // Format card number with spaces (e.g. 1111 2222 3333 4444)
                formatCardNumber() {
                    let clean = this.cardNumber.replace(/\D/g, '');
                    let matches = clean.match(/\d{4,16}/g);
                    let match = (matches && matches[0]) || '';
                    let parts = [];

                    for (let i = 0, len = match.length; i < len; i += 4) {
                        parts.push(match.substring(i, i + 4));
                    }

                    if (parts.length > 0) {
                        this.cardNumberFormatted = parts.join(' ');
                    } else {
                        this.cardNumberFormatted = clean;
                    }
                    this.cardNumber = this.cardNumberFormatted;
                },

                // Format expiry field (e.g. MM/AA)
                formatExpiry() {
                    let clean = this.cardExpiry.replace(/\D/g, '');
                    if (clean.length >= 2) {
                        let mm = clean.substring(0, 2);
                        let yy = clean.substring(2, 4);

                        // MM constraint (01-12)
                        let mmNum = parseInt(mm);
                        if (mmNum > 12) mm = '12';
                        if (mmNum === 0) mm = '01';

                        this.cardExpiry = mm + '/' + yy;
                    } else {
                        this.cardExpiry = clean;
                    }
                },

                // Card Validation
                validateCardForm() {
                    const cleanNum = this.cardNumber.replace(/\D/g, '');
                    const cleanExpiry = this.cardExpiry.replace(/\D/g, '');
                    return cleanNum.length >= 15 &&
                        this.cardName.trim().length >= 4 &&
                        cleanExpiry.length === 4 &&
                        this.cardCvv.length >= 3;
                },

                // Format QR Timer
                formatTime(sec) {
                    const m = Math.floor(sec / 60).toString().padStart(2, '0');
                    const s = (sec % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                },

                // CBU / Alias verification
                validateDebinAccount() {
                    const val = this.debinAccount.trim();
                    if (val.length === 0) {
                        this.debinAccountValid = false;
                        this.debinAccountMessage = 'Introduce tu CBU o Alias';
                        return;
                    }

                    // Checks if it is a CBU (22 numeric digits)
                    if (/^\d+$/.test(val)) {
                        if (val.length === 22) {
                            this.debinAccountValid = true;
                            this.debinAccountMessage = 'CBU válido (' + this.selectedBank + ')';
                        } else {
                            this.debinAccountValid = false;
                            this.debinAccountMessage = 'El CBU debe tener exactamente 22 números (' + val.length + '/22)';
                        }
                    } else {
                        // Checks if it is a valid alias format (e.g. word.word.word)
                        if (val.length >= 6 && val.includes('.')) {
                            this.debinAccountValid = true;
                            this.debinAccountMessage = 'Alias verificado';
                        } else {
                            this.debinAccountValid = false;
                            this.debinAccountMessage = 'Alias debe contener puntos (ej: mi.cuenta.banco)';
                        }
                    }
                },

                // Format CUIT with hyphens (e.g. 20-35678901-9)
                formatCuit() {
                    let clean = this.debinCuit.replace(/\D/g, '');
                    if (clean.length > 11) clean = clean.substring(0, 11);

                    if (clean.length > 2 && clean.length <= 10) {
                        this.debinCuit = clean.substring(0, 2) + '-' + clean.substring(2);
                    } else if (clean.length > 10) {
                        this.debinCuit = clean.substring(0, 2) + '-' + clean.substring(2, 10) + '-' + clean.substring(10);
                    } else {
                        this.debinCuit = clean;
                    }
                },

                // DEBIN Validation
                validateDebinForm() {
                    const cleanCuit = this.debinCuit.replace(/\D/g, '');
                    return this.selectedBank && this.debinAccountValid && cleanCuit.length === 11;
                },

                // Main submit trigger
                async submitPayment(methodName) {
                    this.paymentState = 'processing';

                    try {
                        const csrfToken = this.getCookie('csrf_cookie_name');
                        const headers = {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        };
                        if (csrfToken) {
                            headers['X-CSRF-TOKEN'] = csrfToken;
                        }

                        const response = await fetch('/sportsbook/deposit', {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify({
                                amount: this.amount,
                                method: methodName
                            })
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            this.paymentState = 'success';
                            setTimeout(() => {
                                window.location.href = '/';
                            }, 3000);
                        } else {
                            this.paymentState = 'idle';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Pago',
                                text: result.message || 'No se pudo procesar la transacción.',
                                background: 'var(--bg-panel)',
                                color: '#fff',
                                confirmButtonColor: 'var(--primary)'
                            });
                        }
                    } catch (err) {
                        this.paymentState = 'idle';
                        console.error('Payment error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Red',
                            text: 'Ocurrió un error al contactar al servidor. Inténtalo más tarde.',
                            background: 'var(--bg-panel)',
                            color: '#fff',
                            confirmButtonColor: 'var(--primary)'
                        });
                    }
                },

                startPolling() {
                    this.pollingInterval = setInterval(async () => {
                        if (this.paymentState !== 'idle') return;
                        try {
                            const response = await fetch('/checkout/check-status?reference=' + encodeURIComponent(this.mpPreferenceId));
                            const result = await response.json();
                            if (result.status === 'completed') {
                                clearInterval(this.pollingInterval);
                                this.paymentState = 'success';
                                setTimeout(() => {
                                    window.location.href = '/';
                                }, 3000);
                            }
                        } catch (err) {
                            console.error('Polling error:', err);
                        }
                    }, 3000);
                },

                async simulatePayment() {
                    this.paymentState = 'processing';
                    try {
                        const csrfToken = this.getCookie('csrf_cookie_name');
                        const headers = {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        };
                        if (csrfToken) {
                            headers['X-CSRF-TOKEN'] = csrfToken;
                        }

                        const response = await fetch('/api/payments/simulate-webhook', {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify({
                                preference_id: this.mpPreferenceId,
                                amount: this.amount
                            })
                        });

                        const result = await response.json();
                        if (result.status === 'success') {
                            this.paymentState = 'success';
                            setTimeout(() => {
                                window.location.href = '/';
                            }, 3000);
                        } else {
                            this.paymentState = 'idle';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Simulación',
                                text: result.message || 'No se pudo acreditar la simulación.',
                                background: 'var(--bg-panel)',
                                color: '#fff',
                                confirmButtonColor: 'var(--primary)'
                            });
                        }
                    } catch (err) {
                        this.paymentState = 'idle';
                        console.error('Simulation error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Red',
                            text: 'No se pudo conectar para simular el pago.',
                            background: 'var(--bg-panel)',
                            color: '#fff',
                            confirmButtonColor: 'var(--primary)'
                        });
                    }
                },

                getCookie(name) {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);
                    if (parts.length === 2) return parts.pop().split(';').shift();
                    return null;
                },

                async initCardBrick() {
                    try {
                        this.mpInstance = new MercadoPago(this.mpPublicKey, {
                            locale: 'es-AR'
                        });
                        const bricksBuilder = this.mpInstance.bricks();

                        const settings = {
                            initialization: {
                                amount: this.amount,
                                payer: {
                                    email: this.email,
                                },
                            },
                            customization: {
                                visual: {
                                    style: {
                                        theme: 'dark',
                                    },
                                },
                            },
                            callbacks: {
                                onReady: () => {
                                    console.log('Card Payment Brick is ready');
                                },
                                onSubmit: async (cardFormData) => {
                                    return new Promise(async (resolve, reject) => {
                                        this.paymentState = 'processing';
                                        try {
                                            const csrfToken = this.getCookie('csrf_cookie_name');
                                            const headers = {
                                                'Content-Type': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                            };
                                            if (csrfToken) {
                                                headers['X-CSRF-TOKEN'] = csrfToken;
                                            }

                                            const response = await fetch('/checkout/process-card', {
                                                method: 'POST',
                                                headers: headers,
                                                body: JSON.stringify(cardFormData)
                                            });

                                            const result = await response.json();

                                            if (response.ok && result.status === 'approved') {
                                                this.paymentState = 'success';
                                                resolve();
                                                setTimeout(() => {
                                                    window.location.href = '/';
                                                }, 3000);
                                            } else {
                                                this.paymentState = 'idle';
                                                const errorMsg = result.message || 'Error desconocido al procesar el pago.';
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Pago Rechazado o Fallido',
                                                    text: errorMsg,
                                                    background: 'var(--bg-panel)',
                                                    color: '#fff',
                                                    confirmButtonColor: 'var(--primary)'
                                                });
                                                reject();
                                            }
                                        } catch (error) {
                                            this.paymentState = 'idle';
                                            console.error('Error submitting payment:', error);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error de Red',
                                                text: 'Ocurrió un error al contactar al servidor. Inténtalo más tarde.',
                                                background: 'var(--bg-panel)',
                                                color: '#fff',
                                                confirmButtonColor: 'var(--primary)'
                                            });
                                            reject();
                                        }
                                    });
                                },
                                onError: (error) => {
                                    console.error('Card Payment Brick Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de Inicialización',
                                        text: 'No se pudo cargar la pasarela de Mercado Pago. Verifica tu conexión.',
                                        background: 'var(--bg-panel)',
                                        color: '#fff',
                                        confirmButtonColor: 'var(--primary)'
                                    });
                                },
                            },
                        };

                        this.cardBrickController = await bricksBuilder.create(
                            'cardPayment',
                            'cardPaymentBrick_container',
                            settings
                        );
                    } catch (err) {
                        console.error('Failed to initialize Card Payment Brick:', err);
                    }
                }
            };
        }
    </script>
</body>

</html>