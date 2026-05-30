<?php
    $money = static fn ($value): string => number_format((float) $value, 2) . ' K';
    $value = static fn (array $limits, string $key): string => isset($limits[$key]) && $limits[$key] !== null ? esc((string) $limits[$key]) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#09111f; --panel:#152033; --soft:#1e2b40; --primary:#f97316; --text:#f8fafc; --muted:#93a4bd; --border:rgba(148,163,184,.18); --danger:#fb7185; --success:#22c55e; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter,system-ui,sans-serif; background:var(--bg); color:var(--text); }
        .topbar { min-height:64px; display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:0.85rem 1.25rem; background:var(--panel); border-bottom:1px solid var(--border); }
        .logo { font-family:Outfit,sans-serif; font-size:1.45rem; font-weight:900; color:var(--primary); text-decoration:none; }
        .nav { display:flex; gap:.55rem; flex-wrap:wrap; justify-content:flex-end; }
        .btn { display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:.55rem; padding:.65rem .9rem; color:var(--text); background:rgba(255,255,255,.07); font-weight:800; text-decoration:none; cursor:pointer; }
        .btn-primary { background:var(--primary); border-color:var(--primary); }
        .btn-danger { background:rgba(251,113,133,.14); border-color:rgba(251,113,133,.35); color:#fecdd3; }
        .wrap { width:min(100% - 2rem, 920px); margin:2rem auto 3rem; }
        .head { border:1px solid var(--border); border-radius:.9rem; background:linear-gradient(135deg,rgba(249,115,22,.14),rgba(21,32,51,.96)); padding:1.4rem; margin-bottom:1rem; }
        h1 { font-family:Outfit,sans-serif; font-size:2.2rem; line-height:1; margin:0 0 .5rem; }
        p { color:var(--muted); line-height:1.6; margin:0; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .card { background:var(--panel); border:1px solid var(--border); border-radius:.8rem; padding:1rem; }
        .card h2 { font-family:Outfit,sans-serif; font-size:1.15rem; margin:0 0 .75rem; }
        label { display:block; color:var(--muted); font-size:.72rem; font-weight:900; text-transform:uppercase; margin:.8rem 0 .35rem; }
        input, textarea { width:100%; background:var(--soft); color:var(--text); border:1px solid var(--border); border-radius:.55rem; padding:.7rem; font:inherit; }
        .actions { display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; margin-top:1rem; }
        .alert { border-radius:.65rem; padding:.8rem 1rem; margin-bottom:1rem; font-weight:800; }
        .ok { color:#86efac; background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.25); }
        .bad { color:#fecdd3; background:rgba(251,113,133,.12); border:1px solid rgba(251,113,133,.25); }
        .status { display:inline-flex; border:1px solid var(--border); border-radius:999px; background:rgba(255,255,255,.07); padding:.35rem .65rem; color:var(--muted); font-weight:900; font-size:.78rem; }
        @media (max-width:760px) { .topbar { align-items:flex-start; flex-direction:column; } .nav { justify-content:flex-start; } .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="logo" href="/">Codex SS</a>
        <nav class="nav">
            <span class="status">Saldo <?= $money($walletBalance ?? 0) ?></span>
            <a class="btn" href="/apuestas-deportivas/juego-responsable">Juego responsable</a>
            <a class="btn btn-primary" href="/">Sportsbook</a>
        </nav>
    </header>

    <main class="wrap">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert ok"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert bad"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <section class="head">
            <h1>Limites responsables</h1>
            <p>Configura limites de deposito, perdida y duracion de sesion. La autoexclusion bloquea nuevas operaciones hasta la fecha indicada.</p>
        </section>

        <section class="grid">
            <form class="card" method="post" action="/sportsbook/responsible-limits">
                <?= csrf_field() ?>
                <h2>Limites de actividad</h2>
                <label>Deposito diario maximo</label>
                <input name="daily_deposit_limit" type="number" min="0" step="0.01" value="<?= $value($limits ?? [], 'daily_deposit_limit') ?>" placeholder="Sin limite">
                <label>Deposito mensual maximo</label>
                <input name="monthly_deposit_limit" type="number" min="0" step="0.01" value="<?= $value($limits ?? [], 'monthly_deposit_limit') ?>" placeholder="Sin limite">
                <label>Perdida diaria maxima</label>
                <input name="daily_loss_limit" type="number" min="0" step="0.01" value="<?= $value($limits ?? [], 'daily_loss_limit') ?>" placeholder="Sin limite">
                <label>Perdida mensual maxima</label>
                <input name="monthly_loss_limit" type="number" min="0" step="0.01" value="<?= $value($limits ?? [], 'monthly_loss_limit') ?>" placeholder="Sin limite">
                <label>Sesion maxima en minutos</label>
                <input name="session_limit_minutes" type="number" min="0" step="1" value="<?= $value($limits ?? [], 'session_limit_minutes') ?>" placeholder="Sin limite">
                <div class="actions">
                    <button class="btn btn-primary" type="submit">Guardar limites</button>
                </div>
            </form>

            <form class="card" method="post" action="/sportsbook/self-exclusion">
                <?= csrf_field() ?>
                <h2>Autoexclusion</h2>
                <?php if (!empty($limits['self_excluded_until'])): ?>
                    <div class="alert bad">Autoexclusion activa hasta <?= esc(date('d/m/Y H:i', strtotime($limits['self_excluded_until']))) ?></div>
                <?php endif; ?>
                <p>Al confirmar, se cerrara tu sesion y no podras apostar, depositar ni retirar hasta que termine el periodo.</p>
                <label>Dias de autoexclusion</label>
                <input name="days" type="number" min="1" max="3650" step="1" placeholder="Ej. 30" required>
                <label>Motivo opcional</label>
                <textarea name="reason" rows="4" maxlength="255" placeholder="Motivo o nota para soporte"></textarea>
                <div class="actions">
                    <button class="btn btn-danger" type="submit">Activar autoexclusion</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
