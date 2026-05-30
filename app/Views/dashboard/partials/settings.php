<style>
    .settings-tab-btn {
        background: none;
        border: none;
        padding: 0.75rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        color: var(--text-muted);
        border-bottom: 2px solid transparent;
        transition: all 0.3s var(--ease-out);
        position: relative;
    }
    .settings-tab-btn:hover {
        color: var(--text-primary);
    }
    .settings-tab-btn.active {
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
    }
    
    .settings-form-group {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }
    
    .settings-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .settings-input, .settings-select {
        width: 100%;
        padding: 0.75rem 1rem;
        background: rgba(10, 14, 26, 0.6);
        border: 1px solid var(--border);
        border-radius: 0.625rem;
        color: var(--text-primary);
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: all 0.2s var(--ease-out);
    }
    .settings-input:focus, .settings-select:focus {
        border-color: var(--border-active);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }
    
    /* Toggle Switch Custom Styling */
    .switch-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border);
        transition: all 0.2s;
    }
    .switch-container:last-child {
        border-bottom: none;
    }
    .switch-label-group {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .switch-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    .switch-desc {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .toggle-switch {
        width: 44px;
        height: 24px;
        border-radius: 12px;
        cursor: pointer;
        position: relative;
        transition: background-color 0.3s;
    }
    .toggle-handle {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        position: absolute;
        top: 3px;
        transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    /* QR Upload UI */
    .qr-dropzone {
        border: 2px dashed var(--border);
        border-radius: 0.75rem;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        background: rgba(255,255,255,0.01);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }
    .qr-dropzone.dragover {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.05);
    }
    .qr-preview-img {
        max-width: 180px;
        max-height: 180px;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        padding: 0.5rem;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }
    .qr-preview-img:hover {
        transform: scale(1.03);
    }
    
    /* Gradient Button */
    .btn-gradient {
        background: linear-gradient(135deg, var(--primary), var(--accent-cyan));
        color: white;
        font-weight: 700;
        letter-spacing: 0.02em;
        border: none;
        box-shadow: 0 4px 12px var(--primary-glow);
    }
    .btn-gradient:hover {
        background: linear-gradient(135deg, #5558e6, #06b6d4);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }
</style>

<div style="animation: fadeSlide 0.4s ease-out;" 
     x-data="{ 
        activeTab: 'general',
        security_2fa: <?= ($settings['security_2fa'] ?? '0') === '1' ? 'true' : 'false' ?>,
        security_lockout: <?= ($settings['security_lockout'] ?? '0') === '1' ? 'true' : 'false' ?>,
        security_sessions: <?= ($settings['security_sessions'] ?? '0') === '1' ? 'true' : 'false' ?>,
        notify_email: <?= ($settings['notify_email'] ?? '0') === '1' ? 'true' : 'false' ?>,
        notify_security: <?= ($settings['notify_security'] ?? '0') === '1' ? 'true' : 'false' ?>,
        notify_marketing: <?= ($settings['notify_marketing'] ?? '0') === '1' ? 'true' : 'false' ?>,
        mp_card_enabled: <?= ($settings['mp_card_enabled'] ?? '0') === '1' ? 'true' : 'false' ?>,
        
        qrCodePath: '<?= esc($settings['qr_code_path'] ?? '') ?>',
        qrPreviewUrl: '',
        dragover: false,
        
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.qrPreviewUrl = URL.createObjectURL(file);
            }
        },
        
        triggerFileInput() {
            this.$refs.qrCodeInput.click();
        },
        
        handleDrop(event) {
            this.dragover = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.$refs.qrCodeInput.files = files;
                this.previewImage({ target: { files: files } });
            }
        },
        
        async submitForm(event) {
            const formData = new FormData(event.target);
            
            // Set toggle boolean values as 1 or 0
            formData.set('security_2fa', this.security_2fa ? '1' : '0');
            formData.set('security_lockout', this.security_lockout ? '1' : '0');
            formData.set('security_sessions', this.security_sessions ? '1' : '0');
            formData.set('notify_email', this.notify_email ? '1' : '0');
            formData.set('notify_security', this.notify_security ? '1' : '0');
            formData.set('notify_marketing', this.notify_marketing ? '1' : '0');
            formData.set('mp_card_enabled', this.mp_card_enabled ? '1' : '0');
            
            try {
                // Show loading state
                Swal.fire({
                    title: 'Guardando cambios...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    background: '#111827',
                    color: '#f1f5f9'
                });
                
                const response = await fetch('/dashboard/settings/update', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    if (result.qr_code_path) {
                        this.qrCodePath = result.qr_code_path;
                        this.qrPreviewUrl = ''; // Clear preview since it is uploaded
                    }
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado con éxito!',
                        text: result.message,
                        background: '#111827',
                        color: '#f1f5f9',
                        confirmButtonColor: '#6366f1'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: result.message,
                        background: '#111827',
                        color: '#f1f5f9',
                        confirmButtonColor: '#6366f1'
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo conectar con el servidor para actualizar la configuración.',
                    background: '#111827',
                    color: '#f1f5f9',
                    confirmButtonColor: '#6366f1'
                });
            }
        }
     }">
     
    <div style="margin-bottom: 1.75rem;">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">Configuración del Sistema</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Administra las configuraciones globales, seguridad, alertas de notificación y datos de pasarela bancaria/DEBIN.</p>
    </div>

    <!-- Tabs Wrapper -->
    <div style="display: flex; gap: 0.25rem; margin-bottom: 1.75rem; border-bottom: 1px solid var(--border); overflow-x: auto;">
        <button type="button" @click="activeTab = 'general'" :class="{ 'active': activeTab === 'general' }" class="settings-tab-btn">General</button>
        <button type="button" @click="activeTab = 'security'" :class="{ 'active': activeTab === 'security' }" class="settings-tab-btn">Seguridad</button>
        <button type="button" @click="activeTab = 'notifications'" :class="{ 'active': activeTab === 'notifications' }" class="settings-tab-btn">Notificaciones</button>
        <button type="button" @click="activeTab = 'risk'" :class="{ 'active': activeTab === 'risk' }" class="settings-tab-btn">Riesgo</button>
        <button type="button" @click="activeTab = 'carga_saldo'" :class="{ 'active': activeTab === 'carga_saldo' }" class="settings-tab-btn" style="display: flex; align-items: center; gap: 0.4rem;">
            <i data-lucide="wallet" style="width: 14px; height: 14px;"></i> Carga de Saldo
        </button>
    </div>

    <!-- Main Settings Form -->
    <form @submit.prevent="submitForm($event)" enctype="multipart/form-data">
        <!-- CRSF Token -->
        <?= csrf_field() ?>

        <!-- GENERAL TAB -->
        <div x-show="activeTab === 'general'" x-transition>
            <div class="glass-card" style="max-width: 680px; display: flex; flex-direction: column; gap: 1.5rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Información de la Plataforma</h3>
                    <p style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 1.25rem;">Configura los datos básicos y visuales de la plataforma.</p>
                </div>
                
                <div class="settings-form-group">
                    <label class="settings-label">Nombre de la Plataforma</label>
                    <input type="text" name="platform_name" value="<?= esc($settings['platform_name'] ?? 'Codex SS') ?>" class="settings-input">
                </div>
                
                <div class="settings-form-group">
                    <label class="settings-label">URL Base</label>
                    <input type="text" name="base_url" value="<?= esc($settings['base_url'] ?? 'http://localhost:8080') ?>" class="settings-input">
                </div>
                
                <div class="settings-form-group">
                    <label class="settings-label">Zona Horaria</label>
                    <select name="timezone" class="settings-select">
                        <?php $selectedTimezone = $settings['timezone'] ?? 'America/Argentina/Buenos_Aires'; ?>
                        <option value="America/Argentina/Buenos_Aires" <?= $selectedTimezone === 'America/Argentina/Buenos_Aires' ? 'selected' : '' ?>>America/Argentina/Buenos_Aires (UTC-3)</option>
                        <option value="America/New_York" <?= $selectedTimezone === 'America/New_York' ? 'selected' : '' ?>>America/New_York (UTC-5)</option>
                        <option value="Europe/London" <?= $selectedTimezone === 'Europe/London' ? 'selected' : '' ?>>Europe/London (UTC+0)</option>
                        <option value="Europe/Paris" <?= $selectedTimezone === 'Europe/Paris' ? 'selected' : '' ?>>Europe/Paris (UTC+1)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-gradient" style="align-self: flex-start; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="save" style="width:16px;height:16px;"></i> Guardar Cambios
                </button>
            </div>
        </div>

        <!-- SECURITY TAB -->
        <div x-show="activeTab === 'security'" x-transition style="display: none;">
            <div class="glass-card" style="max-width: 680px; display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Configuración de Seguridad</h3>
                    <p style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 1.25rem;">Gestiona la seguridad y políticas de sesión de la plataforma.</p>
                </div>
                
                <div style="display: flex; flex-direction: column;">
                    <!-- Switch 1 -->
                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Autenticación 2FA de Dos Factores</span>
                            <span class="switch-desc">Fuerza a administradores y usuarios a verificar su identidad vía OTP/email.</span>
                        </div>
                        <div @click="security_2fa = !security_2fa" 
                             :style="security_2fa ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="security_2fa ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>
                    
                    <!-- Switch 2 -->
                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Bloqueo por Intentos Fallidos</span>
                            <span class="switch-desc">Bloquear temporalmente la dirección IP o cuenta tras 5 intentos erróneos.</span>
                        </div>
                        <div @click="security_lockout = !security_lockout" 
                             :style="security_lockout ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="security_lockout ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>
                    
                    <!-- Switch 3 -->
                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Sesiones Concurrentes Limitadas</span>
                            <span class="switch-desc">Evitar que una misma cuenta tenga múltiples sesiones web activas en simultáneo.</span>
                        </div>
                        <div @click="security_sessions = !security_sessions" 
                             :style="security_sessions ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="security_sessions ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-gradient" style="align-self: flex-start; margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="save" style="width:16px;height:16px;"></i> Guardar Cambios
                </button>
            </div>
        </div>

        <!-- NOTIFICATIONS TAB -->
        <div x-show="activeTab === 'notifications'" x-transition style="display: none;">
            <div class="glass-card" style="max-width: 680px; display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Preferencias de Notificación</h3>
                    <p style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 1.25rem;">Ajusta las alertas automáticas enviadas a los correos registrados.</p>
                </div>
                
                <div style="display: flex; flex-direction: column;">
                    <!-- Switch 1 -->
                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Notificación de Transacciones por Email</span>
                            <span class="switch-desc">Enviar un comprobante al usuario cuando realiza depósitos o solicita retiros.</span>
                        </div>
                        <div @click="notify_email = !notify_email" 
                             :style="notify_email ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="notify_email ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>
                    
                    <!-- Switch 2 -->
                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Alertas de Seguridad Críticas</span>
                            <span class="switch-desc">Enviar emails automáticos tras cambios de clave o accesos desde nuevos navegadores.</span>
                        </div>
                        <div @click="notify_security = !notify_security" 
                             :style="notify_security ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="notify_security ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>
                    
                    <!-- Switch 3 -->
                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Emails de Marketing & Promociones</span>
                            <span class="switch-desc">Permitir envíos masivos de bonificaciones y newsletters a usuarios suscritos.</span>
                        </div>
                        <div @click="notify_marketing = !notify_marketing" 
                             :style="notify_marketing ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="notify_marketing ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-gradient" style="align-self: flex-start; margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="save" style="width:16px;height:16px;"></i> Guardar Cambios
                </button>
            </div>
        </div>

        <!-- RISK TAB -->
        <div x-show="activeTab === 'risk'" x-transition style="display: none;">
            <div class="glass-card" style="max-width: 820px; display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Control de Riesgo</h3>
                    <p style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 1.25rem;">Define limites para proteger exposicion, pagos maximos y actividad diaria del apostador.</p>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;">
                    <div class="settings-form-group">
                        <label class="settings-label">Apuesta minima (K)</label>
                        <input type="number" step="0.01" min="0" name="risk_min_stake" value="<?= esc($settings['risk_min_stake'] ?? '100') ?>" class="settings-input">
                    </div>
                    <div class="settings-form-group">
                        <label class="settings-label">Apuesta maxima (K)</label>
                        <input type="number" step="0.01" min="0" name="risk_max_stake" value="<?= esc($settings['risk_max_stake'] ?? '100000') ?>" class="settings-input">
                    </div>
                    <div class="settings-form-group">
                        <label class="settings-label">Pago maximo por ticket (K)</label>
                        <input type="number" step="0.01" min="0" name="risk_max_payout" value="<?= esc($settings['risk_max_payout'] ?? '1000000') ?>" class="settings-input">
                    </div>
                    <div class="settings-form-group">
                        <label class="settings-label">Maximo diario por usuario (K)</label>
                        <input type="number" step="0.01" min="0" name="risk_max_user_daily_stake" value="<?= esc($settings['risk_max_user_daily_stake'] ?? '250000') ?>" class="settings-input">
                    </div>
                    <div class="settings-form-group">
                        <label class="settings-label">Exposicion maxima por evento (K)</label>
                        <input type="number" step="0.01" min="0" name="risk_max_event_exposure" value="<?= esc($settings['risk_max_event_exposure'] ?? '500000') ?>" class="settings-input">
                    </div>
                    <div class="settings-form-group">
                        <label class="settings-label">Exposicion maxima por mercado (K)</label>
                        <input type="number" step="0.01" min="0" name="risk_max_market_exposure" value="<?= esc($settings['risk_max_market_exposure'] ?? '300000') ?>" class="settings-input">
                    </div>
                </div>

                <button type="submit" class="btn btn-gradient" style="align-self: flex-start; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="save" style="width:16px;height:16px;"></i> Guardar Limites
                </button>
            </div>
        </div>

        <!-- CARGA DE SALDO TAB -->
        <div x-show="activeTab === 'carga_saldo'" x-transition style="display: none;">
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.5rem; max-width: 950px; align-items: start;">
                
                <!-- Bank Info Card -->
                <div class="glass-card" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Datos de Cuenta Bancaria / Transferencia</h3>
                        <p style="color: var(--text-muted); font-size: 0.75rem;">Ingresa los datos bancarios donde los usuarios enviarán transferencias para cargar saldo.</p>
                    </div>
                    
                    <div class="settings-form-group">
                        <label class="settings-label">Banco / Entidad Financiera</label>
                        <input type="text" name="bank_name" value="<?= esc($settings['bank_name'] ?? '') ?>" placeholder="Ej. Banco de Galicia y Buenos Aires" class="settings-input">
                    </div>
                    
                    <div class="settings-form-group">
                        <label class="settings-label">Titular de la Cuenta</label>
                        <input type="text" name="bank_holder" value="<?= esc($settings['bank_holder'] ?? '') ?>" placeholder="Ej. Codex Sportsbook S.A." class="settings-input">
                    </div>
                    
                    <div class="settings-form-group">
                        <label class="settings-label">CBU / CVU (22 dígitos)</label>
                        <input type="text" name="bank_cbu_cvu" value="<?= esc($settings['bank_cbu_cvu'] ?? '') ?>" placeholder="0070001230004567891234" maxlength="22" class="settings-input">
                        <span style="font-size:0.7rem; color:var(--text-muted);">Solo números. Se limpiarán guiones y espacios en el servidor.</span>
                    </div>
                    
                    <div class="settings-form-group">
                        <label class="settings-label">Alias de Cuenta</label>
                        <input type="text" name="bank_alias" value="<?= esc($settings['bank_alias'] ?? '') ?>" placeholder="Ej. codex.deposito.mp" class="settings-input">
                    </div>
                    
                    <button type="submit" class="btn btn-gradient" style="align-self: flex-start; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="save" style="width:16px;height:16px;"></i> Guardar Datos de Carga
                    </button>
                </div>
                
                <!-- Mercado Pago Configuration Card -->
                <div class="glass-card" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Configuración de Mercado Pago</h3>
                        <p style="color: var(--text-muted); font-size: 0.75rem;">Ingresa los datos para habilitar recargas automáticas vía QR y cobros con tarjeta en vivo (Checkout API).</p>
                    </div>
                    
                    <div class="settings-form-group">
                        <label class="settings-label">Cuenta de Mercado Pago (Alias, CVU o Email)</label>
                        <input type="text" name="mp_qr_account" value="<?= esc($settings['mp_qr_account'] ?? '') ?>" placeholder="Ej: codex.deposito.mp o 00000031000..." autocomplete="off" class="settings-input">
                        <span style="font-size:0.7rem; color:var(--text-muted);">Si dejas este campo vacío, el método de recarga por QR no estará disponible para los usuarios.</span>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-label">Access Token de Mercado Pago (API Credenciales)</label>
                        <input type="password" name="mp_access_token" value="<?= esc($settings['mp_access_token'] ?? '') ?>" placeholder="Ej: APP_USR-1234567890..." autocomplete="new-password" class="settings-input">
                        <span style="font-size:0.7rem; color:var(--text-muted);">Para habilitar la generación de códigos de pago automáticos y acreditación real vía Webhooks.</span>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-label">Clave Pública de Mercado Pago (Public Key)</label>
                        <input type="text" name="mp_public_key" value="<?= esc($settings['mp_public_key'] ?? '') ?>" placeholder="Ej: APP_USR-12345678-abcd..." class="settings-input">
                        <span style="font-size:0.7rem; color:var(--text-muted);">Clave pública requerida para inicializar el SDK/Bricks en frontend.</span>
                    </div>

                    <div class="switch-container">
                        <div class="switch-label-group">
                            <span class="switch-title">Habilitar Cobros por Tarjeta (Mercado Pago)</span>
                            <span class="switch-desc">Permite a los usuarios pagar directamente en la plataforma usando tarjeta de crédito/débito.</span>
                        </div>
                        <div @click="mp_card_enabled = !mp_card_enabled" 
                             :style="mp_card_enabled ? 'background-color: var(--accent-emerald)' : 'background-color: var(--border)'" 
                             class="toggle-switch">
                            <div :style="mp_card_enabled ? 'right: 3px; left: auto;' : 'left: 3px; right: auto;'" class="toggle-handle"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient" style="align-self: flex-start; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="save" style="width:16px;height:16px;"></i> Guardar Configuración MP
                    </button>
                </div>
                
            </div>
        </div>
        
    </form>
</div>

<script>
    // Initialize Lucide icons on setting tab loading
    lucide.createIcons();
</script>
