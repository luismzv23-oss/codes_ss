<?php
    $kycRecords = $kycRecords ?? [];
    $kycStatus = $kycStatus ?? 'pending';
    $kycSummary = $kycSummary ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $statusText = static fn (string $status): string => match ($status) {
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        default => 'Pendiente',
    };
    $statusColor = static fn (string $status): string => match ($status) {
        'approved' => 'var(--accent-emerald)',
        'rejected' => 'var(--accent-rose)',
        default => 'var(--accent-amber)',
    };
?>

<div style="animation: fadeSlide 0.4s ease-out;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
        <div>
            <h1 style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:900;">KYC</h1>
            <p style="color:var(--text-muted);font-size:0.9rem;">Revision de identidad de apostadores para habilitar retiros.</p>
        </div>
        <div style="color:var(--text-muted);font-size:0.82rem;font-weight:800;"><?= count($kycRecords) ?> verificaciones visibles</div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.8rem;margin-bottom:1rem;">
        <?php foreach (['pending' => 'Pendientes', 'approved' => 'Aprobadas', 'rejected' => 'Rechazadas'] as $key => $label): ?>
            <div class="glass-card" style="padding:1rem;">
                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;"><?= esc($label) ?></div>
                <div style="font-family:Outfit,sans-serif;font-size:1.65rem;font-weight:900;margin-top:0.25rem;color:<?= $statusColor($key) ?>;"><?= (int) ($kycSummary[$key] ?? 0) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="glass-card" style="padding:0.85rem 1rem;margin-bottom:1rem;">
        <div style="display:flex;gap:0.45rem;flex-wrap:wrap;">
            <?php foreach (['pending' => 'Pendientes', 'all' => 'Todos', 'approved' => 'Aprobadas', 'rejected' => 'Rechazadas'] as $value => $label): ?>
                <button class="btn <?= $kycStatus === $value ? 'btn-primary' : 'btn-ghost' ?>" onclick="loadView('/dashboard/kyc?status=<?= esc($value) ?>', 'kyc')">
                    <?= esc($label) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="glass-card" style="padding:0;overflow:auto;">
        <?php if (empty($kycRecords)): ?>
            <div style="padding:2.5rem;text-align:center;color:var(--text-muted);">No hay verificaciones para este filtro.</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Usuario</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Documento</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Datos</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Estado</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Fecha</th>
                        <th style="padding:0.85rem 1rem;text-align:right;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kycRecords as $record): ?>
                        <?php $color = $statusColor($record['status']); ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:0.8rem 1rem;">
                                <div style="font-weight:900;"><?= esc($record['username'] ?? '-') ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($record['email'] ?? '') ?></div>
                            </td>
                            <td style="padding:0.8rem 1rem;">
                                <div style="font-weight:900;text-transform:uppercase;"><?= esc($record['document_type'] ?? '-') ?></div>
                                <div style="font-size:0.82rem;color:var(--text-secondary);"><?= esc($record['document_number'] ?? '-') ?></div>
                            </td>
                            <td style="padding:0.8rem 1rem;color:var(--text-secondary);">
                                <?= esc($record['country'] ?? '-') ?>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= !empty($record['birthdate']) ? date('d/m/Y', strtotime($record['birthdate'])) : '-' ?></div>
                            </td>
                            <td style="padding:0.8rem 1rem;">
                                <span style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid <?= $color ?>55;background:<?= $color ?>18;color:<?= $color ?>;border-radius:999px;padding:0.25rem 0.65rem;font-size:0.72rem;font-weight:900;text-transform:uppercase;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:<?= $color ?>;"></span>
                                    <?= esc($statusText($record['status'])) ?>
                                </span>
                                <?php if (!empty($record['rejection_reason'])): ?>
                                    <div style="font-size:0.72rem;color:var(--accent-rose);margin-top:0.2rem;"><?= esc($record['rejection_reason']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:0.8rem 1rem;color:var(--text-muted);"><?= !empty($record['created_at']) ? date('d/m/Y H:i', strtotime($record['created_at'])) : '-' ?></td>
                            <td style="padding:0.8rem 1rem;text-align:right;">
                                <?php if ($record['status'] === 'pending'): ?>
                                    <div style="display:flex;justify-content:flex-end;gap:0.35rem;flex-wrap:wrap;">
                                        <button class="btn btn-primary" onclick="processKyc(<?= (int) $record['id'] ?>, 'approve', this)" style="padding:0.35rem 0.6rem;font-size:0.74rem;">Aprobar</button>
                                        <button class="btn btn-danger" onclick="processKyc(<?= (int) $record['id'] ?>, 'reject', this)" style="padding:0.35rem 0.6rem;font-size:0.74rem;">Rechazar</button>
                                    </div>
                                <?php elseif (!empty($record['verified_by_name'])): ?>
                                    <span style="font-size:0.72rem;color:var(--text-muted);">Por <?= esc($record['verified_by_name']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
