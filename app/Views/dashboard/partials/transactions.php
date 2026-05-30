<?php
    $transactions = $transactions ?? [];
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $totalTransactions = $totalTransactions ?? count($transactions);
    $byUser = $byUser ?? [];
    $byDay = $byDay ?? [];
    $byEvent = $byEvent ?? [];
    $cashierUsers = $cashierUsers ?? [];
    $filters = $filters ?? ['q' => '', 'type' => 'all', 'date_from' => '', 'date_to' => ''];
    $filterParams = array_filter($filters, static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
    $filterQuery = http_build_query($filterParams);
    $transactionsUrl = static function (array $overrides = []) use ($filters): string {
        $params = array_merge($filters, $overrides);
        $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
        return '/dashboard/transactions' . ($params ? '?' . http_build_query($params) : '');
    };

    $money = static fn ($value): string => number_format((float) $value, 2) . ' K';
    $typeLabel = static function (string $type): string {
        return match ($type) {
            'deposit' => 'Recarga / Deposito',
            'withdrawal' => 'Retiro',
            'bet_placed' => 'Apuesta',
            'bet_won' => 'Premio pagado',
            'bet_refunded' => 'Reintegro',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    };
    $typeColor = static function (string $type, float $amount): string {
        if ($type === 'bet_placed' || $type === 'withdrawal' || $amount < 0) {
            return 'var(--accent-rose)';
        }

        if ($type === 'bet_won' || $type === 'deposit' || $type === 'bet_refunded') {
            return 'var(--accent-emerald)';
        }

        return 'var(--accent-cyan)';
    };
?>

<div style="animation: fadeSlide 0.4s ease-out;" x-data="{ reportTab: 'user', showCashier: false, movementType: 'deposit' }">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:1rem;gap:1rem;">
        <div>
            <h1 style="font-family:'Outfit',sans-serif;font-size:1.55rem;font-weight:800;">Transacciones</h1>
            <p style="color:var(--text-muted);font-size:0.9rem;">Historial completo de recargas, apuestas, premios y movimientos de usuarios.</p>
        </div>
        <div style="display:flex;align-items:center;gap:0.6rem;">
            <div style="color:var(--text-muted);font-size:0.82rem;font-weight:700;">
                <?= (int) $totalTransactions ?> movimientos registrados
            </div>
            <a class="btn btn-ghost" href="/dashboard/transactions/export<?= $filterQuery ? '?' . esc($filterQuery) : '' ?>" style="display:flex;align-items:center;gap:0.4rem;text-decoration:none;">
                <i data-lucide="download" style="width:16px;height:16px;"></i>
                Exportar CSV
            </a>
            <button class="btn btn-primary" style="display:flex;align-items:center;gap:0.4rem;" @click="showCashier = true">
                <i data-lucide="wallet" style="width:16px;height:16px;"></i>
                Caja manual
            </button>
        </div>
    </div>

    <div class="glass-card" style="padding:0.85rem 1rem;margin-bottom:1rem;">
        <form onsubmit="event.preventDefault(); const params = new URLSearchParams(new FormData(this)); params.set('page', '1'); [...params.entries()].forEach(([key, value]) => { if (!value || value === 'all') params.delete(key); }); const query = params.toString(); loadView('/dashboard/transactions' + (query ? '?' + query : ''), 'transactions');" style="display:grid;grid-template-columns:1.5fr 0.9fr 0.85fr 0.85fr auto auto;gap:0.65rem;align-items:end;">
            <div>
                <label style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;margin-bottom:0.3rem;">Buscar</label>
                <div style="display:flex;align-items:center;gap:0.5rem;background:var(--surface-hover);border:1px solid var(--border);border-radius:0.6rem;padding:0.5rem 0.65rem;">
                    <i data-lucide="search" style="width:15px;height:15px;color:var(--text-muted);"></i>
                    <input name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Usuario, email, TXN, referencia, descripcion" style="background:none;border:0;outline:0;color:var(--text-primary);width:100%;font:inherit;font-size:0.82rem;">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;margin-bottom:0.3rem;">Tipo</label>
                <select name="type" style="width:100%;background:var(--surface-hover);border:1px solid var(--border);border-radius:0.6rem;color:var(--text-primary);padding:0.55rem 0.65rem;font:inherit;font-size:0.82rem;">
                    <?php foreach (['all' => 'Todos', 'deposit' => 'Recargas', 'withdrawal' => 'Retiros', 'bet_placed' => 'Apuestas', 'bet_won' => 'Premios', 'bet_refunded' => 'Reintegros'] as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= ($filters['type'] ?? 'all') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;margin-bottom:0.3rem;">Desde</label>
                <input type="date" name="date_from" value="<?= esc($filters['date_from'] ?? '') ?>" style="width:100%;background:var(--surface-hover);border:1px solid var(--border);border-radius:0.6rem;color:var(--text-primary);padding:0.52rem 0.65rem;font:inherit;font-size:0.82rem;">
            </div>
            <div>
                <label style="display:block;font-size:0.68rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;margin-bottom:0.3rem;">Hasta</label>
                <input type="date" name="date_to" value="<?= esc($filters['date_to'] ?? '') ?>" style="width:100%;background:var(--surface-hover);border:1px solid var(--border);border-radius:0.6rem;color:var(--text-primary);padding:0.52rem 0.65rem;font:inherit;font-size:0.82rem;">
            </div>
            <button class="btn btn-primary" style="height:2.55rem;">Filtrar</button>
            <button type="button" class="btn btn-ghost" style="height:2.55rem;" onclick="loadView('/dashboard/transactions', 'transactions')">Limpiar</button>
        </form>
    </div>

    <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0.7rem;margin-bottom:0.85rem;">
        <div class="glass-card" style="padding:0.8rem;">
            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Recargas</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.12rem;font-weight:900;margin-top:0.2rem;color:var(--accent-emerald);"><?= $money($totalDeposits ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:0.8rem;">
            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Retiros</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.12rem;font-weight:900;margin-top:0.2rem;color:var(--accent-rose);"><?= $money($totalWithdrawals ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:0.8rem;">
            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Apostado</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.12rem;font-weight:900;margin-top:0.2rem;color:var(--accent-amber);"><?= $money($totalBets ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:0.8rem;">
            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Premios pagados</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.12rem;font-weight:900;margin-top:0.2rem;color:var(--accent-cyan);"><?= $money($totalPayouts ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:0.8rem;">
            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;">Neto casa</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.12rem;font-weight:900;margin-top:0.2rem;color:<?= ($netCollected ?? 0) >= 0 ? 'var(--accent-emerald)' : 'var(--accent-rose)' ?>;"><?= $money($netCollected ?? 0) ?></div>
        </div>
    </div>

    <div class="glass-card" style="padding:0;overflow:hidden;margin-bottom:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.9rem 1.25rem;border-bottom:1px solid var(--border);">
            <div style="font-weight:900;">Todas las transacciones</div>
            <div style="display:flex;gap:0.45rem;align-items:center;">
                <?php if ($currentPage > 1): ?>
                    <button class="btn btn-ghost" onclick="loadView('<?= esc($transactionsUrl(['page' => $currentPage - 1])) ?>', 'transactions')" style="padding:0.38rem 0.7rem;">Anterior</button>
                <?php endif; ?>
                <span style="font-size:0.78rem;color:var(--text-muted);font-weight:800;">Pagina <?= (int) $currentPage ?> / <?= (int) $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <button class="btn btn-ghost" onclick="loadView('<?= esc($transactionsUrl(['page' => $currentPage + 1])) ?>', 'transactions')" style="padding:0.38rem 0.7rem;">Siguiente</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($transactions)): ?>
            <div style="padding:2rem;text-align:center;color:var(--text-muted);">No hay transacciones registradas todavia.</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">ID</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Usuario</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Tipo</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Monto</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Comisión</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Destino</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Saldo posterior</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Referencia</th>
                        <th style="padding:0.85rem 1.25rem;text-align:left;color:var(--text-muted);font-weight:700;font-size:0.75rem;text-transform:uppercase;">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                            $amount = (float) $tx['amount'];
                            $color = $typeColor($tx['type'], $amount);
                            $commissionVal = (float)($tx['commission'] ?? 0);
                            $targetAccountVal = (string)($tx['target_account'] ?? '');
                        ?>
                        <tr style="border-bottom:1px solid var(--border);transition:background 0.15s;" onmouseover="this.style.background='var(--surface-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:0.75rem 1.25rem;font-weight:700;color:var(--text-muted);font-family:monospace;font-size:0.8rem;">TXN-<?= str_pad((string) $tx['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td style="padding:0.75rem 1.25rem;">
                                <div style="font-weight:800;"><?= esc($tx['username'] ?? '-') ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($tx['email'] ?? '') ?></div>
                            </td>
                            <td style="padding:0.75rem 1.25rem;color:var(--text-secondary);"><?= esc($typeLabel($tx['type'])) ?></td>
                            <td style="padding:0.75rem 1.25rem;font-weight:900;color:<?= $color ?>;"><?= $amount >= 0 ? '+' : '-' ?><?= $money(abs($amount)) ?></td>
                            <td style="padding:0.75rem 1.25rem;color:var(--accent-rose);font-weight:600;"><?= $commissionVal > 0 ? $money($commissionVal) : '-' ?></td>
                            <td style="padding:0.75rem 1.25rem;color:var(--text-secondary);font-size:0.8rem;"><?= !empty($targetAccountVal) ? esc($targetAccountVal) : '-' ?></td>
                            <td style="padding:0.75rem 1.25rem;color:var(--text-secondary);"><?= $money($tx['balance_after']) ?></td>
                            <td style="padding:0.75rem 1.25rem;color:var(--text-muted);">
                                <?= !empty($tx['reference_id']) ? '#' . esc($tx['reference_id']) : '-' ?>
                                <?php if (!empty($tx['description'])): ?>
                                    <div style="font-size:0.7rem;max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= esc($tx['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:0.75rem 1.25rem;color:var(--text-muted);font-size:0.8rem;"><?= !empty($tx['created_at']) ? date('d/m/Y H:i', strtotime($tx['created_at'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;">
        <button class="btn" :class="reportTab === 'user' ? 'btn-primary' : 'btn-ghost'" @click="reportTab = 'user'">Reporte por usuario</button>
        <button class="btn" :class="reportTab === 'day' ? 'btn-primary' : 'btn-ghost'" @click="reportTab = 'day'">Reporte por dia</button>
        <button class="btn" :class="reportTab === 'event' ? 'btn-primary' : 'btn-ghost'" @click="reportTab = 'event'">Reporte por partido</button>
    </div>

    <div class="glass-card" style="padding:0;overflow:hidden;" x-show="reportTab === 'user'" x-transition.opacity.duration.180ms>
        <div style="padding:0.9rem 1.25rem;border-bottom:1px solid var(--border);font-weight:900;">Monto por usuario</div>
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
            <thead><tr style="border-bottom:1px solid var(--border);">
                <th style="padding:0.75rem 1.25rem;text-align:left;color:var(--text-muted);">Usuario</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Recargas</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Apostado</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Premios</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Neto</th>
            </tr></thead>
            <tbody>
                <?php foreach ($byUser as $row): ?>
                    <?php $net = (float) $row['bets'] - (float) $row['payouts']; ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:0.75rem 1.25rem;font-weight:800;"><?= esc($row['username']) ?><div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($row['email']) ?></div></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-emerald);font-weight:800;"><?= $money($row['deposits']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-amber);font-weight:800;"><?= $money($row['bets']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-cyan);font-weight:800;"><?= $money($row['payouts']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:<?= $net >= 0 ? 'var(--accent-emerald)' : 'var(--accent-rose)' ?>;font-weight:900;"><?= $money($net) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="glass-card" style="padding:0;overflow:hidden;" x-show="reportTab === 'day'" x-transition.opacity.duration.180ms>
        <div style="padding:0.9rem 1.25rem;border-bottom:1px solid var(--border);font-weight:900;">Monto por dia</div>
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
            <thead><tr style="border-bottom:1px solid var(--border);">
                <th style="padding:0.75rem 1.25rem;text-align:left;color:var(--text-muted);">Dia</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Movimientos</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Recargas</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Apostado</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Premios</th>
            </tr></thead>
            <tbody>
                <?php foreach ($byDay as $row): ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:0.75rem 1.25rem;font-weight:800;"><?= !empty($row['tx_date']) ? date('d/m/Y', strtotime($row['tx_date'])) : '-' ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-secondary);"><?= (int) $row['transaction_count'] ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-emerald);font-weight:800;"><?= $money($row['deposits']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-amber);font-weight:800;"><?= $money($row['bets']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-cyan);font-weight:800;"><?= $money($row['payouts']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="glass-card" style="padding:0;overflow:hidden;" x-show="reportTab === 'event'" x-transition.opacity.duration.180ms>
        <div style="padding:0.9rem 1.25rem;border-bottom:1px solid var(--border);font-weight:900;">Monto por partido</div>
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
            <thead><tr style="border-bottom:1px solid var(--border);">
                <th style="padding:0.75rem 1.25rem;text-align:left;color:var(--text-muted);">Partido</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Tickets</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Selecciones</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Apostado</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Pagado</th>
                <th style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-muted);">Neto</th>
            </tr></thead>
            <tbody>
                <?php foreach ($byEvent as $row): ?>
                    <?php $eventNet = (float) $row['stake_sum'] - (float) $row['payout_sum']; ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:0.75rem 1.25rem;font-weight:800;">
                            <?= esc($row['home_team']) ?> vs <?= esc($row['away_team']) ?>
                            <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($row['league_name']) ?></div>
                        </td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-secondary);"><?= (int) $row['ticket_count'] ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--text-secondary);"><?= (int) $row['selection_count'] ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-amber);font-weight:800;"><?= $money($row['stake_sum']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:var(--accent-cyan);font-weight:800;"><?= $money($row['payout_sum']) ?></td>
                        <td style="padding:0.75rem 1.25rem;text-align:right;color:<?= $eventNet >= 0 ? 'var(--accent-emerald)' : 'var(--accent-rose)' ?>;font-weight:900;"><?= $money($eventNet) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <template x-if="showCashier">
        <div class="modal-backdrop" @click.self="showCashier = false" x-transition>
            <div class="modal-box" x-transition.scale.90 style="max-width:560px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <h3 style="margin:0;">Caja manual</h3>
                        <p style="margin:0.25rem 0 0;color:var(--text-muted);font-size:0.85rem;">Recargue o retire saldo de usuarios apostadores.</p>
                    </div>
                    <button class="btn btn-ghost" style="padding:0.4rem 0.55rem;" @click="showCashier = false">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <form id="cashier-form" onsubmit="submitCashierMovement(event)" style="display:grid;gap:0.85rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;margin-bottom:0.35rem;">Usuario apostador</label>
                        <select name="user_id" required class="settings-input" style="width:100%;">
                            <option value="">Seleccione usuario</option>
                            <?php foreach ($cashierUsers as $user): ?>
                                <option value="<?= (int) $user['id'] ?>">
                                    <?= esc($user['username']) ?> - <?= esc($user['email']) ?> - Saldo <?= $money($user['balance'] ?? 0) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                        <button type="button" class="btn" :class="movementType === 'deposit' ? 'btn-primary' : 'btn-ghost'" @click="movementType = 'deposit'; $refs.movementType.value = 'deposit'" style="justify-content:center;">
                            <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Recarga
                        </button>
                        <button type="button" class="btn" :class="movementType === 'withdrawal' ? 'btn-primary' : 'btn-ghost'" @click="movementType = 'withdrawal'; $refs.movementType.value = 'withdrawal'" style="justify-content:center;">
                            <i data-lucide="minus-circle" style="width:16px;height:16px;"></i> Retiro
                        </button>
                        <input type="hidden" name="type" value="deposit" x-ref="movementType">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                        <div>
                            <label style="display:block;font-size:0.75rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;margin-bottom:0.35rem;">Monto</label>
                            <input type="number" name="amount" min="1" step="0.01" required class="settings-input" placeholder="0.00" style="width:100%;">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;margin-bottom:0.35rem;">Comision</label>
                            <input type="number" name="commission" min="0" step="0.01" class="settings-input" placeholder="0.00" style="width:100%;">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;font-size:0.75rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;margin-bottom:0.35rem;">Cuenta destino / referencia</label>
                        <input type="text" name="target_account" maxlength="120" class="settings-input" placeholder="Alias, CVU, comprobante o referencia interna" style="width:100%;">
                    </div>

                    <div>
                        <label style="display:block;font-size:0.75rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;margin-bottom:0.35rem;">Descripcion</label>
                        <textarea name="description" maxlength="255" class="settings-input" rows="3" placeholder="Motivo del movimiento" style="width:100%;resize:vertical;"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" @click="showCashier = false">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="cashier-submit">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                            Registrar movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    async function submitCashierMovement(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const button = document.getElementById('cashier-submit');
        const original = button ? button.innerHTML : '';

        if (button) {
            button.disabled = true;
            button.innerHTML = '<i data-lucide="loader-2" style="width:16px;height:16px;animation:spin 0.8s linear infinite;"></i> Procesando';
            lucide.createIcons();
        }

        try {
            const response = await fetch('/dashboard/transactions/wallet-adjustment', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                },
                body: new FormData(form)
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo registrar el movimiento.');
            }

            await loadView('/dashboard/transactions', 'transactions');
        } catch (error) {
            alert(error.message);
            if (button) {
                button.disabled = false;
                button.innerHTML = original;
                lucide.createIcons();
            }
        }
    }

    function filterTransactions(form) {
        const params = new URLSearchParams(new FormData(form));
        params.set('page', '1');
        for (const [key, value] of [...params.entries()]) {
            if (!value || value === 'all') params.delete(key);
        }
        const query = params.toString();
        loadView('/dashboard/transactions' + (query ? '?' + query : ''), 'transactions');
    }
</script>
