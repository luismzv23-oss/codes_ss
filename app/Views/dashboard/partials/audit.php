<?php
    $logs = $logs ?? [];
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $totalLogs = $totalLogs ?? count($logs);

    $statusColor = static function (string $status): string {
        return match ($status) {
            'failure' => 'var(--accent-rose)',
            'suspicious' => 'var(--accent-amber)',
            default => 'var(--accent-emerald)',
        };
    };

    $jsonPreview = static function (?string $json): string {
        if (!$json) return '-';
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return esc($json);
        $text = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return esc(strlen($text) > 120 ? substr($text, 0, 117) . '...' : $text);
    };
?>

<div style="animation: fadeSlide 0.4s ease-out;">
    <style>
        @media (max-width: 768px) {
            .audit-responsive-table thead {
                display: none !important;
            }
            .audit-responsive-table, 
            .audit-responsive-table tbody, 
            .audit-responsive-table tr, 
            .audit-responsive-table td {
                display: block !important;
                width: 100% !important;
            }
            .audit-responsive-table tr {
                margin-bottom: 1rem;
                border: 1px solid var(--border);
                border-radius: 0.75rem;
                background: rgba(255, 255, 255, 0.02);
                padding: 0.75rem;
            }
            .audit-responsive-table td {
                text-align: right !important;
                padding: 0.55rem 0.75rem !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.03);
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .audit-responsive-table td:last-child {
                border-bottom: none;
            }
            .audit-responsive-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
                font-size: 0.75rem;
                text-transform: uppercase;
                text-align: left;
                margin-right: 1rem;
            }
            .audit-responsive-table td > details {
                width: 100%;
                text-align: left;
            }
        }
    </style>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-family:'Outfit',sans-serif;font-size:1.75rem;font-weight:800;">Auditoria Operativa</h1>
            <p style="color:var(--text-muted);font-size:0.9rem;">Registro de acciones administrativas y eventos sensibles.</p>
        </div>
        <span style="font-size:0.78rem;font-weight:800;color:var(--text-muted);background:rgba(255,255,255,0.06);padding:0.38rem 0.7rem;border-radius:999px;"><?= number_format((int) $totalLogs) ?> logs</span>
    </div>

    <div class="glass-card" style="padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.85rem 1.25rem;border-bottom:1px solid var(--border);">
            <div style="font-weight:850;">Ultimas acciones</div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <?php if ($currentPage > 1): ?>
                    <button class="btn btn-ghost" onclick="loadView('/dashboard/audit?page=<?= $currentPage - 1 ?>', 'audit')" style="padding:0.35rem 0.65rem;">Anterior</button>
                <?php endif; ?>
                <span style="font-size:0.78rem;color:var(--text-muted);">Pagina <?= (int) $currentPage ?> / <?= (int) $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <button class="btn btn-ghost" onclick="loadView('/dashboard/audit?page=<?= $currentPage + 1 ?>', 'audit')" style="padding:0.35rem 0.65rem;">Siguiente</button>
                <?php endif; ?>
            </div>
        </div>

        <table class="audit-responsive-table" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">
                    <th style="padding:0.7rem 1rem;">Fecha</th>
                    <th style="padding:0.7rem 1rem;">Usuario</th>
                    <th style="padding:0.7rem 1rem;">Accion</th>
                    <th style="padding:0.7rem 1rem;">Entidad</th>
                    <th style="padding:0.7rem 1rem;">Cambios</th>
                    <th style="padding:0.7rem 1rem;">IP</th>
                    <th style="padding:0.7rem 1rem;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="padding:1.5rem;text-align:center;color:var(--text-muted);">No hay registros de auditoria.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <?php $color = $statusColor((string) $log['status']); ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td data-label="Fecha" style="padding:0.75rem 1rem;color:var(--text-muted);white-space:nowrap;"><?= esc(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
                        <td data-label="Usuario" style="padding:0.75rem 1rem;font-weight:800;"><?= esc($log['username'] ?? 'Sistema') ?></td>
                        <td data-label="Accion" style="padding:0.75rem 1rem;">
                            <span style="font-weight:850;color:var(--text-primary);"><?= esc($log['action']) ?></span>
                        </td>
                        <td data-label="Entidad" style="padding:0.75rem 1rem;color:var(--text-muted);"><?= esc(($log['entity'] ?? '-') . (!empty($log['entity_id']) ? ' #' . $log['entity_id'] : '')) ?></td>
                        <td data-label="Cambios" style="padding:0.75rem 1rem;max-width:360px;">
                            <details>
                                <summary style="cursor:pointer;color:var(--primary);font-weight:800;">Ver</summary>
                                <div style="margin-top:0.45rem;font-family:monospace;font-size:0.72rem;color:var(--text-muted);line-height:1.45;">
                                    <div><strong>Antes:</strong> <?= $jsonPreview($log['old_values'] ?? null) ?></div>
                                    <div><strong>Despues:</strong> <?= $jsonPreview($log['new_values'] ?? null) ?></div>
                                </div>
                            </details>
                        </td>
                        <td data-label="IP" style="padding:0.75rem 1rem;color:var(--text-muted);"><?= esc($log['ip_address']) ?></td>
                        <td data-label="Estado" style="padding:0.75rem 1rem;">
                            <span style="font-size:0.7rem;font-weight:900;color:<?= $color ?>;background:<?= str_replace(')', ',0.12)', $color) ?>;padding:0.22rem 0.55rem;border-radius:999px;"><?= esc($log['status']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex;justify-content:center;align-items:center;gap:0.75rem;margin-top:1.25rem;color:var(--text-muted);font-weight:800;">
        <?php if ($currentPage > 1): ?>
            <button class="btn btn-ghost" onclick="loadView('/dashboard/audit?page=<?= $currentPage - 1 ?>', 'audit')">Anterior</button>
        <?php endif; ?>
        <span>Pagina <?= (int) $currentPage ?> / <?= (int) $totalPages ?></span>
        <?php if ($currentPage < $totalPages): ?>
            <button class="btn btn-ghost" onclick="loadView('/dashboard/audit?page=<?= $currentPage + 1 ?>', 'audit')">Siguiente</button>
        <?php endif; ?>
    </div>
</div>
