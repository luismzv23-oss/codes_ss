<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Codex SS - Registra tu Cuenta' ?></title>

    <!-- Resource Hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://unpkg.com/htmx.org@1.9.10" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" as="script">

    <!-- Scripts -->
    <script src="https://unpkg.com/htmx.org@1.9.10" defer></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('htmx:configRequest', (event) => {
            const getCookie = (name) => {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
            };
            const csrfToken = getCookie('csrf_cookie_name');
            if (csrfToken) {
                event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
            }
        });
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --bg-color: #020617;
            --surface-color: rgba(15, 23, 42, 0.6);
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --accent-color: #8b5cf6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
            --error-color: #ef4444;
            --success-color: #10b981;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            background-image:
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 300px),
                radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 300px);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .glass-panel {
            background: var(--surface-color);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .glass-panel:hover {
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6), 0 0 20px rgba(59, 130, 246, 0.1);
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.05em;
        }

        .subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            background: rgba(0, 0, 0, 0.4);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:hover::after {
            left: 100%;
        }

        .auth-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .btn-google {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.85rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.94);
            color: #111827;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn-google:hover {
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px -8px rgba(15, 23, 42, 0.65);
        }

        .google-mark {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4285f4;
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1;
        }

        .auth-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: #60a5fa;
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 1px solid transparent;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        /* Loading indicator for HTMX */
        .htmx-indicator {
            display: none;
        }

        .htmx-request .htmx-indicator {
            display: inline-block;
        }

        .htmx-request .btn-text {
            display: none;
        }

        /* Decorative Background Elements */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: 1;
            opacity: 0.6;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: var(--primary-color);
            animation: float 10s ease-in-out infinite;
        }

        .blob-2 {
            bottom: -10%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: var(--accent-color);
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -50px) rotate(10deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(-5deg);
            }

            100% {
                transform: translate(0, 0) rotate(0deg);
            }
        }

        .fade-enter {
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>

<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="auth-container">
        <div class="glass-panel fade-enter" id="auth-content">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
</body>

</html>
