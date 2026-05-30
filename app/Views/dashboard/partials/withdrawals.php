<?php
    $withdrawals = $withdrawals ?? [];
    $withdrawalStatus = $withdrawalStatus ?? 'pending';
    $withdrawalSummary = $withdrawalSummary ?? [];
    $money = static fn ($value): string => '$' . number_format((float) $value, 2, ',', '.');
    $statusText = static fn (string $status): string => match ($status) {
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'pending' => 'Pendiente',
        default => ucfirst($status),
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
            <h1 style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:900;">Retiros</h1>
            <p style="color:var(--text-muted);font-size:0.9rem;">Solicitudes de retiro de apostadores, aprobacion y rechazo con devolucion automatica.</p>
        </div>
        <div style="color:var(--text-muted);font-size:0.82rem;font-weight:800;"><?= count($withdrawals) ?> solicitudes visibles</div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.8rem;margin-bottom:1rem;">
        <?php foreach (['pending' => 'Pendientes', 'approved' => 'Aprobados', 'rejected' => 'Rechazados'] as $key => $label): ?>
            <?php $row = $withdrawalSummary[$key] ?? ['count' => 0, 'total' => 0]; ?>
            <div class="glass-card" style="padding:1rem;">
                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;"><?= esc($label) ?></div>
                <div style="display:flex;align-items:end;justify-content:space-between;gap:0.75rem;margin-top:0.25rem;">
                    <div style="font-family:Outfit,sans-serif;font-size:1.45rem;font-weight:900;color:<?= $statusColor($key) ?>;"><?= $money($row['total'] ?? 0) ?></div>
                    <div style="font-size:0.82rem;color:var(--text-muted);font-weight:800;"><?= (int) ($row['count'] ?? 0) ?> sol.</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="glass-card" style="padding:0.85rem 1rem;margin-bottom:1rem;">
        <div style="display:flex;gap:0.45rem;flex-wrap:wrap;">
            <?php foreach (['pending' => 'Pendientes', 'all' => 'Todos', 'approved' => 'Aprobados', 'rejected' => 'Rechazados'] as $value => $label): ?>
                <button class="btn <?= $withdrawalStatus === $value ? 'btn-primary' : 'btn-ghost' ?>" onclick="loadView('/dashboard/withdrawals?status=<?= esc($value) ?>', 'withdrawals')">
                    <?= esc($label) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="glass-card" style="padding:0;overflow:auto;">
        <?php if (empty($withdrawals)): ?>
            <div style="padding:2.5rem;text-align:center;color:var(--text-muted);">No hay solicitudes para este filtro.</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">ID</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Usuario</th>
                        <th style="padding:0.85rem 1rem;text-align:right;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Monto</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Cuenta propia destino</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Estado</th>
                        <th style="padding:0.85rem 1rem;text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Fecha</th>
                        <th style="padding:0.85rem 1rem;text-align:right;color:var(--text-muted);text-transform:uppercase;font-size:0.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $request): ?>
                        <?php $color = $statusColor($request['status']); ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:0.8rem 1rem;font-family:monospace;color:var(--text-muted);font-weight:800;">WDR-<?= str_pad((string) $request['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td style="padding:0.8rem 1rem;">
                                <div style="font-weight:900;"><?= esc($request['username'] ?? '-') ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($request['email'] ?? '') ?></div>
                            </td>
                            <td style="padding:0.8rem 1rem;text-align:right;font-weight:900;color:var(--accent-rose);"><?= $money($request['amount']) ?></td>
                            <td style="padding:0.8rem 1rem;color:var(--text-secondary);min-width:260px;">
                                <div style="display:grid;gap:0.28rem;">
                                    <div>
                                        <span style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Alias / CVU / CBU</span>
                                        <strong style="color:var(--text-primary);"><?= esc($request['target_account']) ?></strong>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
                                        <div>
                                            <span style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Titular</span>
                                            <?= esc($request['account_holder'] ?? '-') ?>
                                        </div>
                                        <div>
                                            <span style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Documento</span>
                                            <?= esc($request['account_document'] ?? '-') ?>
                                        </div>
                                    </div>
                                    <div style="font-size:0.72rem;color:<?= (int)($request['own_account_confirmed'] ?? 0) === 1 ? 'var(--accent-emerald)' : 'var(--accent-rose)' ?>;font-weight:900;">
                                        <?= (int)($request['own_account_confirmed'] ?? 0) === 1 ? 'Cuenta propia confirmada por el apostador' : 'Cuenta propia no confirmada' ?>
                                    </div>
                                    <div style="font-size:0.7rem;color:var(--text-muted);">
                                        KYC apostador: <?= esc($request['document_type'] ?? '-') ?> <?= esc($request['document_number'] ?? '-') ?>
                                    </div>
                                </div>
                                <?php if (!empty($request['user_note'])): ?>
                                    <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.45rem;">Nota: <?= esc($request['user_note']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:0.8rem 1rem;">
                                <span style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid <?= $color ?>55;background:<?= $color ?>18;color:<?= $color ?>;border-radius:999px;padding:0.25rem 0.65rem;font-size:0.72rem;font-weight:900;text-transform:uppercase;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:<?= $color ?>;"></span>
                                    <?= esc($statusText($request['status'])) ?>
                                </span>
                                <?php if (!empty($request['processed_by_name'])): ?>
                                    <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.2rem;">Por <?= esc($request['processed_by_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:0.8rem 1rem;color:var(--text-muted);"><?= !empty($request['created_at']) ? date('d/m/Y H:i', strtotime($request['created_at'])) : '-' ?></td>
                            <td style="padding:0.8rem 1rem;text-align:right;">
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div style="display:flex;justify-content:flex-end;gap:0.35rem;flex-wrap:wrap;">
                                        <button class="btn btn-primary" onclick="processWithdrawal(<?= (int) $request['id'] ?>, 'approve', this)" style="padding:0.35rem 0.6rem;font-size:0.74rem;">Aprobar</button>
                                        <button class="btn btn-danger" onclick="processWithdrawal(<?= (int) $request['id'] ?>, 'reject', this)" style="padding:0.35rem 0.6rem;font-size:0.74rem;">Rechazar</button>
                                    </div>
                                <?php elseif (!empty($request['admin_note'])): ?>
                                    <span style="font-size:0.72rem;color:var(--text-muted);"><?= esc($request['admin_note']) ?></span>
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

<script>
    async function processWithdrawal(id, action, btn) {
        const note = prompt(action === 'approve' ? 'Nota de aprobacion:' : 'Motivo del rechazo:', '');
        if (note === null) return;

        const original = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Procesando...';
        }

        try {
            const body = new FormData();
            body.append('admin_note', note);
            if (typeof postDashboardAction !== 'function') {
                throw new Error('No se pudo preparar la accion. Recarga el dashboard.');
            }
            await postDashboardAction('/dashboard/withdrawals/' + action + '/' + id, body);
            loadView('/dashboard/withdrawals', 'withdrawals');
        } catch (error) {
            alert(error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    }
</script>
