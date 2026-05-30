<?php
    $tickets = $tickets ?? [];
    $summary = $summary ?? [];
    $status = $status ?? 'all';
    $search = $search ?? '';
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $totalTickets = $totalTickets ?? count($tickets);

    $money = static fn ($value): string => '$' . number_format((float) $value, 2, ',', '.');
    $statusText = static fn (string $value): string => match ($value) {
        'pending' => 'Pendiente',
        'won' => 'Ganada',
        'lost' => 'Perdida',
        'void' => 'Anulada',
        'cashed_out' => 'Cash out',
        default => ucfirst($value),
    };
    $statusColor = static fn (string $value): string => match ($value) {
        'won' => 'var(--accent-emerald)',
        'lost' => 'var(--accent-rose)',
        'void', 'cashed_out' => 'var(--text-muted)',
        default => 'var(--accent-amber)',
    };
    $buildUrl = static function (array $overrides = []) use ($status, $search): string {
        $params = array_merge(['status' => $status, 'q' => $search], $overrides);
        $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
        return '/dashboard/bets' . ($params ? '?' . http_build_query($params) : '');
    };
?>

<div style="animation: fadeSlide 0.4s ease-out;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
        <div>
            <h1 style="font-family:Outfit,sans-serif;font-size:1.7rem;font-weight:900;">Apuestas</h1>
            <p style="color:var(--text-muted);font-size:0.9rem;">Monitoreo de tickets, selecciones, exposicion y resultado por usuario.</p>
        </div>
        <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;justify-content:flex-end;">
            <div style="color:var(--text-muted);font-size:0.82rem;font-weight:800;"><?= (int) $totalTickets ?> tickets encontrados</div>
            <a class="btn btn-ghost" href="<?= esc(str_replace('/dashboard/bets', '/dashboard/bets/export', $buildUrl(['page' => null]))) ?>" style="display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none;">
                <i data-lucide="download" style="width:15px;height:15px;"></i>
                Exportar CSV
            </a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0.8rem;margin-bottom:1rem;">
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Tickets totales</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.55rem;font-weight:900;margin-top:0.25rem;"><?= (int) ($summary['total_tickets'] ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Apostado total</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.55rem;font-weight:900;margin-top:0.25rem;color:var(--accent-amber);"><?= $money($summary['total_stake'] ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Exposicion pendiente</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.55rem;font-weight:900;margin-top:0.25rem;color:var(--accent-rose);"><?= $money($summary['pending_exposure'] ?? 0) ?></div>
        </div>
        <div class="glass-card" style="padding:1rem;">
            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Premios pagados</div>
            <div style="font-family:Outfit,sans-serif;font-size:1.55rem;font-weight:900;margin-top:0.25rem;color:var(--accent-emerald);"><?= $money($summary['paid_payout'] ?? 0) ?></div>
        </div>
    </div>

    <div class="glass-card" style="padding:0.85rem 1rem;margin-bottom:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
            <div style="display:flex;gap:0.45rem;flex-wrap:wrap;">
                <?php foreach (['all' => 'Todos', 'pending' => 'Pendientes', 'won' => 'Ganadas', 'lost' => 'Perdidas', 'void' => 'Anuladas'] as $value => $label): ?>
                    <button class="btn <?= $status === $value ? 'btn-primary' : 'btn-ghost' ?>" style="padding:0.42rem 0.75rem;font-size:0.78rem;" onclick="loadView('<?= esc($buildUrl(['status' => $value, 'page' => 1])) ?>', 'bets')">
                        <?= esc($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <form onsubmit="event.preventDefault(); filterAdminBets(this);" style="display:flex;align-items:center;gap:0.5rem;min-width:min(100%,360px);">
                <?php if ($status !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= esc($status) ?>">
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:0.5rem;flex:1;background:var(--surface-hover);border:1px solid var(--border);border-radius:0.6rem;padding:0.45rem 0.65rem;">
                    <i data-lucide="search" style="width:16px;height:16px;color:var(--text-muted);"></i>
                    <input name="q" value="<?= esc($search) ?>" placeholder="Ticket, usuario, equipo, liga o mercado" style="background:none;border:0;outline:0;color:var(--text-primary);width:100%;font:inherit;font-size:0.84rem;">
                </div>
                <button class="btn btn-primary" style="padding:0.5rem 0.8rem;">Buscar</button>
            </form>
        </div>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="glass-card" style="padding:3rem;text-align:center;color:var(--text-muted);">
            <i data-lucide="ticket-x" style="width:44px;height:44px;margin-bottom:0.75rem;opacity:0.55;"></i>
            <div style="font-weight:900;color:var(--text-secondary);">No hay tickets para estos filtros.</div>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0.8rem;">
            <?php foreach ($tickets as $ticket): ?>
                <?php
                    $ticketStatus = (string) ($ticket['status'] ?? 'pending');
                    $ticketType = ((int) ($ticket['selection_count'] ?? 0)) > 1 ? 'Combinada' : 'Simple';
                    $color = $statusColor($ticketStatus);
                ?>
                <div class="glass-card" style="padding:0;overflow:hidden;">
                    <div style="display:grid;grid-template-columns:1.2fr 0.9fr 0.8fr 0.8fr auto;gap:0.75rem;align-items:center;padding:1rem 1.1rem;border-bottom:1px solid var(--border);">
                        <div>
                            <div style="font-family:Outfit,sans-serif;font-size:1.05rem;font-weight:900;">Ticket #<?= str_pad((string) $ticket['id'], 6, '0', STR_PAD_LEFT) ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= esc($ticketType) ?> - <?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '-' ?></div>
                        </div>
                        <div>
                            <div style="font-weight:900;"><?= esc($ticket['username'] ?? '-') ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);"><?= esc($ticket['email'] ?? '') ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Importe</div>
                            <div style="font-weight:900;"><?= $money($ticket['stake'] ?? 0) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:900;text-transform:uppercase;">Pago potencial</div>
                            <div style="font-weight:900;color:<?= $ticketStatus === 'pending' ? 'var(--accent-rose)' : 'var(--text-primary)' ?>;"><?= $money($ticket['potential_payout'] ?? 0) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="display:flex;justify-content:flex-end;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                                <a class="btn btn-ghost" href="/dashboard/bets/ticket/<?= (int) $ticket['id'] ?>" target="_blank" rel="noopener" style="padding:0.32rem 0.55rem;font-size:0.72rem;display:inline-flex;align-items:center;gap:0.3rem;text-decoration:none;">
                                    <i data-lucide="printer" style="width:13px;height:13px;"></i> 80mm
                                </a>
                                <a class="btn btn-ghost" href="/dashboard/bets/ticket/<?= (int) $ticket['id'] ?>/pdf" style="padding:0.32rem 0.55rem;font-size:0.72rem;display:inline-flex;align-items:center;gap:0.3rem;text-decoration:none;">
                                    <i data-lucide="file-down" style="width:13px;height:13px;"></i> PDF
                                </a>
                                <?php if ($ticketStatus === 'pending'): ?>
                                    <button class="btn btn-danger" onclick="voidAdminBet(<?= (int) $ticket['id'] ?>, this)" style="padding:0.32rem 0.55rem;font-size:0.72rem;display:inline-flex;align-items:center;gap:0.3rem;">
                                        <i data-lucide="ban" style="width:13px;height:13px;"></i> Anular
                                    </button>
                                <?php endif; ?>
                                <span style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid <?= $color ?>55;background:<?= $color ?>18;color:<?= $color ?>;border-radius:999px;padding:0.25rem 0.65rem;font-size:0.72rem;font-weight:900;text-transform:uppercase;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:<?= $color ?>;"></span>
                                    <?= esc($statusText($ticketStatus)) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div style="padding:0.4rem 1.1rem 0.9rem;">
                        <?php foreach (($ticket['selections'] ?? []) as $selection): ?>
                            <?php $selectionColor = $statusColor((string) ($selection['selection_status'] ?? 'pending')); ?>
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:0.8rem;align-items:center;padding:0.65rem 0;border-bottom:1px dashed var(--border);">
                                <div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);font-weight:800;text-transform:uppercase;"><?= esc($selection['league_name'] ?? '-') ?></div>
                                    <div style="font-weight:900;"><?= esc($selection['home_team'] ?? '-') ?> vs <?= esc($selection['away_team'] ?? '-') ?></div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);">
                                        <?= esc($selection['market_name'] ?? '-') ?>
                                        <?php if (!empty($selection['start_time'])): ?>
                                            - <?= date('d/m/Y H:i', strtotime($selection['start_time'])) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($selection['venue'])): ?>
                                            - <?= esc($selection['venue']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-weight:900;color:var(--accent-cyan);"><?= esc($selection['odd_name'] ?? '-') ?> <span style="color:var(--text-primary);background:var(--surface-hover);border-radius:0.35rem;padding:0.15rem 0.45rem;"><?= number_format((float) ($selection['odd_at_bet_time'] ?? 0), 2) ?></span></div>
                                    <div style="font-size:0.72rem;font-weight:900;color:<?= $selectionColor ?>;margin-top:0.2rem;text-transform:uppercase;"><?= esc($statusText((string) ($selection['selection_status'] ?? 'pending'))) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display:flex;justify-content:center;align-items:center;gap:0.75rem;margin-top:1rem;color:var(--text-muted);font-weight:800;">
            <?php if ($currentPage > 1): ?>
                <button class="btn btn-ghost" onclick="loadView('<?= esc($buildUrl(['page' => $currentPage - 1])) ?>', 'bets')">Anterior</button>
            <?php endif; ?>
            <span>Pagina <?= (int) $currentPage ?> / <?= (int) $totalPages ?></span>
            <?php if ($currentPage < $totalPages): ?>
                <button class="btn btn-ghost" onclick="loadView('<?= esc($buildUrl(['page' => $currentPage + 1])) ?>', 'bets')">Siguiente</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function filterAdminBets(form) {
        const params = new URLSearchParams(new FormData(form));
        params.set('page', '1');
        const query = params.toString();
        loadView('/dashboard/bets' + (query ? '?' + query : ''), 'bets');
    }

    async function voidAdminBet(ticketId, btn) {
        const reason = prompt('Motivo de anulacion del ticket #' + ticketId + ':', 'Anulacion administrativa');
        if (reason === null) return;

        const original = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" style="width:13px;height:13px;animation:spin 0.8s linear infinite;"></i>';
            lucide.createIcons();
        }

        try {
            const body = new FormData();
            body.append('reason', reason);

            const response = await fetch('/dashboard/bets/void/' + ticketId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                },
                body
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo anular el ticket.');
            }

            loadView('/dashboard/bets', 'bets');
        } catch (error) {
            alert(error.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
                lucide.createIcons();
            }
        }
    }
</script>
