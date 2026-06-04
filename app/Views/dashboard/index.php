<?= $this->extend($layout ?? 'layouts/main') ?>

<?= $this->section('content') ?>
<?php if (($activePage ?? 'overview') === 'overview'): ?>
<div style="animation: fadeSlide 0.4s ease-out;">

    <!-- Page Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.75rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.25rem;">
                Bienvenido, <?= esc(session()->get('username') ?? 'Usuario') ?> 👋
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Aquí tienes un resumen de la actividad de la plataforma.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-ghost" style="display:flex;align-items:center;gap:0.4rem;">
                <i data-lucide="download" style="width:16px;height:16px;"></i> Exportar
            </button>
            <button class="btn btn-primary" style="display:flex;align-items:center;gap:0.4rem;">
                <i data-lucide="plus" style="width:16px;height:16px;"></i> Nuevo Evento
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">

        <!-- Stat: Revenue -->
        <div class="glass-card" x-data="{ count: 0 }" x-init="let target = <?= (int)($totalRevenue ?? 0) ?>;
            let step = Math.ceil(target / 40) || 1;
            let interval = setInterval(() => { count += step; if (count >= target) { count = target; clearInterval(interval); } }, 30);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Ingresos Totales</span>
                <div style="width: 36px; height: 36px; border-radius: 0.5rem; background: rgba(52, 211, 153, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="trending-up" style="width:18px; height:18px; color: var(--accent-emerald);"></i>
                </div>
            </div>
            <div style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; font-family: 'Outfit', sans-serif;">
                <span x-text="count.toLocaleString()">0</span> K
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
                <span style="font-size: 0.75rem; font-weight: 600; color: var(--accent-emerald); background: rgba(52, 211, 153, 0.1); padding: 0.15rem 0.5rem; border-radius: 9999px;">Estable</span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">según depósitos</span>
            </div>
        </div>

        <!-- Stat: Users -->
        <div class="glass-card" x-data="{ count: 0 }" x-init="let target = <?= (int)($activeUsers ?? 0) ?>;
            let step = Math.ceil(target / 40) || 1;
            let interval = setInterval(() => { count += step; if (count >= target) { count = target; clearInterval(interval); } }, 30);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Usuarios Activos</span>
                <div style="width: 36px; height: 36px; border-radius: 0.5rem; background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="users" style="width:18px; height:18px; color: var(--primary);"></i>
                </div>
            </div>
            <div style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; font-family: 'Outfit', sans-serif;">
                <span x-text="count.toLocaleString()">0</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
                <span style="font-size: 0.75rem; font-weight: 600; color: var(--accent-emerald); background: rgba(52, 211, 153, 0.1); padding: 0.15rem 0.5rem; border-radius: 9999px;">En línea</span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">registrados activos</span>
            </div>
        </div>

        <!-- Stat: Events -->
        <div class="glass-card" x-data="{ count: 0 }" x-init="let target = <?= (int)($activeEvents ?? 0) ?>;
            let step = Math.ceil(target / 40) || 1;
            let interval = setInterval(() => { count += step; if (count >= target) { count = target; clearInterval(interval); } }, 30);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Eventos Activos</span>
                <div style="width: 36px; height: 36px; border-radius: 0.5rem; background: rgba(34, 211, 238, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="trophy" style="width:18px; height:18px; color: var(--accent-cyan);"></i>
                </div>
            </div>
            <div style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; font-family: 'Outfit', sans-serif;">
                <span x-text="count.toLocaleString()">0</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
                <span style="font-size: 0.75rem; font-weight: 600; color: var(--accent-cyan); background: rgba(34, 211, 238, 0.1); padding: 0.15rem 0.5rem; border-radius: 9999px;">Disponibles</span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">pendientes o en vivo</span>
            </div>
        </div>

        <!-- Stat: Uptime -->
        <div class="glass-card" x-data="{ count: 0 }" x-init="let target = <?= (float)($systemUptime ?? 99.98) ?>;
            let step = target / 40;
            let interval = setInterval(() => { count += step; if (count >= target) { count = count = target; clearInterval(interval); } }, 30);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">Uptime Sistema</span>
                <div style="width: 36px; height: 36px; border-radius: 0.5rem; background: rgba(251, 191, 36, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="activity" style="width:18px; height:18px; color: var(--accent-amber);"></i>
                </div>
            </div>
            <div style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; font-family: 'Outfit', sans-serif;">
                <span x-text="count.toFixed(2)">0</span>%
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
                <span style="font-size: 0.75rem; font-weight: 600; color: var(--accent-emerald); background: rgba(52, 211, 153, 0.1); padding: 0.15rem 0.5rem; border-radius: 9999px;">Estable</span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">últimos 30 días</span>
            </div>
        </div>
    </div>

    <!-- Bottom Grid: Activity + Quick Stats -->
    <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 1rem;">

        <!-- Activity Feed -->
        <div class="glass-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 700;">Actividad Reciente</h3>
                <button class="btn btn-ghost" style="font-size:0.75rem; padding: 0.35rem 0.75rem;">Ver todo</button>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php if (empty($activities)): ?>
                <div style="padding: 2rem 0; color: var(--text-muted); font-size: 0.85rem; text-align: center;">No hay actividad reciente registrada.</div>
                <?php else: ?>
                <?php foreach ($activities as $a): ?>
                <div style="display: flex; align-items: center; gap: 0.85rem; padding: 0.65rem 0; border-bottom: 1px solid var(--border);">
                    <div style="width: 36px; height: 36px; border-radius: 0.5rem; background: <?= $a['bg'] ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="<?= $a['icon'] ?>" style="width:16px; height:16px; color: <?= $a['color'] ?>;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 0.825rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $a['title'] ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);"><?= $a['time'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- System Status -->
            <div class="glass-card">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Estado del Sistema</h3>
                <?php
                $services = [
                    ['name' => 'API Gateway', 'status' => 'Operativo', 'color' => 'var(--accent-emerald)'],
                    ['name' => 'Base de Datos', 'status' => $dbStatus ?? 'Operativo', 'color' => $dbColor ?? 'var(--accent-emerald)'],
                    ['name' => 'Cache Redis', 'status' => $redisStatus ?? 'Desconectado', 'color' => $redisColor ?? 'var(--accent-amber)'],
                    ['name' => 'Queue Workers', 'status' => $queueStatus ?? 'Operativo', 'color' => $queueColor ?? 'var(--accent-emerald)'],
                ];
                foreach ($services as $s): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0; border-bottom: 1px solid var(--border);">
                    <span style="font-size: 0.825rem; font-weight: 500;"><?= $s['name'] ?></span>
                    <span style="font-size: 0.7rem; font-weight: 600; color: <?= $s['color'] ?>; display: flex; align-items: center; gap: 0.35rem;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: <?= $s['color'] ?>;"></span>
                        <?= $s['status'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card" x-data="{
                async triggerJob(endpoint, btn) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i data-lucide=\'loader\' style=\'width:16px;height:16px;animation:spin 1s linear infinite;\'></i> Procesando...';
                    btn.disabled = true;
                    try {
                        const csrfHeader = document.querySelector('meta[name=\'csrf-token-name\']')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name=\'csrf-token\']')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch(endpoint, { method: 'POST', headers });
                        const result = await res.json();
                        alert(result.message || 'Operación completada.');
                    } catch(e) {
                        alert('Error de conexión.');
                    } finally {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        lucide.createIcons();
                    }
                }
            }">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Acciones Rápidas (API)</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-ghost" style="width:100%; justify-content:flex-start; display:flex; align-items:center; gap:0.6rem; text-align:left;"
                        @click="triggerJob('/dashboard/jobs/fetch-fixtures', $event.currentTarget)">
                        <i data-lucide="download-cloud" style="width:16px;height:16px;color:var(--primary);"></i>
                        Descargar Nuevos Partidos
                    </button>
                    <button class="btn btn-ghost" style="width:100%; justify-content:flex-start; display:flex; align-items:center; gap:0.6rem; text-align:left;"
                        @click="triggerJob('/dashboard/jobs/fetch-odds', $event.currentTarget)">
                        <i data-lucide="refresh-cw" style="width:16px;height:16px;color:var(--accent-cyan);"></i>
                        Sincronizar Cuotas (Odds)
                    </button>
                    <button class="btn btn-ghost" style="width:100%; justify-content:flex-start; display:flex; align-items:center; gap:0.6rem; text-align:left;"
                        @click="triggerJob('/dashboard/jobs/settle', $event.currentTarget)">
                        <i data-lucide="check-circle" style="width:16px;height:16px;color:var(--accent-emerald);"></i>
                        Liquidar Apuestas (Settlement)
                    </button>
                    <button class="btn btn-ghost" style="width:100%; justify-content:flex-start; display:flex; align-items:center; gap:0.6rem; text-align:left; color: #d946ef;"
                        @click="triggerJob('/dashboard/jobs/start-websocket', $event.currentTarget)">
                        <i data-lucide="radio" style="width:16px;height:16px;color:#d946ef;"></i>
                        Iniciar WebSocket Server
                    </button>
                    <button class="btn btn-ghost" style="width:100%; justify-content:flex-start; display:flex; align-items:center; gap:0.6rem; text-align:left; color: #eab308;"
                        @click="triggerJob('/dashboard/jobs/trigger-b2b-mock', $event.currentTarget)">
                        <i data-lucide="zap" style="width:16px;height:16px;color:#eab308;"></i>
                        Simular Cuota B2B (Python)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <?= $this->include('dashboard/partials/' . $activePage) ?>
<?php endif; ?>
<?= $this->endSection() ?>
