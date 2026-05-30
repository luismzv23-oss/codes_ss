<?php

namespace App\Libraries;

use App\Models\LoginAttemptModel;
use App\Models\UserModel;

class RateLimiter
{
    private $cache;
    private $rules = [
        'login'    => ['max_attempts' => 5, 'window' => 900],      // 5 intentos en 15 minutos
        'register' => ['max_attempts' => 3, 'window' => 3600],     // 3 registros en 1 hora
        'deposit'  => ['max_attempts' => 10, 'window' => 3600],    // 10 depósitos en 1 hora
        'withdraw' => ['max_attempts' => 5, 'window' => 3600],     // 5 retiros en 1 hora
        'api'      => ['max_attempts' => 100, 'window' => 60],     // 100 requests en 1 minuto
    ];

    public function __construct()
    {
        $this->cache = cache();
    }

    /**
     * Verificar si IP está bloqueada
     */
    public function isIPBlocked(string $ip): bool
    {
        $blockedKey = 'rate_limit:blocked_ip:' . $ip;
        return $this->cache->get($blockedKey) !== null;
    }

    /**
     * Registrar intento fallido de login
     */
    public function recordFailedLogin(string $email, string $ip, string $userAgent, ?string $reason = null): void
    {
        $model = new LoginAttemptModel();
        
        // Obtener usuario si existe
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();
        $userId = $user['id'] ?? null;

        $model->insert([
            'user_id'      => $userId,
            'email'        => $email,
            'ip_address'   => $ip,
            'user_agent'   => $userAgent,
            'success'      => 0,
            'failed_reason' => $reason,
            'attempted_at' => date('Y-m-d H:i:s')
        ]);

        // Incrementar contador
        $attemptKey = 'rate_limit:login:' . $email;
        $attempts = $this->cache->get($attemptKey) ?? 0;
        $this->cache->save($attemptKey, $attempts + 1, $this->rules['login']['window']);

        // Si usuario existe, incrementar contador de intentos fallidos y bloquear si es necesario
        if ($userId) {
            $this->cache->save('rate_limit:user:' . $userId, $attempts + 1, $this->rules['login']['window']);
            
            // Bloquear usuario si alcanza máximo
            if ($attempts + 1 >= $this->rules['login']['max_attempts']) {
                $lockTime = 15 * 60; // 15 minutos
                $userModel->update($userId, [
                    'failed_login_attempts' => $attempts + 1,
                    'locked_until' => date('Y-m-d H:i:s', time() + $lockTime)
                ]);
            }
        }
    }

    /**
     * Registrar intento exitoso de login
     */
    public function recordSuccessfulLogin(int $userId, string $ip): void
    {
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if ($user) {
            $model = new LoginAttemptModel();
            $model->insert([
                'user_id'     => $userId,
                'email'       => $user['email'],
                'ip_address'  => $ip,
                'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'success'     => 1,
                'attempted_at' => date('Y-m-d H:i:s')
            ]);

            // Limpiar contadores
            $attemptKey = 'rate_limit:login:' . $user['email'];
            $this->cache->delete($attemptKey);
            $this->cache->delete('rate_limit:user:' . $userId);

            // Resetear intentos fallidos y lock
            $userModel->update($userId, [
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $ip
            ]);
        }
    }

    /**
     * Chequear si usuario está bloqueado
     */
    public function isUserLocked(int $userId): bool
    {
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || !$user['locked_until']) {
            return false;
        }

        $lockedUntil = strtotime($user['locked_until']);
        if ($lockedUntil > time()) {
            return true; // Todavía bloqueado
        }

        // Desbloquear
        $userModel->update($userId, [
            'locked_until' => null,
            'failed_login_attempts' => 0
        ]);

        return false;
    }

    /**
     * Chequear intentos por endpoint/acción
     */
    public function checkLimit(string $action, string $identifier): bool
    {
        if (!isset($this->rules[$action])) {
            return true; // Acción no limitada
        }

        $rule = $this->rules[$action];
        $key = 'rate_limit:' . $action . ':' . $identifier;
        
        $attempts = $this->cache->get($key) ?? 0;
        
        return $attempts < $rule['max_attempts'];
    }

    /**
     * Incrementar contador de intento
     */
    public function recordAttempt(string $action, string $identifier): void
    {
        if (!isset($this->rules[$action])) {
            return;
        }

        $rule = $this->rules[$action];
        $key = 'rate_limit:' . $action . ':' . $identifier;
        
        $attempts = $this->cache->get($key) ?? 0;
        $this->cache->save($key, $attempts + 1, $rule['window']);
    }

    /**
     * Obtener intentos restantes
     */
    public function getAttemptsRemaining(string $action, string $identifier): int
    {
        if (!isset($this->rules[$action])) {
            return -1; // Sin límite
        }

        $rule = $this->rules[$action];
        $key = 'rate_limit:' . $action . ':' . $identifier;
        
        $attempts = $this->cache->get($key) ?? 0;
        return max(0, $rule['max_attempts'] - $attempts);
    }

    /**
     * Resetear límite para usuario/IP/email
     */
    public function resetLimit(string $action, string $identifier): void
    {
        $key = 'rate_limit:' . $action . ':' . $identifier;
        $this->cache->delete($key);
    }

    /**
     * Bloquear IP (para intentos masivos de bruteforce)
     */
    public function blockIP(string $ip, int $durationSeconds = 3600): void
    {
        $blockedKey = 'rate_limit:blocked_ip:' . $ip;
        $this->cache->save($blockedKey, true, $durationSeconds);
    }
}
