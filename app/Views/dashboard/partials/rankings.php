<div style="animation: fadeSlide 0.4s ease-out;">
    <style>
        .rankings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .rankings-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <?php $medals = ['#1', '#2', '#3']; ?>
    <div style="margin-bottom: 1.75rem;">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800;">Rankings & Estadísticas</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Leaderboards calculados con tickets, usuarios y eventos reales.</p>
    </div>

    <!-- Ranking Cards Row -->
    <div class="rankings-grid" style="margin-bottom: 1.5rem;">

        <!-- Top Apostadores -->
        <div class="glass-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                <h3 style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, var(--primary), var(--accent-cyan)); display: flex; align-items: center; justify-content: center;">🏆</span>
                    Top Apostadores
                </h3>
                <span style="font-size: 0.7rem; font-weight: 600; color: var(--primary); background: var(--primary-muted); padding: 0.2rem 0.6rem; border-radius: 9999px;">DB: bet_slips.stake</span>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <tbody>
                    <?php if (empty($topBettors)): ?>
                    <tr>
                        <td colspan="3" style="padding: 1.25rem; color: var(--text-muted); text-align: center;">Todavia no hay apuestas registradas.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($topBettors as $entry): ?>
                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s;" onmouseover="this.style.background='var(--surface-hover)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 0.6rem 1.25rem; width: 40px;">
                            <?php
                            echo $entry['rank'] <= 3
                                ? '<span style="font-size: 1.2rem;">' . $medals[$entry['rank'] - 1] . '</span>'
                                : '<span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">#' . $entry['rank'] . '</span>';
                            ?>
                        </td>
                        <td style="padding: 0.6rem 0.5rem; font-weight: 600;"><?= esc($entry['member']) ?></td>
                        <td style="padding: 0.6rem 1.25rem; text-align: right; font-weight: 700; color: var(--accent-emerald); font-family: 'Outfit', sans-serif;"><?= number_format($entry['score'], 2) ?> K</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Ganadores -->
        <div class="glass-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                <h3 style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, var(--accent-emerald), var(--accent-cyan)); display: flex; align-items: center; justify-content: center;">💰</span>
                    Top Ganadores (Profit)
                </h3>
                <span style="font-size: 0.7rem; font-weight: 600; color: var(--accent-emerald); background: rgba(16,185,129,0.1); padding: 0.2rem 0.6rem; border-radius: 9999px;">DB: profit neto</span>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <tbody>
                    <?php if (empty($topWinners)): ?>
                    <tr>
                        <td colspan="3" style="padding: 1.25rem; color: var(--text-muted); text-align: center;">Todavia no hay usuarios con profit calculable.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($topWinners as $entry): ?>
                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s;" onmouseover="this.style.background='var(--surface-hover)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 0.6rem 1.25rem; width: 40px;">
                            <?php
                            echo $entry['rank'] <= 3
                                ? '<span style="font-size: 1.2rem;">' . $medals[$entry['rank'] - 1] . '</span>'
                                : '<span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">#' . $entry['rank'] . '</span>';
                            ?>
                        </td>
                        <td style="padding: 0.6rem 0.5rem; font-weight: 600;"><?= esc($entry['member']) ?></td>
                        <td style="padding: 0.6rem 1.25rem; text-align: right; font-weight: 700; font-family: 'Outfit', sans-serif; color: <?= $entry['score'] >= 0 ? 'var(--accent-emerald)' : 'var(--accent-rose)' ?>;">
                            <?= $entry['score'] >= 0 ? '+' : '' ?><?= number_format($entry['score'], 2) ?> K
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hot Events + Cache Stats -->
    <div class="rankings-grid">

        <!-- Hot Events -->
        <div class="glass-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                <h3 style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, var(--accent-amber), var(--accent-rose)); display: flex; align-items: center; justify-content: center;">🔥</span>
                    Eventos más apostados
                </h3>
                <span style="font-size: 0.7rem; font-weight: 600; color: var(--accent-amber); background: rgba(245,158,11,0.1); padding: 0.2rem 0.6rem; border-radius: 9999px;">DB: volumen evento</span>
            </div>
            <?php if (empty($hotEvents)): ?>
            <div style="padding: 1.25rem; color: var(--text-muted); text-align: center;">Todavia no hay eventos apostados.</div>
            <?php endif; ?>
            <?php foreach ($hotEvents as $entry): ?>
            <div style="padding: 0.65rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); width: 24px;">#<?= $entry['rank'] ?></span>
                <div style="flex: 1;">
                    <div style="font-size: 0.85rem; font-weight: 600;"><?= esc($entry['member']) ?></div>
                    <div style="height: 4px; background: var(--border); border-radius: 2px; margin-top: 0.35rem; overflow: hidden;">
                        <?php $pct = ($entry['score'] / ($hotEvents[0]['score'] ?: 1)) * 100; ?>
                        <div style="height: 100%; width: <?= $pct ?>%; background: linear-gradient(90deg, var(--accent-amber), var(--accent-rose)); border-radius: 2px;"></div>
                    </div>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--accent-amber);"><?= number_format($entry['score']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Cache & Redis Monitor -->
        <div class="glass-card">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span style="width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, #ef4444, #f97316); display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">⚡</span>
                Cache Monitor
            </h3>

            <!-- Status -->
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem; background: var(--bg-primary); border-radius: 0.5rem;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: <?= $cacheStats['is_redis'] ? 'var(--accent-emerald)' : 'var(--accent-amber)' ?>;"></span>
                <span style="font-size: 0.85rem; font-weight: 600;">Handler: <code style="color: var(--primary);"><?= $cacheStats['handler'] ?></code></span>
                <?php if (!$cacheStats['is_redis']): ?>
                <span style="font-size: 0.65rem; color: var(--text-muted); margin-left: auto;">Redis disponible en producción</span>
                <?php endif; ?>
            </div>

            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="background: var(--bg-primary); padding: 0.75rem; border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: var(--accent-emerald);"><?= $cacheStats['hits'] ?></div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Cache Hits</div>
                </div>
                <div style="background: var(--bg-primary); padding: 0.75rem; border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: var(--accent-rose);"><?= $cacheStats['misses'] ?></div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Cache Misses</div>
                </div>
                <div style="background: var(--bg-primary); padding: 0.75rem; border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: var(--accent-cyan);"><?= $cacheStats['hit_rate'] ?>%</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Hit Rate</div>
                </div>
                <div style="background: var(--bg-primary); padding: 0.75rem; border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: var(--primary);"><?= $cacheStats['keys_count'] ?></div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Keys Stored</div>
                </div>
            </div>

            <!-- Queue Stats -->
            <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
                <i data-lucide="layers" style="width:16px;height:16px;"></i> Queues
            </h4>
            <?php foreach ($queueStats['queues'] as $qName => $qData): ?>
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; border-bottom: 1px solid var(--border); font-size: 0.8rem;">
                <code style="font-weight: 600; width: 130px;"><?= $qName ?></code>
                <span style="color: var(--accent-amber);"><?= $qData['pending'] ?> pendiente<?= $qData['pending'] !== 1 ? 's' : '' ?></span>
                <span style="color: var(--text-muted);">·</span>
                <span style="color: var(--accent-emerald);"><?= $qData['completed'] ?> completado<?= $qData['completed'] !== 1 ? 's' : '' ?></span>
            </div>
            <?php endforeach; ?>
            <div style="margin-top: 0.75rem; display: flex; gap: 0.75rem; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Total procesados: <strong style="color: var(--accent-emerald);"><?= $queueStats['totals']['processed'] ?></strong></span>
                <span style="color: var(--text-muted);">Fallidos: <strong style="color: var(--accent-rose);"><?= $queueStats['totals']['failed'] ?></strong></span>
            </div>
        </div>
    </div>
</div>
