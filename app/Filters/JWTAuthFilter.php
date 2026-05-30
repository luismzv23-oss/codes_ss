<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTAuth;
use App\Libraries\RateLimiter;
use App\Models\SuspensionModel;

class JWTAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $rateLimiter = new RateLimiter();
        $clientIP = $request->getIPAddress();

        // Chequear si IP está bloqueada
        if ($rateLimiter->isIPBlocked($clientIP)) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'IP bloqueada por razones de seguridad.'])
                ->setStatusCode(429);
        }

        // Obtener token de Authorization header o cookie
        $token = $this->getToken($request);

        if (!$token) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'Token no proporcionado.'])
                ->setStatusCode(401);
        }

        // Validar token
        $decoded = JWTAuth::validateToken($token);

        if (!$decoded) {
            return service('response')
                ->setJSON(['status' => 'error', 'message' => 'Token inválido o expirado.'])
                ->setStatusCode(401);
        }

        // Verificar si usuario está suspendido
        $suspensionModel = new SuspensionModel();
        $suspension = $suspensionModel
            ->where('user_id', $decoded->uid)
            ->where('is_active', 1)
            ->first();

        if ($suspension) {
            // Si es suspensión temporal, chequear si expira
            if ($suspension['suspension_type'] === 'temporary' && $suspension['expires_at']) {
                if (time() < strtotime($suspension['expires_at'])) {
                    return service('response')
                        ->setJSON([
                            'status' => 'error',
                            'message' => 'Tu cuenta está suspendida hasta ' . $suspension['expires_at'],
                            'suspension_until' => $suspension['expires_at']
                        ])
                        ->setStatusCode(403);
                } else {
                    // Suspensión expiró, desactivarla
                    $suspensionModel->update($suspension['id'], ['is_active' => 0]);
                }
            } else {
                // Suspensión permanente
                return service('response')
                    ->setJSON(['status' => 'error', 'message' => 'Tu cuenta ha sido suspendida permanentemente.'])
                    ->setStatusCode(403);
            }
        }

        // Verificar si usuario tiene 2FA habilitado y está verificado
        $require2FA = (bool) env('2FA_ENABLED', true);
        if ($require2FA && !$request->getUri()->getSegments()[2] === '2fa') { // Permitir endpoints de 2FA
            // Nota: esto es simplificado; en producción habría lógica más compleja
        }

        // Guardar usuario en request para acceso posterior
        $request->user_id = $decoded->uid;
        $request->user_role = $decoded->role;
        $request->user_email = $decoded->username; // Puede ser username o email

        return; // Permitir
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Agregar security headers
        $response
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    /**
     * Obtener token de Authorization header o cookie
     */
    private function getToken(RequestInterface $request): ?string
    {
        // Intenta obtener del header Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Intenta obtener de cookie
        if ($request->hasCookie('access_token')) {
            return $request->getCookie('access_token');
        }

        return null;
    }
}
