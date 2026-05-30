<div style="animation: fadeSlide 0.4s ease-out;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.75rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">Analíticas</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Métricas en tiempo real de la plataforma.</p>
        </div>
    </div>

    <!-- Analytics Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="glass-card">
            <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Apuestas Hoy</span>
            <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0.5rem 0;">
                <?= number_format($betsToday ?? 0) ?>
            </div>
            <div style="height: 4px; background: var(--border); border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; width: <?= (int)($betsPercentage ?? 0) ?>%; background: linear-gradient(90deg, var(--primary), var(--accent-cyan)); border-radius: 2px;"></div>
            </div>
            <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                <?= (int)($betsPercentage ?? 0) ?>% del objetivo diario (100)
            </span>
        </div>
        <div class="glass-card">
            <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Volumen (K)</span>
            <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0.5rem 0;">
                <?= number_format($volumeToday ?? 0, 2, ',', '.') ?> K
            </div>
            <div style="height: 4px; background: var(--border); border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; width: <?= (int)($volumePercentage ?? 0) ?>%; background: linear-gradient(90deg, var(--accent-emerald), var(--accent-cyan)); border-radius: 2px;"></div>
            </div>
            <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                <?= (int)($volumePercentage ?? 0) ?>% del objetivo diario (5.000 K)
            </span>
        </div>
        <div class="glass-card">
            <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Margen Promedio (Hold)</span>
            <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; margin: 0.5rem 0;">
                <?= number_format($holdMargin ?? 0.0, 1, ',', '.') ?>%
            </div>
            <div style="height: 4px; background: var(--border); border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; width: <?= min(100, (int)($holdMargin ?? 0)) ?>%; background: linear-gradient(90deg, var(--accent-amber), var(--accent-rose)); border-radius: 2px;"></div>
            </div>
            <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                Objetivo: 7.5%
            </span>
        </div>
    </div>

    <?php
        $money = static fn ($value): string => number_format((float) $value, 2, ',', '.') . ' K';
    ?>

    <!-- Risk Backoffice -->
    <div style="margin-bottom: 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.85rem;">
            <div>
                <h2 style="font-family:'Outfit',sans-serif;font-size:1.25rem;font-weight:800;">Riesgo Operativo</h2>
                <p style="color:var(--text-muted);font-size:0.82rem;">Exposicion pendiente por tickets abiertos.</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0.8rem;margin-bottom:1rem;">
            <div class="glass-card">
                <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Tickets Pendientes</span>
                <div style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:850;margin-top:0.4rem;"><?= number_format($riskOverview['pending_tickets'] ?? 0) ?></div>
            </div>
            <div class="glass-card">
                <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Stake Pendiente</span>
                <div style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:850;margin-top:0.4rem;color:var(--accent-emerald);"><?= $money($riskOverview['pending_stake'] ?? 0) ?></div>
            </div>
            <div class="glass-card">
                <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Payout Potencial</span>
                <div style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:850;margin-top:0.4rem;color:var(--accent-amber);"><?= $money($riskOverview['pending_payout'] ?? 0) ?></div>
            </div>
            <div class="glass-card">
                <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Multiplicador Riesgo</span>
                <div style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:850;margin-top:0.4rem;color:var(--primary);"><?= number_format((float)($riskOverview['risk_multiple'] ?? 0), 2, ',', '.') ?>x</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div class="glass-card" style="padding:0;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);font-weight:800;">Eventos mas expuestos</div>
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">
                            <th style="padding:0.65rem 1rem;">Evento</th>
                            <th style="padding:0.65rem 1rem;text-align:right;">Tickets</th>
                            <th style="padding:0.65rem 1rem;text-align:right;">Exposicion</th>
                            <th style="padding:0.65rem 1rem;text-align:right;">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riskEvents)): ?>
                            <tr><td colspan="4" style="padding:1rem;text-align:center;color:var(--text-muted);">Sin exposicion pendiente.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($riskEvents as $row): ?>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.72rem 1rem;">
                                    <div style="font-weight:750;"><?= esc($row['home_team'] . ' vs ' . $row['away_team']) ?></div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($row['league_name']) ?></div>
                                </td>
                                <td style="padding:0.72rem 1rem;text-align:right;"><?= number_format((int)$row['tickets']) ?></td>
                                <td style="padding:0.72rem 1rem;text-align:right;color:var(--accent-amber);font-weight:850;"><?= $money($row['exposure']) ?></td>
                                <td style="padding:0.72rem 1rem;text-align:right;">
                                    <button onclick="suspendRiskEvent(<?= (int) $row['event_id'] ?>, this)" style="border:none;border-radius:6px;background:rgba(239,68,68,0.14);color:#fca5a5;padding:0.34rem 0.55rem;font-size:0.72rem;font-weight:850;cursor:pointer;">Suspender</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="glass-card" style="padding:0;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);font-weight:800;">Mercados mas peligrosos</div>
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">
                            <th style="padding:0.65rem 1rem;">Mercado</th>
                            <th style="padding:0.65rem 1rem;text-align:right;">Tickets</th>
                            <th style="padding:0.65rem 1rem;text-align:right;">Exposicion</th>
                            <th style="padding:0.65rem 1rem;text-align:right;">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riskMarkets)): ?>
                            <tr><td colspan="4" style="padding:1rem;text-align:center;color:var(--text-muted);">Sin mercados pendientes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($riskMarkets as $row): ?>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.72rem 1rem;">
                                    <div style="font-weight:750;"><?= esc($row['market_name']) ?></div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($row['home_team'] . ' vs ' . $row['away_team']) ?></div>
                                </td>
                                <td style="padding:0.72rem 1rem;text-align:right;"><?= number_format((int)$row['tickets']) ?></td>
                                <td style="padding:0.72rem 1rem;text-align:right;color:var(--accent-amber);font-weight:850;"><?= $money($row['exposure']) ?></td>
                                <td style="padding:0.72rem 1rem;text-align:right;">
                                    <button onclick="suspendRiskMarket(<?= (int) $row['market_id'] ?>, this)" style="border:none;border-radius:6px;background:rgba(239,68,68,0.14);color:#fca5a5;padding:0.34rem 0.55rem;font-size:0.72rem;font-weight:850;cursor:pointer;">Suspender</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass-card" style="padding:0;overflow:hidden;margin-bottom:1.5rem;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);font-weight:800;">Usuarios con mayor exposicion pendiente</div>
            <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:0.68rem;">
                        <th style="padding:0.65rem 1rem;">Usuario</th>
                        <th style="padding:0.65rem 1rem;text-align:right;">Tickets</th>
                        <th style="padding:0.65rem 1rem;text-align:right;">Stake</th>
                        <th style="padding:0.65rem 1rem;text-align:right;">Exposicion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riskUsers)): ?>
                        <tr><td colspan="4" style="padding:1rem;text-align:center;color:var(--text-muted);">Sin usuarios con tickets pendientes.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($riskUsers as $row): ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:0.72rem 1rem;">
                                <div style="font-weight:800;"><?= esc($row['username']) ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($row['email']) ?></div>
                            </td>
                            <td style="padding:0.72rem 1rem;text-align:right;"><?= number_format((int)$row['tickets']) ?></td>
                            <td style="padding:0.72rem 1rem;text-align:right;color:var(--accent-emerald);font-weight:800;"><?= $money($row['stake']) ?></td>
                            <td style="padding:0.72rem 1rem;text-align:right;color:var(--accent-amber);font-weight:850;"><?= $money($row['exposure']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Events Table -->
    <div class="glass-card">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Top Eventos por Volumen</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 0.6rem 0.75rem; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Evento</th>
                    <th style="padding: 0.6rem 0.75rem; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Apuestas</th>
                    <th style="padding: 0.6rem 0.75rem; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Volumen</th>
                    <th style="padding: 0.6rem 0.75rem; color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($formattedEvents)): ?>
                <tr>
                    <td colspan="4" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                        No hay eventos registrados en el sistema.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($formattedEvents as $e): ?>
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s;" onmouseover="this.style.background='var(--surface-hover)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 0.7rem 0.75rem; font-weight: 500;"><?= esc($e['name']) ?></td>
                    <td style="padding: 0.7rem 0.75rem; color: var(--text-secondary);"><?= esc($e['tickets']) ?></td>
                    <td style="padding: 0.7rem 0.75rem; font-weight: 600; color: var(--accent-emerald);"><?= esc($e['volume']) ?></td>
                    <td style="padding: 0.7rem 0.75rem;">
                        <span style="font-size: 0.7rem; font-weight: 600; color: <?= esc($e['status_color']) ?>; background: <?= str_replace(')', ',0.1)', esc($e['status_color'])) ?>; padding: 0.2rem 0.6rem; border-radius: 9999px;"><?= esc($e['status_text']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    async function riskPost(url, btn, successText) {
        const original = btn ? btn.innerText : '';
        if (btn) {
            btn.disabled = true;
            btn.innerText = '...';
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await response.json();

            if (result.status !== 'success') {
                alert(result.message || 'No se pudo completar la accion.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = original;
                }
                return;
            }

            if (btn) {
                btn.innerText = successText;
                btn.style.opacity = '0.6';
            }

            if (typeof loadView === 'function') {
                setTimeout(() => loadView('/dashboard/analytics', 'analytics'), 650);
            }
        } catch (e) {
            console.error(e);
            alert('Error ejecutando la accion de riesgo.');
            if (btn) {
                btn.disabled = false;
                btn.innerText = original;
            }
        }
    }

    async function suspendRiskEvent(eventId, btn) {
        if (!confirm('Suspender todos los mercados abiertos de este evento?')) return;
        await riskPost('/dashboard/events/suspend-markets/' + eventId, btn, 'Suspendido');
    }

    async function suspendRiskMarket(marketId, btn) {
        if (!confirm('Suspender este mercado?')) return;
        await riskPost('/dashboard/markets/toggle/' + marketId, btn, 'Suspendido');
    }
</script>
