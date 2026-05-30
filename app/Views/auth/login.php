<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>

<h1 class="logo-text">Codex SS</h1>
<p class="subtitle">Ingrese a su cuenta para continuar</p>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <?= esc($error) ?>
    </div>
<?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-error">
        <?= session()->getFlashdata('error') ?>
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

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('message') ?>
    </div>
<?php endif; ?>

<form hx-post="/auth/loginAction" 
      hx-target="body" 
      hx-swap="outerHTML"
      x-data="{ loading: false }"
      @submit="loading = true"
      @htmx:after-request="loading = false">
    
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="username" class="form-label">Usuario o Email</label>
        <input type="text" id="username" name="username" class="form-input" required autocomplete="username" value="<?= old('username') ?>" autofocus>
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
    </div>

    <button type="submit" class="btn-primary" :disabled="loading" :style="loading ? 'opacity: 0.7; cursor: not-allowed;' : ''">
        <span class="btn-text" x-show="!loading">Iniciar Sesión</span>
        <span class="htmx-indicator" x-show="loading">
            <svg style="width: 20px; height: 20px; animation: spin 1s linear infinite; margin: 0 auto; display: block;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.25"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
    </button>
</form>

<div class="auth-links">
    <p>¿No tiene una cuenta? <a href="/auth/register" hx-get="/auth/register" hx-target="#auth-content" hx-swap="innerHTML transition:true" hx-push-url="true">Regístrese aquí</a></p>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<?= $this->endSection() ?>
