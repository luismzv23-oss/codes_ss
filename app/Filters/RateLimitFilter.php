<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\RateLimiter;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $rateLimiter = new RateLimiter();
        $clientIP = $request->getIPAddress();

        // Chequear si IP está bloqueada
        if ($rateLimiter->isIPBlocked($clientIP)) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'IP bloqueada por razones de seguridad.'])
                ->setStatusCode(429)
                ->setHeader('Retry-After', '3600');
        }

        // Obtener el endpoint actual
        $uri = $request->getUri()->getPath();
        
        // Determinar acción basada en URI
        $action = 'api';
        if (str_contains($uri, 'login')) {
            $action = 'login';
        } elseif (str_contains($uri, 'register')) {
            $action = 'register';
        } elseif (str_contains($uri, 'deposit')) {
            $action = 'deposit';
        } elseif (str_contains($uri, 'withdraw')) {
            $action = 'withdraw';
        }

        // Identificador: puede ser IP o usuario_id
        $identifier = $request->user_id ?? $clientIP;

        // Chequear límite
        if (!$rateLimiter->checkLimit($action, (string)$identifier)) {
            $remaining = $rateLimiter->getAttemptsRemaining($action, (string)$identifier);
            
            return service('response')
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Demasiados intentos. Intenta más tarde.',
                    'remaining_attempts' => max(0, $remaining),
                ])
                ->setStatusCode(429)
                ->setHeader('Retry-After', '60');
        }

        // Registrar intento
        $rateLimiter->recordAttempt($action, (string)$identifier);

        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
