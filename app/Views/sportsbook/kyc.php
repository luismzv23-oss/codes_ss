<?php
    $status = $user['kyc_status'] ?? ($kyc['status'] ?? 'not_submitted');
    $statusText = match ($status) {
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'pending' => 'Pendiente',
        default => 'No enviado',
    };
    $statusColor = match ($status) {
        'approved' => '#22c55e',
        'rejected' => '#fb7185',
        'pending' => '#fbbf24',
        default => '#93a4bd',
    };
?>
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
        .card { background:#152033; border:1px solid rgba(148,163,184,.18); border-radius:.85rem; padding:1.25rem; }
        h1 { font-family:Outfit,sans-serif; font-size:2rem; margin:0 0 .35rem; }
        p { color:#93a4bd; }
        label { display:block; color:#93a4bd; font-size:.75rem; text-transform:uppercase; font-weight:900; margin:.9rem 0 .35rem; }
        input, select { width:100%; box-sizing:border-box; background:#1e2b40; border:1px solid rgba(148,163,184,.2); color:#f8fafc; border-radius:.6rem; padding:.75rem; font:inherit; }
        .status { display:inline-flex; align-items:center; gap:.4rem; border:1px solid <?= $statusColor ?>55; background:<?= $statusColor ?>18; color:<?= $statusColor ?>; border-radius:999px; padding:.35rem .75rem; font-weight:900; text-transform:uppercase; font-size:.75rem; }
        .alert { padding:.8rem 1rem; border-radius:.65rem; margin-bottom:1rem; font-weight:800; }
        .ok { background:rgba(34,197,94,.12); color:#86efac; }
        .bad { background:rgba(251,113,133,.12); color:#fda4af; }
        @media (max-width: 600px) {
            .topbar { height:auto; min-height:64px; flex-direction:column; align-items:flex-start; gap:0.5rem; padding: 0.85rem 1.25rem; }
            .kyc-header-row { flex-direction: column; align-items: flex-start; gap: 0.75rem !important; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="/" class="logo">Codex SS</a>
        <a href="/" class="btn btn-ghost">Volver</a>
    </header>
    <main class="wrap">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert ok"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert bad"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <section class="card">
            <div class="kyc-header-row" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                <div>
                    <h1>Verificacion KYC</h1>
                    <p>Valida tu identidad para habilitar retiros y operaciones sensibles.</p>
                </div>
                <span class="status"><?= esc($statusText) ?></span>
            </div>

            <?php if (($kyc['rejection_reason'] ?? '') !== ''): ?>
                <div class="alert bad">Motivo de rechazo: <?= esc($kyc['rejection_reason']) ?></div>
            <?php endif; ?>

            <form method="post" action="/sportsbook/kyc/submit">
                <?= csrf_field() ?>
                <label>Tipo de documento</label>
                <select name="document_type" required>
                    <?php $currentType = old('document_type', $user['document_type'] ?? $kyc['document_type'] ?? 'dni'); ?>
                    <option value="dni" <?= $currentType === 'dni' ? 'selected' : '' ?>>DNI</option>
                    <option value="passport" <?= $currentType === 'passport' ? 'selected' : '' ?>>Pasaporte</option>
                    <option value="license" <?= $currentType === 'license' ? 'selected' : '' ?>>Licencia</option>
                </select>
                <label>Numero de documento</label>
                <input name="document_number" required minlength="6" maxlength="50" value="<?= esc(old('document_number', $user['document_number'] ?? $kyc['document_number'] ?? '')) ?>">
                <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
                    <button class="btn" type="submit"><?= $status === 'approved' ? 'Reenviar verificacion' : 'Enviar verificacion' ?></button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
