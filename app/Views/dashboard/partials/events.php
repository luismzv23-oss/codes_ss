<style>
    .events-modal-shell {
        background: var(--bg-panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        width: min(1120px, calc(100vw - 2rem));
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 60px -18px rgba(0,0,0,0.65);
        overflow: hidden;
    }

    .events-modal-header {
        padding: 1.15rem 1.35rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .event-create-panel {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,0.015);
    }

    .event-admin-form {
        margin-top: 0.75rem;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
    }

    .event-admin-form input {
        min-width: 0;
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 6px;
        color: var(--text-primary);
        padding: 0.5rem;
        font-size: 0.78rem;
    }

    .event-admin-card {
        border: 1px solid var(--border);
        border-radius: 10px;
        background: rgba(255,255,255,0.025);
        padding: 0.9rem;
        margin-bottom: 0.75rem;
    }

    .event-admin-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
        gap: 1rem;
    }

    .event-admin-teams {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 0.9rem;
        font-weight: 850;
        font-size: 1.02rem;
    }

    .event-admin-team {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .event-admin-team.away {
        justify-content: flex-end;
        text-align: right;
    }

    .event-admin-team span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-admin-meta {
        margin-top: 0.45rem;
        color: var(--text-muted);
        font-size: 0.78rem;
        line-height: 1.45;
    }

    .event-admin-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
        gap: 0.45rem;
        min-width: 255px;
    }

    .event-admin-score {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.35rem;
    }

    .event-admin-score input {
        width: 46px;
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 5px;
        color: var(--text-primary);
        padding: 0.28rem;
        text-align: center;
        font-weight: 850;
    }

    .event-admin-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.75rem;
    }

    .event-admin-pill {
        font-size: 0.7rem;
        font-weight: 850;
        color: var(--text-muted);
        background: rgba(255,255,255,0.07);
        padding: 0.28rem 0.55rem;
        border-radius: 999px;
    }

    .event-admin-details {
        margin-top: 0.7rem;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 0.65rem;
    }

    .event-admin-details details {
        min-width: 0;
        border-top: 1px solid var(--border);
        padding-top: 0.55rem;
    }

    .event-admin-details summary {
        cursor: pointer;
        color: var(--text-muted);
        font-size: 0.74rem;
        font-weight: 850;
    }

    @media (max-width: 860px) {
        .event-admin-head,
        .event-admin-details,
        .event-admin-form {
            grid-template-columns: 1fr;
        }

        .event-admin-actions {
            justify-content: flex-start;
            min-width: 0;
        }
    }
</style>

<div style="animation: fadeSlide 0.4s ease-out;" x-data="{
    showModal: false,
    leagueId: null,
    leagueName: '',
    eventsHtml: '<div style=\'padding:2rem;text-align:center;\'>Cargando eventos...</div>',
    async openLeague(id, name) {
        this.leagueId = id;
        this.leagueName = name;
        this.showModal = true;
        this.eventsHtml = '<div style=\'padding:2rem;text-align:center;\'>Cargando eventos...</div>';
        try {
            const res = await fetch('/dashboard/events/league/' + id, { headers: {'X-Requested-With': 'XMLHttpRequest'} });
            this.eventsHtml = await res.text();
        } catch (e) {
            this.eventsHtml = '<div style=\'padding:2rem;text-align:center;color:var(--danger);\'>Error al cargar.</div>';
        }
    },
    async generateLeagueMarkets() {
        if (!this.leagueId) return;
        try {
            const res = await fetch('/dashboard/events/league/' + this.leagueId + '/generate-markets', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await res.json();
            alert(result.message || 'Mercados generados.');
            const reload = await fetch('/dashboard/events/league/' + this.leagueId, { headers: {'X-Requested-With': 'XMLHttpRequest'} });
            this.eventsHtml = await reload.text();
        } catch (e) {
            alert('Error al generar mercados del torneo.');
        }
    },
    async fetchScoresManual(btn) {
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = '⏳ Consultando API...';
        try {
            const res = await fetch('/dashboard/events/fetch-scores', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await res.json();
            if (result.status === 'success') {
                let msg = result.message;
                if (result.results && result.results.length > 0) {
                    msg += '\n\n' + result.results.join('\n');
                }
                alert(msg);
                // Recargar los eventos de la liga actual
                if (this.leagueId) {
                    const reload = await fetch('/dashboard/events/league/' + this.leagueId, { headers: {'X-Requested-With': 'XMLHttpRequest'} });
                    this.eventsHtml = await reload.text();
                }
            } else {
                alert(result.message || 'Error al obtener marcadores.');
            }
        } catch (e) {
            alert('Error de conexión al obtener marcadores.');
        } finally {
            btn.disabled = false;
            btn.innerText = original;
        }
    },
    async createEvent(btn) {
        if (!this.leagueId) return;
        const field = (name) => document.getElementById('new-event-' + name)?.value || '';
        const homeTeam = field('home-team').trim();
        const awayTeam = field('away-team').trim();
        const startTime = field('start-time');
        const venue = field('venue').trim();

        if (!homeTeam || !awayTeam || !startTime || !venue) {
            alert('Equipo local, visitante, fecha y estadio son obligatorios.');
            return;
        }

        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Creando...';

        try {
            const body = new FormData();
            body.append('home_team', homeTeam);
            body.append('away_team', awayTeam);
            body.append('home_flag', field('home-flag').trim());
            body.append('away_flag', field('away-flag').trim());
            body.append('stage', field('stage').trim());
            body.append('group_name', field('group').trim());
            body.append('venue', venue);
            body.append('start_time', startTime);
            body.append('match_number', field('match-number'));

            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/events/league/' + this.leagueId + '/create', body)
                : await (await fetch('/dashboard/events/league/' + this.leagueId + '/create', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                    },
                    body
                })).json();

            if (result.status !== 'success') {
                alert(result.message || 'No se pudo crear el partido.');
                btn.disabled = false;
                btn.innerText = original;
                return;
            }

            ['home-team', 'away-team', 'home-flag', 'away-flag', 'stage', 'group', 'venue', 'start-time', 'match-number'].forEach((name) => {
                const input = document.getElementById('new-event-' + name);
                if (input) input.value = '';
            });
            const reload = await fetch('/dashboard/events/league/' + this.leagueId, { headers: {'X-Requested-With': 'XMLHttpRequest'} });
            this.eventsHtml = await reload.text();
            btn.innerText = 'Creado';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = original;
            }, 900);
        } catch (e) {
            alert(e.message || 'Error al crear el partido.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }
}">
    <div style="margin-bottom: 1.75rem;">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800;">Gestión de Torneos</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Seleccione una liga o torneo para administrar sus partidos.</p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
        <?php foreach ($leagues as $l): ?>
        <?php $isActive = $l['active'] == 1; ?>
        <?php $statusColor = $isActive ? 'var(--success)' : 'var(--danger)'; ?>
        <?php $statusText = $isActive ? 'Activo' : 'Inactivo'; ?>
        <div class="glass-card" style="cursor: pointer; position: relative;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'" @click="if(!event.target.closest('button')) openLeague(<?= $l['id'] ?>, '<?= esc($l['name'], 'js') ?>')">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <span style="font-size:1.5rem;"><?= esc($l['sport_icon']) ?></span>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <span style="font-size:0.65rem;font-weight:600;color:var(--text-main);background:rgba(255,255,255,0.1);padding:0.2rem 0.5rem;border-radius:9999px;">
                        <?= $l['event_count'] ?> partidos
                    </span>
                    <button onclick="toggleLeague(<?= $l['id'] ?>, this)" style="cursor:pointer; font-size:0.65rem; font-weight:850; color:<?= $statusColor ?>; background:<?= $statusColor ?>18; padding:0.2rem 0.5rem; border-radius:9999px; border:none;">
                        <?= $statusText ?>
                    </button>
                </div>
            </div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:0.25rem;"><?= esc($l['name']) ?></h4>
            <p style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:0.5rem;"><?= esc($l['country'] ?? 'Internacional') ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal de Partidos -->
    <div x-show="showModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.82); z-index:9999; display:flex; align-items:center; justify-content:center; padding:1rem;" x-transition>
        <div class="events-modal-shell">
            <div class="events-modal-header">
                <h3 style="font-family:'Outfit', sans-serif; font-size:1.3rem; font-weight:700;" x-text="leagueName"></h3>
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <button @click="fetchScoresManual($event.target)" style="cursor:pointer;font-size:0.75rem;font-weight:900;color:#fff;background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:0.45rem 0.7rem;border-radius:6px;border:none;">📡 Obtener Marcadores</button>
                    <button @click="generateLeagueMarkets()" style="cursor:pointer;font-size:0.75rem;font-weight:900;color:#0f172a;background:linear-gradient(135deg,#34d399,#22c55e);padding:0.45rem 0.7rem;border-radius:6px;border:none;">Generar mercados</button>
                    <button @click="showModal = false" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
            </div>
            <div class="event-create-panel">
                <details>
                    <summary style="cursor:pointer;color:var(--text-primary);font-size:0.82rem;font-weight:900;">Nuevo partido</summary>
                    <div class="event-admin-form">
                        <input id="new-event-home-team" placeholder="Equipo local">
                        <input id="new-event-away-team" placeholder="Equipo visitante">
                        <input id="new-event-home-flag" placeholder="Bandera local">
                        <input id="new-event-away-flag" placeholder="Bandera visitante">
                        <input id="new-event-stage" placeholder="Fase">
                        <input id="new-event-group" placeholder="Grupo">
                        <input id="new-event-venue" placeholder="Estadio">
                        <input id="new-event-start-time" type="datetime-local">
                        <input id="new-event-match-number" type="number" min="1" placeholder="Nro. partido opcional">
                        <button @click="createEvent($event.target)" style="cursor:pointer;border:none;border-radius:6px;background:var(--primary);color:#fff;font-size:0.78rem;font-weight:900;padding:0.5rem;">Crear partido</button>
                    </div>
                </details>
            </div>
            <div style="padding:1rem; overflow-y:auto; flex:1;" x-html="eventsHtml"></div>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    async function saveEventDetails(id, btn) {
        const field = (name) => document.getElementById('event-' + id + '-' + name)?.value || '';
        const homeTeam = field('home-team').trim();
        const awayTeam = field('away-team').trim();
        const startTime = field('start-time');
        const venue = field('venue').trim();

        if (!homeTeam || !awayTeam || !startTime || !venue) {
            alert('Equipo local, visitante, fecha y estadio son obligatorios.');
            return;
        }

        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Guardando...';

        try {
            const body = new FormData();
            body.append('home_team', homeTeam);
            body.append('away_team', awayTeam);
            body.append('home_flag', field('home-flag').trim());
            body.append('away_flag', field('away-flag').trim());
            body.append('stage', field('stage').trim());
            body.append('group_name', field('group').trim());
            body.append('venue', venue);
            body.append('start_time', startTime);
            body.append('match_number', field('match-number'));

            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/events/update/' + id, body)
                : await (await fetch('/dashboard/events/update/' + id, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                    },
                    body
                })).json();

            if (result.status !== 'success') {
                alert(result.message || 'No se pudo actualizar el partido.');
                btn.disabled = false;
                btn.innerText = original;
                return;
            }

            btn.innerText = 'Guardado';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = original;
            }, 900);
        } catch (e) {
            console.error(e);
            alert(e.message || 'Error al actualizar el partido.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }

    async function createEventMarket(id, btn) {
        const field = (name) => document.getElementById('market-' + id + '-' + name)?.value || '';
        const name = field('name').trim();
        const selections = field('selections').trim();
        const odds = field('odds').trim();

        if (!name || !selections || !odds) {
            alert('Nombre, selecciones y cuotas son obligatorios.');
            return;
        }

        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Creando...';

        try {
            const body = new FormData();
            body.append('name', name);
            body.append('type', field('type').trim());
            body.append('selections', selections);
            body.append('odds', odds);

            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/events/markets/create/' + id, body)
                : await (await fetch('/dashboard/events/markets/create/' + id, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                    },
                    body
                })).json();

            if (result.status !== 'success') {
                alert(result.message || 'No se pudo crear el mercado.');
                btn.disabled = false;
                btn.innerText = original;
                return;
            }

            ['name', 'type', 'selections', 'odds'].forEach((item) => {
                const input = document.getElementById('market-' + id + '-' + item);
                if (input) input.value = '';
            });

            const counter = document.getElementById('market-count-' + id);
            if (counter) {
                const current = Number.parseInt(counter.innerText, 10) || 0;
                counter.innerText = (current + 1) + ' mercados';
            }

            btn.innerText = 'Creado';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = original;
            }, 900);
        } catch (e) {
            console.error(e);
            alert(e.message || 'Error al crear el mercado.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }
    
    async function toggleLeague(id, btn) {
        try {
            btn.innerText = '...';
            const response = await fetch('/dashboard/leagues/toggle/' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await response.json();
            if (result.status === 'success') {
                const isActive = (result.active == 1);
                const color = isActive ? 'var(--success)' : 'var(--danger)';
                btn.style.color = color;
                btn.style.backgroundColor = color + '18';
                btn.innerText = result.new_status;
            } else {
                alert(result.message || 'Error desconocido');
            }
        } catch (e) {
            console.error(e);
            alert('Error al actualizar torneo');
        }
    }

    async function toggleEvent(id, btn) {
        try {
            btn.innerText = '...';
            const response = await fetch('/dashboard/events/toggle/' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await response.json();
            if (result.status === 'success') {
                const isActive = (result.new_status === 'pending' || result.new_status === 'live');
                const color = isActive ? 'var(--success)' : 'var(--danger)';
                btn.style.color = color;
                btn.style.backgroundColor = color + '18';
                btn.innerText = isActive ? 'Activo' : 'Inactivo';
            } else {
                alert(result.message);
            }
        } catch (e) {
            console.error(e);
            alert('Error toggling status');
        }
    }

    window.doFinishEvent = async function(e, id, btn) {
        e.preventDefault();
        try {
            const homeEl = document.getElementById('score-home-' + id);
            const awayEl = document.getElementById('score-away-' + id);

            if (!homeEl || !awayEl) {
                alert('CRITICAL ERROR: no se encontraron los campos input score-home-' + id + ' o score-away-' + id);
                return;
            }

            const home = homeEl.value;
            const away = awayEl.value;

            if (home === '' || away === '') {
                alert('Debes ingresar ambos valores del marcador antes de presionar Fijar Marcador.');
                return;
            }

            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Guardando...';

            const body = new URLSearchParams();
            body.append('score_home', home);
            body.append('score_away', away);

            const response = await fetch('/dashboard/events/finish/' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                },
                body
            });

            if (!response.ok) {
                const text = await response.text();
                alert('ERROR DEL SERVIDOR (' + response.status + '): ' + text.substring(0, 100));
                btn.disabled = false;
                btn.innerText = originalText;
                return;
            }

            const result = await response.json();

            if (result.status !== 'success') {
                alert('ERROR DE LOGICA: ' + (result.message || 'Error desconocido'));
                btn.disabled = false;
                btn.innerText = originalText;
                return;
            }

            // Éxito:
            const container = btn.closest('.event-admin-score')?.parentElement || btn.closest('div');
            container.innerHTML = `<div style="font-size:0.82rem;font-weight:900;color:var(--success);background:rgba(34,197,94,0.12);border-radius:8px;padding:0.48rem 0.65rem;">Marcador guardado: ${home}-${away}</div>`;

            if (result.bracket_completed) {
                alert('INFO: Fase completada automaticamente.');
            }
        } catch (err) {
            alert('ERROR FATAL JAVASCRIPT: ' + err.message);
            btn.disabled = false;
            btn.innerText = 'Fijar Marcador';
        }
    };

    async function generateMarkets(id, btn) {
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = '...';

        try {
            const response = await fetch('/dashboard/events/generate-markets/' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await response.json();

            if (result.status !== 'success') {
                alert(result.message || 'No se pudieron generar mercados.');
                btn.disabled = false;
                btn.innerText = original;
                return;
            }

            const count = document.getElementById('market-count-' + id);
            if (count) {
                count.innerText = result.market_count + ' mercados';
            }
            btn.innerText = result.created > 0 ? 'Generados' : 'Ya existen';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerText = original;
            }, 1600);
        } catch (e) {
            console.error(e);
            alert('Error al generar mercados.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }

    async function toggleMarket(id, btn) {
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = '...';

        try {
            const response = await fetch('/dashboard/markets/toggle/' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await response.json();
            if (result.status !== 'success') {
                alert(result.message || 'No se pudo actualizar el mercado.');
                btn.disabled = false;
                btn.innerText = original;
                return;
            }

            const status = document.getElementById('market-status-' + id);
            if (status) status.innerText = result.new_status;
            const isOpen = result.new_status === 'open';
            btn.innerText = isOpen ? 'Suspender' : 'Reabrir';
            btn.style.background = isOpen ? 'rgba(239,68,68,0.16)' : 'rgba(34,197,94,0.16)';
            btn.style.color = isOpen ? '#fca5a5' : '#86efac';
            btn.disabled = false;
        } catch (e) {
            console.error(e);
            alert('Error al actualizar el mercado.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }

    async function toggleOdd(id, btn) {
        btn.disabled = true;
        try {
            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/odds/toggle/' + id)
                : await (await fetch('/dashboard/odds/toggle/' + id, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                    }
                })).json();
            if (result.status !== 'success') {
                alert(result.message || 'No se pudo actualizar la cuota.');
                btn.disabled = false;
                return;
            }

            const active = Number(result.active) === 1;
            const control = document.getElementById('odd-control-' + id) || btn;
            control.style.opacity = active ? '1' : '0.45';
            control.style.borderColor = active ? 'var(--border)' : 'rgba(239,68,68,0.35)';
            btn.disabled = false;
        } catch (e) {
            console.error(e);
            alert('Error al actualizar la cuota.');
            btn.disabled = false;
        }
    }

    async function runWorldCupBracket(stage, btn) {
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Procesando...';

        try {
            const response = await fetch('/dashboard/events/worldcup-bracket/' + stage, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            });
            const result = await response.json();

            if (result.status !== 'success') {
                alert(result.message || 'No se pudo completar la etapa.');
                btn.disabled = false;
                btn.innerText = original;
                return;
            }

            alert(result.message);
            btn.innerText = 'Completado';
        } catch (e) {
            console.error(e);
            alert('Error al ejecutar el bracket.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }
</script>
