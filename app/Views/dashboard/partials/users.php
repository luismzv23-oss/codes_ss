<?php
    $internalUsers = $internalUsers ?? [];
    $externalUsers = $externalUsers ?? [];
    $totalUsers = $totalUsers ?? (count($internalUsers) + count($externalUsers));

    $renderTable = static function (array $users, string $emptyText): void {
?>
    <?php if (empty($users)): ?>
        <div style="padding:2rem;text-align:center;color:var(--text-muted);"><?= esc($emptyText) ?></div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border);">
                    <th style="padding: 0.85rem 1.25rem; text-align:left; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Usuario</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:left; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Email</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:left; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Rol</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:right; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Saldo</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:right; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Riesgo</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:left; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Estado</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:left; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Alta</th>
                    <th style="padding: 0.85rem 1.25rem; text-align:right; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                        $username = (string) ($user['username'] ?? 'usuario');
                        $roleName = (string) ($user['role_name'] ?? ((int) ($user['role_id'] ?? 0) === 1 ? 'Admin' : 'User'));
                        $isInternal = (int) ($user['role_id'] ?? 0) === 1;
                        $roleColor = $isInternal ? '#6366f1' : '#22d3ee';
                        $statusColor = !empty($user['is_active']) ? 'var(--accent-emerald)' : 'var(--accent-rose)';
                        $statusText = !empty($user['is_active']) ? 'Activo' : 'Inactivo';
                        $lockedUntil = !empty($user['locked_until']) ? strtotime($user['locked_until']) : null;
                        $isLocked = $lockedUntil && $lockedUntil > time();
                        if ($isLocked) {
                            $statusColor = 'var(--accent-amber)';
                            $statusText = 'Bloqueado';
                        }
                        $createdAt = !empty($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '-';
                        $lockedLabel = $isLocked ? ' hasta ' . date('d/m/Y H:i', $lockedUntil) : '';
                        $balance = (float) ($user['balance'] ?? 0);
                        $pendingExposure = (float) ($user['pending_exposure'] ?? 0);
                        $pendingTickets = (int) ($user['pending_tickets'] ?? 0);
                        $totalTickets = (int) ($user['total_tickets'] ?? 0);
                    ?>
                    <tr data-user-row
                        data-user-search="<?= esc(strtolower($username . ' ' . ($user['email'] ?? '') . ' ' . $roleName), 'attr') ?>"
                        style="border-bottom: 1px solid var(--border); transition: background 0.15s;"
                        onmouseover="this.style.background='var(--surface-hover)'"
                        onmouseout="this.style.background='transparent'">
                        <td style="padding: 0.75rem 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=<?= ltrim($roleColor, '#') ?>&color=fff&size=64&bold=true" style="width:32px;height:32px;border-radius:8px;" alt="">
                                <div>
                                    <div style="font-weight: 700;"><?= esc($username) ?></div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);">ID #<?= (int) $user['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 0.75rem 1.25rem; color: var(--text-secondary);"><?= esc($user['email'] ?? '-') ?></td>
                        <td style="padding: 0.75rem 1.25rem;">
                            <span style="font-size: 0.7rem; font-weight: 700; color: <?= $roleColor ?>; background: <?= $roleColor ?>18; padding: 0.2rem 0.6rem; border-radius: 9999px;"><?= esc($isInternal ? 'Admin interno' : 'Apostador') ?></span>
                        </td>
                        <td style="padding: 0.75rem 1.25rem; text-align:right; font-weight:800;">$<?= number_format($balance, 2, ',', '.') ?></td>
                        <td style="padding: 0.75rem 1.25rem; text-align:right;">
                            <div style="font-weight:800;color:<?= $pendingExposure > 0 ? 'var(--accent-amber)' : 'var(--text-secondary)' ?>;">$<?= number_format($pendingExposure, 2, ',', '.') ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);"><?= $pendingTickets ?> pendientes / <?= $totalTickets ?> tickets</div>
                        </td>
                        <td style="padding: 0.75rem 1.25rem;">
                            <span style="font-size: 0.7rem; font-weight: 700; color: <?= $statusColor ?>; display:flex;align-items:center;gap:0.3rem;">
                                <span style="width:6px;height:6px;border-radius:50%;background:<?= $statusColor ?>;"></span>
                                <?= $statusText ?>
                            </span>
                            <?php if ($isLocked): ?>
                                <div style="font-size:0.68rem;color:var(--text-muted);margin-top:0.15rem;"><?= esc($lockedLabel) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem 1.25rem; color: var(--text-secondary);"><?= esc($createdAt) ?></td>
                        <td style="padding: 0.75rem 1.25rem; text-align:right;">
                            <div style="display:flex;justify-content:flex-end;gap:0.35rem;flex-wrap:wrap;">
                                <?php if ($isLocked): ?>
                                    <button class="btn btn-ghost" style="padding:0.35rem 0.55rem;font-size:0.72rem;" onclick="unlockUser(<?= (int) $user['id'] ?>, this)">
                                        <i data-lucide="unlock" style="width:13px;height:13px;"></i> Desbloquear
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-ghost" style="padding:0.35rem 0.55rem;font-size:0.72rem;" onclick="lockUser(<?= (int) $user['id'] ?>, this)">
                                        <i data-lucide="lock" style="width:13px;height:13px;"></i> Bloquear
                                    </button>
                                <?php endif; ?>
                                <button class="btn <?= !empty($user['is_active']) ? 'btn-ghost' : 'btn-primary' ?>" style="padding:0.35rem 0.55rem;font-size:0.72rem;" onclick="toggleUserActive(<?= (int) $user['id'] ?>, this)">
                                    <i data-lucide="<?= !empty($user['is_active']) ? 'user-x' : 'user-check' ?>" style="width:13px;height:13px;"></i>
                                    <?= !empty($user['is_active']) ? 'Suspender' : 'Reactivar' ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php
    };
?>

<div style="animation: fadeSlide 0.4s ease-out;" x-data="{ search: '', activeTab: 'external', showModal: false, toast: '' }" x-init="$watch('search', () => filterUserRows())">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.75rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800;">Gestion de Usuarios</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Administre las cuentas internas y los apostadores de la plataforma.</p>
        </div>
        <button class="btn btn-primary" style="display:flex;align-items:center;gap:0.4rem;" @click="showModal = true">
            <i data-lucide="user-plus" style="width:16px;height:16px;"></i> Nuevo Usuario
        </button>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.85rem;margin-bottom:1rem;">
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Total usuarios</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.6rem;font-weight:900;margin-top:0.25rem;"><?= (int) $totalUsers ?></div>
        </div>
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Usuarios internos</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.6rem;font-weight:900;margin-top:0.25rem;color:#a5b4fc;"><?= count($internalUsers) ?></div>
        </div>
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Usuarios externos</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.6rem;font-weight:900;margin-top:0.25rem;color:var(--accent-cyan);"><?= count($externalUsers) ?></div>
        </div>
    </div>

    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;">
        <button class="btn" @click="activeTab = 'internal'; setTimeout(filterUserRows, 0)"
                :class="activeTab === 'internal' ? 'btn-primary' : 'btn-ghost'"
                style="display:flex;align-items:center;gap:0.45rem;">
            <i data-lucide="shield" style="width:16px;height:16px;"></i>
            Usuarios Internos
            <span style="font-size:0.72rem;opacity:0.82;">(<?= count($internalUsers) ?>)</span>
        </button>
        <button class="btn" @click="activeTab = 'external'; setTimeout(filterUserRows, 0)"
                :class="activeTab === 'external' ? 'btn-primary' : 'btn-ghost'"
                style="display:flex;align-items:center;gap:0.45rem;">
            <i data-lucide="ticket" style="width:16px;height:16px;"></i>
            Usuarios Externos
            <span style="font-size:0.72rem;opacity:0.82;">(<?= count($externalUsers) ?>)</span>
        </button>
    </div>

    <div class="glass-card" style="margin-bottom: 1rem; padding: 0.75rem 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i data-lucide="search" style="width:18px;height:18px;color:var(--text-muted);flex-shrink:0;"></i>
            <input type="text" x-model="search" @input="filterUserRows()" placeholder="Buscar por nombre, email o rol..."
                   style="flex:1; background:none; border:none; color:var(--text-primary); font-size:0.875rem; font-family:'Inter',sans-serif; outline:none;"
                   autocomplete="off">
        </div>
    </div>

    <div x-show="toast" x-transition.opacity.duration.180ms style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border:1px solid rgba(34,211,238,0.25);background:rgba(34,211,238,0.08);border-radius:0.75rem;color:var(--accent-cyan);font-weight:800;" x-text="toast"></div>

    <div class="glass-card" style="padding: 0; overflow: auto;" x-show="activeTab === 'internal'" x-transition.opacity.duration.180ms>
        <?php $renderTable($internalUsers, 'No hay usuarios internos registrados.'); ?>
    </div>

    <div class="glass-card" style="padding: 0; overflow: auto;" x-show="activeTab === 'external'" x-transition.opacity.duration.180ms>
        <?php $renderTable($externalUsers, 'No hay usuarios externos registrados.'); ?>
    </div>

    <template x-if="showModal">
        <div class="modal-backdrop" @click.self="showModal = false" x-transition>
            <div class="modal-box" x-transition.scale.90 style="max-width:520px;">
                <h3>Nuevo Usuario</h3>
                <p>La creacion de usuarios desde esta ventana queda lista para conectar al endpoint administrativo.</p>
                <div class="modal-actions">
                    <button class="btn btn-ghost" @click="showModal = false">Cancelar</button>
                    <button class="btn btn-primary" @click="showModal = false">Aceptar</button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function filterUserRows() {
        const root = document.querySelector('[x-data*="activeTab"]');
        const searchInput = root ? root.querySelector('input[x-model="search"]') : null;
        const term = searchInput ? searchInput.value.trim().toLowerCase() : '';

        document.querySelectorAll('[data-user-row]').forEach((row) => {
            const haystack = row.getAttribute('data-user-search') || '';
            row.style.display = haystack.includes(term) ? '' : 'none';
        });
    }

    async function postUserAction(url, btn, confirmText) {
        if (confirmText && !confirm(confirmText)) return;

        const original = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" style="width:13px;height:13px;animation:spin 0.8s linear infinite;"></i>';
            lucide.createIcons();
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo completar la accion.');
            }

            const target = document.getElementById('main-view');
            const reload = await fetch('/dashboard/users', {
                headers: { 'HX-Request': 'true', 'X-Requested-With': 'XMLHttpRequest' }
            });
            target.innerHTML = await reload.text();
            target.querySelectorAll('[x-data]').forEach(el => {
                if (!el._x_dataStack) Alpine.initTree(el);
            });
            lucide.createIcons();
        } catch (error) {
            alert(error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
                lucide.createIcons();
            }
        }
    }

    function toggleUserActive(userId, btn) {
        postUserAction('/dashboard/users/toggle-active/' + userId, btn, 'Confirmar cambio de estado del usuario?');
    }

    function lockUser(userId, btn) {
        postUserAction('/dashboard/users/lock/' + userId, btn, 'Bloquear este usuario por 24 horas?');
    }

    function unlockUser(userId, btn) {
        postUserAction('/dashboard/users/unlock/' + userId, btn, 'Desbloquear este usuario?');
    }
</script>
