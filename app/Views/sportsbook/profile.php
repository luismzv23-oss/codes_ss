<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family:Inter,sans-serif; background:#09111f; color:#f8fafc; }
        .topbar { height:64px; display:flex; align-items:center; justify-content:space-between; padding:0 1.25rem; background:#152033; border-bottom:1px solid rgba(148,163,184,.18); }
        .logo { font-family:Outfit,sans-serif; font-size:1.45rem; font-weight:900; color:#f97316; text-decoration:none; }
        .btn { display:inline-flex; align-items:center; justify-content:center; border:0; border-radius:.55rem; padding:.65rem .95rem; color:#fff; background:#f97316; font-weight:800; text-decoration:none; cursor:pointer; }
        .btn-ghost { background:rgba(255,255,255,.08); border:1px solid rgba(148,163,184,.18); }
        .wrap { max-width:760px; margin:2rem auto; padding:0 1rem; }
        .card { background:#152033; border:1px solid rgba(148,163,184,.18); border-radius:.85rem; padding:1.25rem; margin-bottom: 2rem; }
        h1 { font-family:Outfit,sans-serif; font-size:2rem; margin:0 0 .35rem; }
        p { color:#93a4bd; }
        label { display:block; color:#93a4bd; font-size:.75rem; text-transform:uppercase; font-weight:900; margin:.9rem 0 .35rem; }
        input, select { width:100%; box-sizing:border-box; background:#1e2b40; border:1px solid rgba(148,163,184,.2); color:#f8fafc; border-radius:.6rem; padding:.75rem; font:inherit; }
        input:focus, select:focus { outline:none; border-color:#f97316; }
        .alert { padding:.8rem 1rem; border-radius:.65rem; margin-bottom:1rem; font-weight:800; }
        .ok { background:rgba(34,197,94,.12); color:#86efac; }
        .bad { background:rgba(251,113,133,.12); color:#fda4af; }
        .info-group { background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 0.5rem; border: 1px dashed rgba(255,255,255,0.1); margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.2rem; }
        .info-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 800; }
        .info-val { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; }
        .responsive-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 600px) {
            .topbar { height:auto; min-height:64px; flex-direction:column; align-items:flex-start; gap:0.5rem; padding: 0.85rem 1.25rem; }
            .responsive-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/" class="logo">Codex SS</a>
        <a href="/" class="btn btn-ghost">Volver al inicio</a>
    </header>
    <main class="wrap">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert ok"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert bad"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        
        <section class="card">
            <h1>Mi Perfil</h1>
            <p>Visualiza y actualiza tus datos personales de la cuenta.</p>

            <div class="responsive-grid" style="margin-top: 1.5rem; margin-bottom: 2rem;">
                <div class="info-group">
                    <span class="info-label">Usuario</span>
                    <span class="info-val"><?= esc($user['username']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Correo Electrónico</span>
                    <span class="info-val"><?= esc($user['email']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Fecha de Registro</span>
                    <span class="info-val"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Estado de la cuenta</span>
                    <span class="info-val" style="color: <?= $user['is_active'] ? '#22c55e' : '#ef4444' ?>;"><?= $user['is_active'] ? 'Activa' : 'Inactiva' ?></span>
                </div>
            </div>

            <form method="post" action="/sportsbook/profile/update">
                <?= csrf_field() ?>
                
                <div class="responsive-grid">
                    <div>
                        <label>Teléfono</label>
                        <input type="tel" name="phone" value="<?= esc(old('phone', $user['phone'] ?? '')) ?>" placeholder="+54 9 11 1234-5678">
                    </div>
                    <div>
                        <label>País</label>
                        <input type="text" name="country" value="<?= esc(old('country', $user['country'] ?? '')) ?>" placeholder="Ej: Argentina">
                    </div>
                </div>

                <div style="margin-top: 0.5rem;">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="birthdate" value="<?= esc(old('birthdate', $user['birthdate'] ?? '')) ?>">
                </div>

                <h3 style="margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">Seguridad y Contraseña</h3>
                <p style="font-size: 0.85rem;">Si no deseas cambiar tu contraseña, deja estos campos en blanco.</p>

                <div style="margin-top: 0.5rem;">
                    <label>Contraseña Actual</label>
                    <input type="password" name="current_password" placeholder="Requerida solo si cambias la contraseña">
                </div>

                <div style="margin-top: 0.5rem;">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" placeholder="Mínimo 6 caracteres" minlength="6">
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:2rem;">
                    <button class="btn" type="submit">Actualizar Perfil</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
