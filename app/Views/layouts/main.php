<?php
    $db = \Config\Database::connect();
    $realEventCount = (int) $db->table('events')->countAllResults();

    $adminNotifications = $adminNotifications ?? [];
    if (empty($adminNotifications) && (int) (session()->get('role_id') ?? 0) === 1) {
        try {
            $db = \Config\Database::connect();
            $pendingWithdrawals = (int) $db->table('withdrawal_requests')->where('status', 'pending')->countAllResults();
            $pendingKyc = (int) $db->table('kyc_verifications')->where('status', 'pending')->countAllResults();
            $highRiskTickets = (int) $db->table('bet_slips')
                ->where('status', 'pending')
                ->where('potential_payout >=', 50000)
                ->countAllResults();
            $recentAudit = $db->table('audit_logs a')
                ->select('a.action, a.created_at, u.username')
                ->join('users u', 'u.id = a.user_id', 'left')
                ->orderBy('a.created_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($pendingWithdrawals > 0) {
                $adminNotifications[] = [
                    'title' => 'Retiros pendientes',
                    'desc' => $pendingWithdrawals . ' solicitud(es) esperando aprobacion.',
                    'time' => 'Ahora',
                    'color' => 'var(--accent-amber)',
                    'url' => '/dashboard/withdrawals',
                    'page' => 'withdrawals',
                ];
            }
            if ($pendingKyc > 0) {
                $adminNotifications[] = [
                    'title' => 'KYC pendiente',
                    'desc' => $pendingKyc . ' verificacion(es) listas para revisar.',
                    'time' => 'Ahora',
                    'color' => 'var(--accent-cyan)',
                    'url' => '/dashboard/kyc',
                    'page' => 'kyc',
                ];
            }
            if ($highRiskTickets > 0) {
                $adminNotifications[] = [
                    'title' => 'Tickets de alto riesgo',
                    'desc' => $highRiskTickets . ' ticket(s) con pago potencial superior a $50.000.',
                    'time' => 'Ahora',
                    'color' => 'var(--accent-rose)',
                    'url' => '/dashboard/bets?status=pending',
                    'page' => 'bets',
                ];
            }
            if ($recentAudit) {
                $adminNotifications[] = [
                    'title' => 'Actividad reciente',
                    'desc' => ($recentAudit['username'] ?? 'Sistema') . ' ejecuto ' . str_replace('_', ' ', $recentAudit['action'] ?? 'accion'),
                    'time' => !empty($recentAudit['created_at']) ? date('d/m/Y H:i', strtotime($recentAudit['created_at'])) : 'Reciente',
                    'color' => 'var(--accent-emerald)',
                    'url' => '/dashboard/audit',
                    'page' => 'audit',
                ];
            }
        } catch (\Throwable $e) {
            $adminNotifications[] = [
                'title' => 'Notificaciones no disponibles',
                'desc' => 'No se pudo cargar el resumen operativo.',
                'time' => 'Ahora',
                'color' => 'var(--accent-rose)',
                'url' => '/dashboard/overview',
                'page' => 'overview',
            ];
        }
    }
    $adminNotificationCount = count(array_filter($adminNotifications, static fn ($item) => ($item['page'] ?? '') !== 'audit'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Codex SS - Dashboard' ?></title>
    <meta name="description" content="Codex SS - Plataforma de Apuestas Enterprise">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-header" content="<?= csrf_header() ?>">
    <meta name="csrf-field" content="<?= csrf_token() ?>">

    <!-- Resource Hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://unpkg.com/htmx.org@1.9.10" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" as="script">
    <link rel="preload" href="https://unpkg.com/lucide@latest" as="script">

    <!-- HTMX (must load before Alpine) -->
    <script src="https://unpkg.com/htmx.org@1.9.10" defer></script>
    <script>
        function dashboardShell() {
            return {
                sidebarOpen: true,
                isMobile: false,
                init() {
                    const mobileQuery = window.matchMedia('(max-width: 768px)');
                    this.isMobile = mobileQuery.matches;

                    const saved = localStorage.getItem('codex_ss_admin_sidebar');
                    this.sidebarOpen = saved === null ? !this.isMobile : saved === 'open';
                    Alpine.store('app').sidebar = this.sidebarOpen;

                    mobileQuery.addEventListener('change', (event) => {
                        this.isMobile = event.matches;
                        if (this.isMobile) {
                            this.sidebarOpen = false;
                        }
                    });

                    this.$watch('sidebarOpen', (value) => {
                        Alpine.store('app').sidebar = value;
                        localStorage.setItem('codex_ss_admin_sidebar', value ? 'open' : 'closed');
                    });
                },
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },
                closeSidebar() {
                    this.sidebarOpen = false;
                }
            }
        }
        document.addEventListener('htmx:configRequest', (event) => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (csrfToken) {
                event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
            }
        });
        document.addEventListener('htmx:send', () => {
            const loader = document.getElementById('module-loader');
            if (loader) loader.style.display = 'flex';
        });
        document.addEventListener('htmx:afterOnLoad', () => {
            const loader = document.getElementById('module-loader');
            if (loader) loader.style.display = 'none';
        });
    </script>
    <div id="module-loader" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(10,10,12,0.85); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
        <div style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.1); border-left-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
        <p style="margin-top: 1rem; color: #fff; font-family: 'Outfit', sans-serif; font-weight: 600;">Cargando módulo...</p>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
    </div>
    <!-- Alpine.js global store -->
    <script>
        window.eventsManager = function() {
            return {
                showModal: false,
                showSerpApiInput: false,
                serpApiQuery: 'partidos de futbol hoy',
                leagueId: null,
                leagueName: '',
                eventsHtml: '<div style="padding:2rem;text-align:center;">Cargando eventos...</div>',
                stagedEvents: [],
                async loadStagedEvents() {
                    try {
                        const res = await fetch('/dashboard/events/staged');
                        this.stagedEvents = await res.json();
                    } catch(e) { console.error('Error cargando staging:', e); }
                },
                async approveStaged(id) {
                    try {
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch('/dashboard/events/staged/approve/' + id, { method: 'POST', headers });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.loadStagedEvents();
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            alert(result.message);
                        }
                    } catch(e) { alert('Error aprobando partido.'); }
                },
                async rejectStaged(id) {
                    if(!confirm('¿Descartar este partido importado?')) return;
                    try {
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch('/dashboard/events/staged/reject/' + id, { method: 'POST', headers });
                        const result = await res.json();
                        if (result.status === 'success') this.loadStagedEvents();
                    } catch(e) {}
                },
                async approveAllStaged() {
                    if(this.stagedEvents.length === 0 || !confirm('¿Aprobar TODOS los partidos en revisión?')) return;
                    const batchId = this.stagedEvents[0].batch_id || 'all';
                    try {
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch('/dashboard/events/staged/bulk-approve/' + batchId, { method: 'POST', headers });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(result.message);
                            this.loadStagedEvents();
                            setTimeout(() => window.location.reload(), 1000);
                        } else { alert(result.message); }
                    } catch(e) { alert('Error en bulk approve'); }
                },
                async clearStagedEvents() {
                    if(!confirm('¿Estás seguro de limpiar la tabla de importación? Se borrarán todos los partidos no aprobados.')) return;
                    try {
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch('/dashboard/events/staged/clear', { method: 'POST', headers });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.stagedEvents = [];
                            this.serpApiQuery = '';
                        } else {
                            alert(result.message);
                        }
                    } catch(e) { alert('Error al limpiar importación'); }
                },
                async openLeague(id, name) {
                    this.leagueId = id;
                    this.leagueName = name;
                    this.showModal = true;
                    this.eventsHtml = '<div style="padding:2rem;text-align:center;">Cargando eventos...</div>';
                    try {
                        const res = await fetch('/dashboard/events/league/' + id, { headers: {'X-Requested-With': 'XMLHttpRequest'} });
                        this.eventsHtml = await res.text();
                    } catch (e) {
                        this.eventsHtml = '<div style="padding:2rem;text-align:center;color:var(--danger);">Error al cargar.</div>';
                    }
                },
                async generateLeagueMarkets() {
                    if (!this.leagueId) return;
                    try {
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch('/dashboard/events/league/' + this.leagueId + '/generate-markets', {
                            method: 'POST',
                            headers
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
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;
                        
                        const res = await fetch('/dashboard/events/fetch-scores', {
                            method: 'POST',
                            headers
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            let msg = result.message;
                            if (result.results && result.results.length > 0) {
                                msg += '\n\n' + result.results.join('\n');
                            }
                            alert(msg);
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

                        let result;
                        if (typeof postDashboardAction === 'function') {
                            result = await postDashboardAction('/dashboard/events/league/' + this.leagueId + '/create', body);
                        } else {
                            const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                            const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                            if (csrfToken) headers[csrfHeader] = csrfToken;
                            const res = await fetch('/dashboard/events/league/' + this.leagueId + '/create', {
                                method: 'POST',
                                headers,
                                body
                            });
                            result = await res.json();
                        }

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
                },
                async fetchSerpApi(btn) {
                    if (!this.showSerpApiInput) {
                        this.showSerpApiInput = true;
                        if (btn) btn.innerText = '🔍 Buscar en Google';
                        setTimeout(() => document.getElementById('serpapi-query')?.focus(), 100);
                        return;
                    }

                    const query = this.serpApiQuery.trim();
                    if (!query) {
                        alert('Por favor, ingresa qué eventos quieres buscar (ej: "partidos de boca juniors").');
                        return;
                    }
                    
                    const original = btn ? btn.innerText : '🔍 Buscar en Google';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerText = '⏳ Buscando en SerpApi...';
                    }
                    try {
                        const body = new URLSearchParams();
                        body.append('query', query);
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;

                        const res = await fetch('/dashboard/events/fetch-serpapi', {
                            method: 'POST',
                            headers,
                            body
                        });
                        const result = await res.json();
                        alert(result.status === 'success' ? result.message : (result.message || 'Error desconocido'));
                        if (result.status === 'success') this.loadStagedEvents();
                    } catch(e) { alert('Error conectando a SerpApi'); }
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = original;
                    }
                },
                async fetchFootballData(btn) {
                    const original = btn.innerText;
                    btn.disabled = true;
                    btn.innerText = '⏳ Buscando en Football-Data...';
                    try {
                        const body = new URLSearchParams();
                        body.append('competition', '');
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;

                        const res = await fetch('/dashboard/events/fetch-football-data', {
                            method: 'POST',
                            headers,
                            body
                        });
                        const result = await res.json();
                        alert(result.status === 'success' ? result.message : (result.message || 'Error desconocido'));
                        if (result.status === 'success') this.loadStagedEvents();
                    } catch(e) { alert('Error conectando a Football-Data'); }
                    btn.disabled = false;
                    btn.innerText = original;
                },
                async fetchESPN(btn) {
                    const original = btn.innerText;
                    btn.disabled = true;
                    btn.innerText = '⏳ Cargando desde ESPN...';
                    try {
                        const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
                        const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfToken) headers[csrfHeader] = csrfToken;

                        const res = await fetch('/dashboard/events/fetch-espn', {
                            method: 'POST',
                            headers
                        });
                        const result = await res.json();
                        alert(result.status === 'success' ? result.message : (result.message || 'Error desconocido'));
                        if (result.status === 'success') this.loadStagedEvents();
                    } catch(e) { alert('Error conectando a ESPN'); }
                    btn.disabled = false;
                    btn.innerText = original;
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            Alpine.store('app', {
                sidebar: true,
                userMenu: false,
                notifOpen: false,
                currentPage: '<?= $activePage ?? "overview" ?>',
                setActive(page) { this.currentPage = page; }
            });
        });
    </script>
    
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
            alert('Error general al suspender el partido.');
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

    async function editLeagueAction(id, currentName, btn) {
        const newName = prompt('Ingrese el nuevo nombre para este Torneo/Liga:', currentName);
        if (!newName || newName.trim() === '' || newName.trim() === currentName) {
            return;
        }
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = '⏳';
        try {
            const body = new FormData();
            body.append('name', newName.trim());
            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/leagues/update/' + id, body)
                : await (await fetch('/dashboard/leagues/update/' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                    body
                })).json();
            
            if (result.status === 'success') {
                if (typeof loadView === 'function') { loadView('/dashboard/events', 'events'); }
                else { window.location.reload(); }
            } else {
                alert(result.message || 'Error al actualizar torneo.');
                btn.disabled = false;
                btn.innerText = original;
            }
        } catch(e) {
            alert('Error al actualizar torneo.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }

    async function deleteLeagueAction(id, btn) {
        if (!confirm('¿Está seguro de eliminar esta Liga/Torneo? (Solo será posible si no hay apuestas asociadas)')) return;
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = '⏳';
        try {
            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/leagues/delete/' + id)
                : await (await fetch('/dashboard/leagues/delete/' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': '<?= csrf_hash() ?>' }
                })).json();
            
            if (result.status === 'success') {
                if (typeof loadView === 'function') { loadView('/dashboard/events', 'events'); }
                else { window.location.reload(); }
            } else {
                alert(result.message || 'Error al eliminar torneo.');
                btn.disabled = false;
                btn.innerText = original;
            }
        } catch(e) {
            alert(e.message || 'Error de conexión al eliminar torneo.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }

    async function deleteEventAction(id, btn) {
        if (!confirm('¿Está seguro de eliminar este Evento/Partido? (Solo será posible si no hay apuestas asociadas)')) return;
        const original = btn.innerText;
        btn.disabled = true;
        btn.innerText = '⏳';
        try {
            const result = typeof postDashboardAction === 'function'
                ? await postDashboardAction('/dashboard/events/delete/' + id)
                : await (await fetch('/dashboard/events/delete/' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', '<?= csrf_header() ?>': '<?= csrf_hash() ?>' }
                })).json();
            
            if (result.status === 'success') {
                const card = document.getElementById('event-card-' + id);
                if (card) { card.remove(); }
                else if (typeof loadView === 'function') { loadView('/dashboard/events', 'events'); }
                else { window.location.reload(); }
            } else {
                alert(result.message || 'Error al eliminar partido.');
                btn.disabled = false;
                btn.innerText = original;
            }
        } catch(e) {
            alert(e.message || 'Error de conexión al eliminar partido.');
            btn.disabled = false;
            btn.innerText = original;
        }
    }
</script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --surface: rgba(17, 24, 39, 0.85);
            --surface-hover: rgba(30, 41, 59, 0.9);
            --border: rgba(255, 255, 255, 0.06);
            --border-active: rgba(99, 102, 241, 0.4);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.15);
            --accent-cyan: #22d3ee;
            --accent-emerald: #34d399;
            --accent-amber: #fbbf24;
            --accent-rose: #fb7185;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --sidebar-w: 260px;
            --topbar-h: 64px;
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow: hidden;
        }

        /* ═══ SIDEBAR ═══ */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            backdrop-filter: blur(24px);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 40;
            will-change: transform, opacity;
            box-shadow: 22px 0 50px rgba(0, 0, 0, 0.18);
            transition:
                transform 0.42s var(--ease-out),
                opacity 0.32s ease,
                box-shadow 0.42s ease;
        }
        .sidebar.collapsed {
            opacity: 0;
            pointer-events: none;
            transform: translateX(calc((var(--sidebar-w) + 18px) * -1));
            box-shadow: none;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 0.75rem;
        }
        .sidebar-brand .logo {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--accent-cyan));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 0.9rem;
            color: white; flex-shrink: 0;
        }
        .sidebar-brand span {
            font-family: 'Outfit', sans-serif; font-weight: 700;
            font-size: 1.15rem; letter-spacing: -0.02em;
            background: linear-gradient(135deg, #818cf8, var(--accent-cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .sidebar-brand span,
        .sidebar-nav,
        .sidebar-footer-info {
            transition:
                opacity 0.24s ease,
                transform 0.32s var(--ease-out),
                filter 0.32s ease;
        }
        .sidebar.collapsed .sidebar-brand span,
        .sidebar.collapsed .sidebar-nav,
        .sidebar.collapsed .sidebar-footer-info {
            opacity: 0;
            filter: blur(4px);
            transform: translateX(-12px);
        }

        .sidebar-nav { flex: 1; padding: 1rem 0.75rem; overflow-y: auto; }
        .nav-section-title {
            font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.1em; color: var(--text-muted);
            padding: 0.75rem 0.75rem 0.5rem; margin-top: 0.5rem;
        }
        .nav-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 0.75rem; border-radius: 0.5rem;
            color: var(--text-secondary); text-decoration: none;
            font-size: 0.875rem; font-weight: 500;
            transition:
                background 0.2s ease,
                color 0.2s ease,
                border-color 0.2s ease,
                transform 0.22s var(--ease-out);
            cursor: pointer;
            margin-bottom: 2px; position: relative;
            background: none; border: none; width: 100%; text-align: left;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .nav-item i { width: 18px; height: 18px; flex-shrink: 0; }
        .nav-item:hover { background: var(--surface-hover); color: var(--text-primary); transform: translateX(3px); }
        .nav-item.active {
            background: var(--primary-glow); color: #a5b4fc;
            border: 1px solid var(--border-active);
        }
        .nav-item.active::before {
            content: ''; position: absolute; left: -0.75rem; top: 50%;
            transform: translateY(-50%); width: 3px; height: 20px;
            background: var(--primary); border-radius: 0 4px 4px 0;
        }
        .nav-badge {
            margin-left: auto; font-size: 0.7rem; font-weight: 600;
            padding: 0.15rem 0.5rem; border-radius: 9999px;
            background: rgba(99, 102, 241, 0.2); color: #a5b4fc;
        }

        .sidebar-footer {
            padding: 1rem 1.25rem; border-top: 1px solid var(--border);
            display: flex; align-items: center; gap: 0.75rem;
        }
        .sidebar-footer-avatar {
            width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
        }
        .sidebar-footer-info { flex: 1; min-width: 0; }
        .sidebar-footer-name {
            font-size: 0.8rem; font-weight: 600; color: var(--text-primary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-footer-role { font-size: 0.7rem; color: var(--text-muted); }

        /* ═══ MAIN AREA ═══ */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            height: 100vh; display: flex; flex-direction: column;
            will-change: margin-left;
            transition: margin-left 0.42s var(--ease-out);
            background-image:
                radial-gradient(ellipse at 10% 0%, rgba(99, 102, 241, 0.08), transparent 50%),
                radial-gradient(ellipse at 90% 100%, rgba(34, 211, 238, 0.06), transparent 50%);
        }
        .main-wrapper.expanded { margin-left: 0; }

        /* ═══ TOPBAR ═══ */
        .topbar {
            height: var(--topbar-h); padding: 0 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            border-bottom: 1px solid var(--border);
            background: rgba(10, 14, 26, 0.6); backdrop-filter: blur(12px);
            position: sticky; top: 0; z-index: 30;
        }
        .topbar-toggle {
            background: none; border: 1px solid var(--border); border-radius: 0.5rem;
            color: var(--text-secondary); cursor: pointer;
            width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
            transition:
                background 0.2s ease,
                color 0.2s ease,
                border-color 0.2s ease,
                transform 0.24s var(--ease-out),
                box-shadow 0.24s ease;
        }
        .topbar-toggle:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
            border-color: rgba(99,102,241,0.32);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.08);
            transform: translateY(-1px);
        }
        .topbar-toggle i { transition: transform 0.34s var(--ease-out), color 0.2s ease; }
        .topbar-toggle.is-closed i {
            color: var(--accent-cyan);
            transform: rotate(180deg);
        }

        .topbar-breadcrumb {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.85rem; color: var(--text-muted); flex: 1;
        }
        .topbar-breadcrumb .current { color: var(--text-primary); font-weight: 600; }

        .topbar-actions { display: flex; align-items: center; gap: 0.5rem; }
        .topbar-btn {
            background: none; border: 1px solid var(--border); border-radius: 0.5rem;
            color: var(--text-secondary); cursor: pointer;
            width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; position: relative;
        }
        .topbar-btn:hover { background: var(--surface-hover); color: var(--text-primary); }
        .topbar-btn .badge-dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--accent-rose); border: 2px solid var(--bg-primary);
        }

        /* User Dropdown */
        .user-menu { position: relative; }
        .user-trigger {
            display: flex; align-items: center; gap: 0.6rem;
            cursor: pointer; padding: 0.35rem 0.6rem 0.35rem 0.35rem;
            border-radius: 0.5rem; border: 1px solid var(--border);
            background: none; color: var(--text-primary); transition: all 0.2s;
        }
        .user-trigger:hover { background: var(--surface-hover); }
        .user-trigger img { width: 30px; height: 30px; border-radius: 8px; }
        .user-trigger span { font-size: 0.8rem; font-weight: 500; }

        .dropdown-panel {
            position: absolute; top: calc(100% + 8px); right: 0;
            width: 220px; background: var(--bg-secondary);
            border: 1px solid var(--border); border-radius: 0.75rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4); padding: 0.5rem;
            z-index: 50;
        }
        .dropdown-item {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.55rem 0.75rem; border-radius: 0.5rem;
            font-size: 0.8rem; color: var(--text-secondary);
            text-decoration: none; transition: all 0.15s; cursor: pointer;
        }
        .dropdown-item:hover { background: var(--surface-hover); color: var(--text-primary); }
        .dropdown-item i { width: 16px; height: 16px; }
        .dropdown-divider { height: 1px; background: var(--border); margin: 0.35rem 0; }
        .dropdown-item.danger { color: var(--accent-rose); }
        .dropdown-item.danger:hover { background: rgba(251, 113, 133, 0.1); }

        /* ═══ CONTENT ═══ */
        .content-area {
            flex: 1 1 auto;
            height: calc(100vh - var(--topbar-h));
            min-height: 0;
            overflow-y: scroll;
            overflow-x: hidden;
            overscroll-behavior: contain;
            padding: 1.5rem 1.5rem 6rem;
            scrollbar-gutter: stable;
            transition: padding 0.42s var(--ease-out);
        }
        .content-area::-webkit-scrollbar { width: 8px; }
        .content-area::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        .content-area::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 999px;
        }
        .content-area::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.55); }

        /* ═══ NOTIFICATION PANEL ═══ */
        .notif-panel {
            position: fixed; top: 0; right: 0; bottom: 0; width: 380px;
            background: var(--bg-secondary); border-left: 1px solid var(--border);
            box-shadow: -10px 0 40px rgba(0,0,0,0.3); z-index: 50;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .notif-panel.open { transform: translateX(0); }
        .notif-header {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .notif-header h3 { font-size: 1rem; font-weight: 700; }
        .notif-list { flex: 1; overflow-y: auto; padding: 0.75rem; }
        .notif-item {
            padding: 0.85rem; border-radius: 0.5rem;
            border: 1px solid var(--border); margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .notif-item:hover { background: var(--surface-hover); }
        .notif-item-title { font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; }
        .notif-item-desc { font-size: 0.75rem; color: var(--text-muted); }
        .notif-item-time { font-size: 0.65rem; color: var(--text-muted); margin-top: 0.35rem; }

        /* ═══ MODAL ═══ */
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px); z-index: 60;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-box {
            background: var(--bg-secondary); border: 1px solid var(--border);
            border-radius: 1rem; padding: 2rem; width: 90%; max-width: 480px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }
        .modal-box h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem; }
        .modal-box p { font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.5rem; }
        .modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
        .btn { padding: 0.55rem 1.25rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-ghost { background: var(--surface-hover); color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text-primary); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #5558e6; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-danger { background: rgba(251,113,133,0.15); color: var(--accent-rose); border: 1px solid rgba(251,113,133,0.2); }
        .btn-danger:hover { background: rgba(251,113,133,0.25); }

        /* ═══ GLASS CARD ═══ */
        .glass-card {
            background: var(--surface); backdrop-filter: blur(16px);
            border: 1px solid var(--border); border-radius: 0.875rem;
            padding: 1.25rem; transition: all 0.25s ease;
        }
        .glass-card:hover { border-color: rgba(255,255,255,0.1); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }

        /* ═══ HTMX ═══ */
        .htmx-indicator { display: none; }
        .htmx-request .htmx-indicator { display: block; }
        #main-view { animation: fadeSlide 0.35s ease-out; }
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .view-loading {
            display: flex; align-items: center; justify-content: center;
            padding: 4rem; color: var(--text-muted);
        }
        .spinner { width: 24px; height: 24px; border: 2.5px solid var(--border);
            border-top-color: var(--primary); border-radius: 50%;
            animation: spin 0.7s linear infinite; margin-right: 0.75rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(calc(var(--sidebar-w) * -1)); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-wrapper { margin-left: 0 !important; }
            .sidebar.collapsed { opacity: 0; transform: translateX(calc((var(--sidebar-w) + 18px) * -1)); }
        }
    </style>
</head>
<body x-data="dashboardShell()" x-init="init()">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="sidebar" :class="{ 'collapsed': !sidebarOpen, 'mobile-open': sidebarOpen && isMobile }" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo">CS</div>
            <span>Codex SS</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Principal</div>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'overview' }"
               onclick="loadView('/dashboard/overview', 'overview')">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'analytics' }"
               onclick="loadView('/dashboard/analytics', 'analytics')">
                <i data-lucide="bar-chart-3"></i> Analíticas
                <span class="nav-badge">Live</span>
            </button>

            <div class="nav-section-title">Gestión</div>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'users' }"
               onclick="loadView('/dashboard/users', 'users')">
                <i data-lucide="users"></i> Usuarios
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'events' }"
               onclick="loadView('/dashboard/events', 'events')">
                <i data-lucide="trophy"></i> Eventos
                <span class="nav-badge"><?= $realEventCount ?></span>
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'bets' }"
               onclick="loadView('/dashboard/bets', 'bets')">
                <i data-lucide="ticket-check"></i> Apuestas
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'transactions' }"
               onclick="loadView('/dashboard/transactions', 'transactions')">
                <i data-lucide="wallet"></i> Transacciones
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'withdrawals' }"
               onclick="loadView('/dashboard/withdrawals', 'withdrawals')">
                <i data-lucide="banknote"></i> Retiros
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'kyc' }"
               onclick="loadView('/dashboard/kyc', 'kyc')">
                <i data-lucide="id-card"></i> KYC
            </button>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'audit' }"
               onclick="loadView('/dashboard/audit', 'audit')">
                <i data-lucide="shield-check"></i> Auditoria
            </button>

            <div class="nav-section-title">Redis & Cache</div>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'rankings' }"
               onclick="loadView('/dashboard/rankings', 'rankings')">
                <i data-lucide="flame"></i> Rankings
                <span class="nav-badge" style="background: linear-gradient(135deg, #ef4444, #f97316); color: white;">Live</span>
            </button>

            <div class="nav-section-title">Sistema</div>
            <button class="nav-item" :class="{ 'active': $store.app.currentPage === 'settings' }"
               onclick="loadView('/dashboard/settings', 'settings')">
                <i data-lucide="settings"></i> Configuración
            </button>
        </nav>

        <div class="sidebar-footer">
            <img class="sidebar-footer-avatar" src="https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username') ?? 'U') ?>&background=6366f1&color=fff&size=68&bold=true" alt="Avatar">
            <div class="sidebar-footer-info">
                <div class="sidebar-footer-name"><?= esc(session()->get('username') ?? 'Usuario') ?></div>
                <div class="sidebar-footer-role"><?= session()->get('role_id') == 1 ? 'Administrador' : 'Usuario' ?></div>
            </div>
        </div>
    </aside>

    <!-- ═══ MAIN WRAPPER ═══ -->
    <div class="main-wrapper" :class="{ 'expanded': !sidebarOpen }" id="main-wrapper">

        <!-- TOPBAR -->
        <header class="topbar">
            <button class="topbar-toggle"
                    :class="{ 'is-closed': !sidebarOpen }"
                    @click="toggleSidebar()"
                    aria-label="Alternar menu principal"
                    :aria-expanded="sidebarOpen.toString()">
                <i data-lucide="panel-left"></i>
            </button>
            <div class="topbar-breadcrumb">
                <span>Codex SS</span>
                <span>/</span>
                <span class="current" x-text="
                    $store.app.currentPage === 'overview' ? 'Dashboard' :
                    $store.app.currentPage === 'analytics' ? 'Analíticas' :
                    $store.app.currentPage === 'users' ? 'Usuarios' :
                    $store.app.currentPage === 'events' ? 'Eventos' :
                    $store.app.currentPage === 'bets' ? 'Apuestas' :
                    $store.app.currentPage === 'transactions' ? 'Transacciones' :
                    $store.app.currentPage === 'withdrawals' ? 'Retiros' :
                    $store.app.currentPage === 'kyc' ? 'KYC' :
                    $store.app.currentPage === 'audit' ? 'Auditoria' :
                    $store.app.currentPage === 'rankings' ? 'Rankings' :
                    $store.app.currentPage === 'settings' ? 'Configuración' : 'Dashboard'
                ">Dashboard</span>
            </div>

            <div class="topbar-actions">
                <!-- Notifications -->
                <button class="topbar-btn" @click="$store.app.notifOpen = !$store.app.notifOpen" aria-label="Notificaciones">
                    <i data-lucide="bell"></i>
                    <?php if ($adminNotificationCount > 0): ?>
                        <span class="badge-dot"></span>
                    <?php endif; ?>
                </button>

                <!-- User Menu -->
                <div class="user-menu" @click.away="$store.app.userMenu = false">
                    <button class="user-trigger" @click="$store.app.userMenu = !$store.app.userMenu">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username') ?? 'U') ?>&background=6366f1&color=fff&size=60&bold=true" alt="Avatar">
                        <span><?= esc(session()->get('username') ?? 'Usuario') ?></span>
                        <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--text-muted)"></i>
                    </button>
                    <div class="dropdown-panel" x-show="$store.app.userMenu" x-transition.origin.top.right @click.away="$store.app.userMenu = false" style="display: none;">
                        <a class="dropdown-item" href="#"><i data-lucide="user"></i> Mi Perfil</a>
                        <a class="dropdown-item" href="#"><i data-lucide="shield"></i> Seguridad</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item danger" href="/auth/logout"><i data-lucide="log-out"></i> Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- HTMX Loading -->
        <!--
        <div class="htmx-indicator view-loading" id="view-loader">
            <div class="spinner"></div> Cargando módulo...
        </div>
        
        <!-- CONTENT AREA (NO Alpine scope here — HTMX owns this) -->
        <section class="content-area">
            <div id="main-view">
                <?= $this->renderSection('content') ?>
            </div>
        </section>
    </div>

    <!-- ═══ NOTIFICATION PANEL ═══ -->
    <div class="notif-panel" x-data="{}" :class="{ 'open': $store.app.notifOpen }">
        <div class="notif-header">
            <h3>Notificaciones <?= $adminNotificationCount > 0 ? '(' . (int) $adminNotificationCount . ')' : '' ?></h3>
            <button class="topbar-btn" @click="$store.app.notifOpen = false"><i data-lucide="x"></i></button>
        </div>
        <div class="notif-list">
            <?php if (empty($adminNotifications)): ?>
                <div class="notif-item">
                    <div class="notif-item-title" style="color: var(--accent-emerald);">Sin alertas pendientes</div>
                    <div class="notif-item-desc">No hay retiros, KYC o tickets de alto riesgo esperando accion.</div>
                    <div class="notif-item-time">Ahora</div>
                </div>
            <?php else: ?>
                <?php foreach ($adminNotifications as $item): ?>
                    <button class="notif-item" style="width:100%;text-align:left;cursor:pointer;background:transparent;color:inherit;" onclick="loadView('<?= esc($item['url'] ?? '/dashboard/overview') ?>', '<?= esc($item['page'] ?? 'overview') ?>'); Alpine.store('app').notifOpen = false;">
                        <div class="notif-item-title" style="color: <?= esc($item['color'] ?? 'var(--accent-cyan)') ?>;"><?= esc($item['title'] ?? 'Notificacion') ?></div>
                        <div class="notif-item-desc"><?= esc($item['desc'] ?? '') ?></div>
                        <div class="notif-item-time"><?= esc($item['time'] ?? 'Ahora') ?></div>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (false): ?>
            <div class="notif-item">
                <div class="notif-item-title" style="color: var(--accent-emerald);">Sistema Operativo</div>
                <div class="notif-item-desc">Todos los servicios funcionan con normalidad.</div>
                <div class="notif-item-time">Hace 2 minutos</div>
            </div>
            <div class="notif-item">
                <div class="notif-item-title" style="color: var(--accent-cyan);">Nuevo usuario registrado</div>
                <div class="notif-item-desc">El usuario "test_user" se registró exitosamente.</div>
                <div class="notif-item-time">Hace 15 minutos</div>
            </div>
            <div class="notif-item">
                <div class="notif-item-title" style="color: var(--accent-amber);">Backup completado</div>
                <div class="notif-item-desc">Respaldo automático de la base de datos completado.</div>
                <div class="notif-item-time">Hace 1 hora</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ MODAL ═══ -->
    <!-- Overlay for mobile sidebar -->
    <div x-show="sidebarOpen && isMobile" @click="closeSidebar()"
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:35;"
         x-transition.opacity></div>

    <script>
        // Sidebar navigation via plain fetch — no HTMX dependency
        async function loadView(url, page) {
            const target = document.getElementById('main-view');
            // Update Alpine store
            Alpine.store('app').setActive(page);
            // Show loading
            target.style.opacity = '0.5';
            try {
                const headers = { 'HX-Request': 'true', 'X-Requested-With': 'XMLHttpRequest' };
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

                const res = await fetch(url, { headers });
                if (res.redirected) { window.location.href = res.url; return; }
                const html = await res.text();
                target.innerHTML = html;
                target.style.opacity = '1';
                target.style.animation = 'none';
                target.offsetHeight; // trigger reflow
                target.style.animation = 'fadeSlide 0.35s ease-out';
                // Re-init Lucide icons
                lucide.createIcons();
                // Re-init Alpine on new content
                target.querySelectorAll('[x-data]').forEach(el => {
                    if (!el._x_dataStack) Alpine.initTree(el);
                });
            } catch (err) {
                console.error('Navigation error:', err);
                target.style.opacity = '1';
            }
        }

        async function postDashboardAction(url, formData = new FormData()) {
            const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.content || 'X-CSRF-TOKEN';
            const csrfField  = document.querySelector('meta[name="csrf-field"]')?.content  || 'csrf_test_name';
            const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content   || '';

            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
            if (csrfToken) {
                headers[csrfHeader] = csrfToken;
                formData.append(csrfField, csrfToken);
            }

            const response = await fetch(url, { method: 'POST', headers, body: formData, credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No se pudo completar la accion.');
            }

            // Actualizar el meta tag con el nuevo token si el server lo devuelve
            if (data.csrf_token) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.content = data.csrf_token;
            }
            return data;
        }

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

        async function processKyc(id, action, btn) {
            const note = prompt(action === 'approve' ? 'Nota de aprobacion:' : 'Motivo del rechazo:', '');
            if (note === null) return;

            const original = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = 'Procesando...';
            }

            try {
                const body = new FormData();
                body.append('note', note);
                await postDashboardAction('/dashboard/kyc/' + action + '/' + id, body);
                loadView('/dashboard/kyc', 'kyc');
            } catch (error) {
                alert(error.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }
        }

        async function updateOddValue(id, btn) {
            const input = document.getElementById('odd-input-' + id);
            if (!input) return;

            const value = Number.parseFloat(input.value);
            if (!Number.isFinite(value) || value < 1.01 || value > 1000) {
                alert('La cuota debe estar entre 1.01 y 1000.');
                return;
            }

            const original = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '...';
            }

            try {
                const body = new FormData();
                body.append('odds_decimal', value.toFixed(2));
                const data = await postDashboardAction('/dashboard/odds/update/' + id, body);
                input.value = Number.parseFloat(data.odds_decimal).toFixed(2);
                if (btn) {
                    btn.innerHTML = 'OK';
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = original || 'OK';
                    }, 700);
                }
            } catch (error) {
                alert(error.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
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

                const body = new FormData();
                body.append('score_home', home);
                body.append('score_away', away);

                // Reutilizamos postDashboardAction que maneja CSRF automáticamente
                const data = await postDashboardAction('/dashboard/events/finish/' + id, body);
                
                // Éxito:
                const container = btn.closest('.event-admin-score')?.parentElement || btn.closest('div');
                container.innerHTML = `<div style="font-size:0.82rem;font-weight:900;color:var(--success);background:rgba(34,197,94,0.12);border-radius:8px;padding:0.48rem 0.65rem;">Marcador guardado: ${home}-${away}</div>`;

                if (data.bracket_completed) {
                    alert('INFO: Fase completada automaticamente.');
                }
            } catch (err) {
                alert('ERROR: ' + err.message);
                btn.disabled = false;
                btn.innerText = 'Fijar Marcador';
            }
        };

        // --- Drag and Drop Logic for League Cards ---
        let dragSrcEl = null;

        window.handleDragStart = function(e) {
            dragSrcEl = e.currentTarget;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.currentTarget.innerHTML);
            e.currentTarget.style.opacity = '0.4';
        };

        window.handleDragOver = function(e) {
            if (e.preventDefault) {
                e.preventDefault(); // Necessary. Allows us to drop.
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        };

        window.handleDragEnter = function(e) {
            e.currentTarget.style.borderColor = 'var(--primary)';
        };

        window.handleDragLeave = function(e) {
            e.currentTarget.style.borderColor = 'var(--border)';
        };

        window.handleDrop = function(e) {
            if (e.stopPropagation) {
                e.stopPropagation(); // stops the browser from redirecting.
            }

            const targetEl = e.currentTarget;
            if (dragSrcEl !== targetEl) {
                // Swap the inner HTML to swap the visual items
                const tempHtml = dragSrcEl.innerHTML;
                dragSrcEl.innerHTML = targetEl.innerHTML;
                targetEl.innerHTML = tempHtml;

                // Also swap the data-id attributes so the update request sends the correct order
                const tempId = dragSrcEl.getAttribute('data-id');
                dragSrcEl.setAttribute('data-id', targetEl.getAttribute('data-id'));
                targetEl.setAttribute('data-id', tempId);

                updateLeagueOrderAction();
            }
            return false;
        };

        window.addEventListener('dragend', function(e) {
            const cards = document.querySelectorAll('.league-card');
            cards.forEach(card => {
                card.style.opacity = '1';
                card.style.borderColor = 'var(--border)';
            });
        });

        window.updateLeagueOrderAction = async function() {
            const container = document.getElementById('leagues-container');
            if (!container) return;

            const cards = container.querySelectorAll('.league-card');
            const orderedIds = [];
            cards.forEach(card => {
                orderedIds.push(card.getAttribute('data-id'));
            });

            try {
                const body = new URLSearchParams();
                orderedIds.forEach(id => {
                    body.append('order[]', id);
                });

                const data = await postDashboardAction('/dashboard/leagues/update-order', body);
                console.log('Order updated:', data);
            } catch (err) {
                console.error('Failed to update order', err);
            }
        };

        // Init Lucide icons
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
    </script>
</body>
</html>
