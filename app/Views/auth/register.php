<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>

<h1 class="logo-text">Registro</h1>
<p class="subtitle">Cree una nueva cuenta</p>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <?= esc($error) ?>
    </div>
<?php elseif (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul style="margin: 0; padding-left: 1rem;">
        <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <li><?= esc($error) ?></li>
        <?php endforeach ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (isset($validation)): ?>
    <div class="alert alert-error">
        <ul style="margin: 0; padding-left: 1rem;">
        <?php foreach ($validation->getErrors() as $err): ?>
            <li><?= esc($err) ?></li>
        <?php endforeach ?>
        </ul>
    </div>
<?php endif; ?>

<form hx-post="/auth/registerAction" 
      hx-target="body" 
      hx-swap="outerHTML"
      x-data="{ 
          loading: false,
          email: '<?= old('email') ?>',
          emailError: '',
          birthdate: '<?= old('birthdate') ?>',
          birthdateError: '',
          
          validateEmail() {
              if (!this.email) {
                  this.emailError = '';
                  return;
              }
              const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
              if (!re.test(this.email)) {
                  this.emailError = 'Por favor, ingrese un correo electrónico válido.';
              } else {
                  this.emailError = '';
              }
          },
          
          validateAge() {
              if (!this.birthdate) {
                  this.birthdateError = '';
                  return;
              }
              const dob = new Date(this.birthdate);
              const today = new Date();
              let age = today.getFullYear() - dob.getFullYear();
              const m = today.getMonth() - dob.getMonth();
              if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                  age--;
              }
              if (age < 18) {
                  this.birthdateError = 'Debe ser mayor de 18 años para poder registrarse y apostar.';
              } else {
                  this.birthdateError = '';
              }
          }
      }"
      @submit="validateEmail(); validateAge(); if (emailError || birthdateError) { $event.preventDefault(); return; }; loading = true"
      @htmx:after-request="loading = false">
    
    <?= csrf_field() ?>

    <div class="register-grid">
        <div class="form-group">
            <label for="username" class="form-label">Usuario</label>
            <input type="text" id="username" name="username" class="form-input" required autocomplete="username" value="<?= old('username') ?>">
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-input" required autocomplete="email" x-model="email" @blur="validateEmail()" @change="validateEmail()">
            <span x-show="emailError" x-text="emailError" style="color: var(--error-color); font-size: 0.8rem; display: block; margin-top: 0.25rem;"></span>
        </div>

        <!-- Teléfono -->
        <div class="form-group grid-col-full">
            <label class="form-label">Teléfono</label>
            <div class="phone-group">
                <select name="phone_country" class="form-input phone-country" required>
                    <option value="" disabled <?= !old('phone_country') ? 'selected' : '' ?>>País</option>
                    <option value="54" <?= old('phone_country') === '54' ? 'selected' : '' ?>>+54 (AR)</option>
                    <option value="598" <?= old('phone_country') === '598' ? 'selected' : '' ?>>+598 (UY)</option>
                    <option value="55" <?= old('phone_country') === '55' ? 'selected' : '' ?>>+55 (BR)</option>
                    <option value="56" <?= old('phone_country') === '56' ? 'selected' : '' ?>>+56 (CL)</option>
                    <option value="595" <?= old('phone_country') === '595' ? 'selected' : '' ?>>+595 (PY)</option>
                    <option value="591" <?= old('phone_country') === '591' ? 'selected' : '' ?>>+591 (BO)</option>
                    <option value="51" <?= old('phone_country') === '51' ? 'selected' : '' ?>>+51 (PE)</option>
                    <option value="57" <?= old('phone_country') === '57' ? 'selected' : '' ?>>+57 (CO)</option>
                    <option value="58" <?= old('phone_country') === '58' ? 'selected' : '' ?>>+58 (VE)</option>
                    <option value="593" <?= old('phone_country') === '593' ? 'selected' : '' ?>>+593 (EC)</option>
                    <option value="52" <?= old('phone_country') === '52' ? 'selected' : '' ?>>+52 (MX)</option>
                    <option value="34" <?= old('phone_country') === '34' ? 'selected' : '' ?>>+34 (ES)</option>
                </select>
                <input type="text" name="phone_area" class="form-input phone-area" placeholder="Cód. Área (ej. 11)" required maxlength="4" value="<?= old('phone_area') ?>" @input="$el.value = $el.value.replace(/[^0-9]/g, '')" title="Solo números (máximo 4 dígitos)">
                <input type="text" name="phone_number" class="form-input phone-number" placeholder="Número (ej. 1234567)" required maxlength="7" value="<?= old('phone_number') ?>" @input="$el.value = $el.value.replace(/[^0-9]/g, '')" title="Solo números (máximo 7 dígitos)">
            </div>
        </div>

        <!-- Nacionalidad -->
        <div class="form-group">
            <label for="country" class="form-label">Nacionalidad</label>
            <select id="country" name="country" class="form-input" required>
                <option value="" disabled <?= !old('country') ? 'selected' : '' ?>>Seleccione nacionalidad</option>
                <option value="AR" <?= old('country') === 'AR' ? 'selected' : '' ?>>Argentina</option>
                <option value="UY" <?= old('country') === 'UY' ? 'selected' : '' ?>>Uruguay</option>
                <option value="BR" <?= old('country') === 'BR' ? 'selected' : '' ?>>Brasil</option>
                <option value="CL" <?= old('country') === 'CL' ? 'selected' : '' ?>>Chile</option>
                <option value="PY" <?= old('country') === 'PY' ? 'selected' : '' ?>>Paraguay</option>
                <option value="BO" <?= old('country') === 'BO' ? 'selected' : '' ?>>Bolivia</option>
                <option value="PE" <?= old('country') === 'PE' ? 'selected' : '' ?>>Perú</option>
                <option value="CO" <?= old('country') === 'CO' ? 'selected' : '' ?>>Colombia</option>
                <option value="VE" <?= old('country') === 'VE' ? 'selected' : '' ?>>Venezuela</option>
                <option value="EC" <?= old('country') === 'EC' ? 'selected' : '' ?>>Ecuador</option>
                <option value="MX" <?= old('country') === 'MX' ? 'selected' : '' ?>>México</option>
                <option value="ES" <?= old('country') === 'ES' ? 'selected' : '' ?>>España</option>
            </select>
        </div>

        <!-- DNI/CUIT -->
        <div class="form-group">
            <label for="document_number" class="form-label">Número DNI/CUIT</label>
            <input type="text" id="document_number" name="document_number" class="form-input" required pattern="[0-9]+" title="Solo números" placeholder="Ej. 35123456 o 20351234569" value="<?= old('document_number') ?>">
        </div>

        <!-- Fecha de Nacimiento -->
        <div class="form-group grid-col-full">
            <label for="birthdate" class="form-label">Fecha de Nacimiento</label>
            <?php $maxBirthdate = date('Y-m-d', strtotime('-18 years')); ?>
            <input type="date" id="birthdate" name="birthdate" class="form-input" required max="<?= $maxBirthdate ?>" x-model="birthdate" @blur="validateAge()" @change="validateAge()">
            <small class="form-text text-muted" x-show="!birthdateError" style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">Debe ser mayor de 18 años para poder apostar.</small>
            <span x-show="birthdateError" x-text="birthdateError" style="color: var(--error-color); font-size: 0.85rem; display: block; margin-top: 0.25rem;"></span>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" class="form-input" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-input" required autocomplete="new-password">
        </div>

        <!-- Políticas de Privacidad -->
        <div class="form-group grid-col-full" style="display: flex; align-items: flex-start; gap: 0.75rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">
            <input type="checkbox" id="privacy_policy" name="privacy_policy" value="1" required style="width: 1.2rem; height: 1.2rem; accent-color: var(--primary-color); margin-top: 0.1rem; cursor: pointer;">
            <label for="privacy_policy" class="form-label" style="font-weight: 400; font-size: 0.875rem; color: var(--text-muted); cursor: pointer; user-select: none; margin-bottom: 0;">
                Acepto las <a href="#" style="color: var(--primary-color); text-decoration: none;">políticas de privacidad y seguridad</a> del sistema.
            </label>
        </div>
    </div>

    <button type="submit" class="btn-primary" :disabled="loading" :style="loading ? 'opacity: 0.7; cursor: not-allowed;' : ''">
        <span class="btn-text" x-show="!loading">Crear Cuenta</span>
        <span class="htmx-indicator" x-show="loading">
            <svg style="width: 20px; height: 20px; animation: spin 1s linear infinite; margin: 0 auto; display: block;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.25"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
    </button>
</form>

<div class="auth-links">
    <p>¿Ya tiene una cuenta? <a href="/auth/login" hx-get="/auth/login" hx-target="#auth-content" hx-swap="innerHTML transition:true" hx-push-url="true">Inicie sesión</a></p>
</div>

<style>
    /* Expand register form container */
    .auth-container {
        max-width: 650px !important;
        transition: max-width 0.3s ease;
    }
    
    .register-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
    
    .grid-col-full {
        grid-column: span 2;
    }
    
    /* Phone input grouping */
    .phone-group {
        display: flex;
        gap: 0.5rem;
    }
    
    .phone-country {
        flex: 0 0 35%;
        text-align: left;
    }
    
    .phone-area {
        flex: 0 0 25%;
        text-align: center;
    }
    
    .phone-number {
        flex: 1;
    }
    
    /* Select styling */
    select.form-input {
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23f8fafc' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.25em;
        padding-right: 2.5rem;
        cursor: pointer;
    }
    
    select.form-input option {
        background-color: #0f172a;
        color: #f8fafc;
    }

    @media (max-width: 600px) {
        .register-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .grid-col-full {
            grid-column: span 1;
        }
        .phone-group {
            flex-direction: row;
        }
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<?= $this->endSection() ?>
