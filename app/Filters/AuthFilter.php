<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTAuth;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check Hybrid Auth
        
        $session = session();
        $isLoggedIn = $session->get('isLoggedIn');
        
        $isAuthenticated = false;
        $userRole = null;

        // 1. Check Session First
        if ($isLoggedIn) {
            $token = $request->getCookie('access_token');
            if ($token) {
                $decoded = JWTAuth::validateToken($token);
                if ($decoded) {
                    $isAuthenticated = true;
                    $userRole = $decoded->role;
                }
            }
            if (!$isAuthenticated) {
                $session->destroy();
            }
        }

        // 2. Check JWT if Session is missing or invalid
        if (!$isAuthenticated) {
            $token = $request->getCookie('access_token');
            if ($token) {
                $decoded = JWTAuth::validateToken($token);
                if ($decoded) {
                    $session->set([
                        'user_id'    => $decoded->uid,
                        'username'   => $decoded->username,
                        'role_id'    => $decoded->role,
                        'isLoggedIn' => true,
                    ]);
                    $isAuthenticated = true;
                    $userRole = $decoded->role;
                }
            }
        }

        if ($isAuthenticated) {
            // Check Roles if specified in arguments (e.g., filter => 'auth:1,2')
            if (!empty($arguments)) {
                if (!in_array($userRole, $arguments)) {
                    if ($request->isAJAX() || $request->hasHeader('HX-Request')) {
                        $response = service('response');
                        $response->setHeader('HX-Redirect', '/');
                        $response->setStatusCode(403);
                        return $response;
                    }
                    return redirect()->to('/')->with('error', 'No tiene permisos para acceder a esta seccion.');
                }
            }
            return; // All good
        }

        // Not authenticated
        
        // Handle AJAX / HTMX requests
        if ($request->isAJAX() || $request->hasHeader('HX-Request')) {
            $response = service('response');
            // Instead of redirecting an ajax call, send a 401 or HX-Redirect
            $response->setHeader('HX-Redirect', '/auth/login');
            $response->setStatusCode(401);
            return $response;
        }

        return redirect()->to('/auth/login')->with('error', 'Por favor inicie sesión para continuar.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
