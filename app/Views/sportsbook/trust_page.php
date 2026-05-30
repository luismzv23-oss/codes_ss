<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <meta name="description" content="<?= esc($intro ?? 'Informacion publica de confianza para Codex SS.') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #09111f;
            --panel: #152033;
            --soft: #1e2b40;
            --primary: #f97316;
            --text: #f8fafc;
            --muted: #93a4bd;
            --border: rgba(148, 163, 184, 0.2);
            --danger: #fb7185;
            --success: #22c55e;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .topbar { min-height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.85rem 1.25rem; background: var(--panel); border-bottom: 1px solid var(--border); }
        .logo { font-family: Outfit, sans-serif; font-weight: 900; font-size: 1.45rem; color: var(--primary); text-decoration: none; }
        .nav { display: flex; gap: 0.55rem; flex-wrap: wrap; justify-content: flex-end; }
        .nav a, .btn { border: 1px solid var(--border); border-radius: 0.55rem; color: var(--text); background: rgba(255,255,255,0.06); padding: 0.55rem 0.8rem; text-decoration: none; font-weight: 800; font-size: 0.82rem; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .wrap { width: min(100% - 2rem, 980px); margin: 2rem auto 3rem; }
        .hero { border: 1px solid var(--border); background: linear-gradient(135deg, rgba(249,115,22,0.16), rgba(21,32,51,0.96)); border-radius: 0.9rem; padding: 1.5rem; }
        .eyebrow { display: inline-flex; align-items: center; gap: 0.45rem; color: #fed7aa; background: rgba(249,115,22,0.14); border: 1px solid rgba(249,115,22,0.35); border-radius: 999px; padding: 0.35rem 0.7rem; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; }
        h1 { font-family: Outfit, sans-serif; font-size: clamp(2rem, 5vw, 3.4rem); line-height: 1; margin: 1rem 0 0.65rem; letter-spacing: 0; }
        p { color: var(--muted); line-height: 1.65; margin: 0; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-top: 1rem; }
        .card { background: var(--panel); border: 1px solid var(--border); border-radius: 0.8rem; padding: 1.1rem; }
        .card h2 { font-family: Outfit, sans-serif; font-size: 1.1rem; margin: 0 0 0.65rem; }
        ul { margin: 0; padding-left: 1.1rem; color: var(--muted); line-height: 1.6; }
        li + li { margin-top: 0.45rem; }
        .trust-strip { display: flex; flex-wrap: wrap; gap: 0.55rem; margin-top: 1rem; }
        .pill { color: var(--text); background: var(--soft); border: 1px solid var(--border); border-radius: 999px; padding: 0.45rem 0.75rem; font-size: 0.78rem; font-weight: 900; }
        .footer { margin-top: 1.25rem; color: var(--muted); font-size: 0.8rem; line-height: 1.55; border-top: 1px solid var(--border); padding-top: 1rem; }
        @media (max-width: 720px) {
            .topbar { align-items: flex-start; flex-direction: column; }
            .nav { justify-content: flex-start; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/apuestas-deportivas" class="logo">Codex SS</a>
        <nav class="nav" aria-label="Informacion publica">
            <a href="/apuestas-deportivas/reglas-de-apuestas">Reglas</a>
            <a href="/apuestas-deportivas/juego-responsable">Juego responsable</a>
            <a href="/apuestas-deportivas/terminos-y-condiciones">Terminos</a>
            <a href="/apuestas-deportivas/soporte">Soporte</a>
            <a href="/apuestas-deportivas" class="btn-primary">Apostar</a>
        </nav>
    </header>

    <main class="wrap">
        <section class="hero">
            <span class="eyebrow">18+ - Transparencia operativa</span>
            <h1><?= esc($heading) ?></h1>
            <p><?= esc($intro) ?></p>
            <div class="trust-strip">
                <span class="pill">Mayores de 18</span>
                <span class="pill">KYC para retiros</span>
                <span class="pill">Limites de riesgo</span>
                <span class="pill">Auditoria interna</span>
                <span class="pill">Jurisdiccion controlada</span>
            </div>
        </section>

        <section class="grid" aria-label="Contenido">
            <?php foreach (($sections ?? []) as $section): ?>
                <article class="card">
                    <h2><?= esc($section['title'] ?? '') ?></h2>
                    <ul>
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                            <li><?= esc($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </section>

        <footer class="footer">
            Codex SS debe operar solamente donde cuente con autorizacion aplicable. Antes de produccion, validar licencia,
            geolocalizacion, politicas de privacidad, cookies, canal formal de reclamos y mecanismos de autoexclusion.
        </footer>
    </main>
</body>
</html>
