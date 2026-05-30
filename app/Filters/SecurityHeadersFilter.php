<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SecurityHeadersFilter — Adds enterprise-grade security headers.
 * Covers: XSS, Clickjacking, MIME sniffing, Referrer policy,
 * Content-Security-Policy, Permissions-Policy, and CORS.
 */
class SecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // CORS — Only allow same-origin in production
        $allowedOrigins = [
            env('app.baseURL', 'http://localhost:8080'),
        ];

        $origin = $request->getHeaderLine('Origin');
        if ($origin && in_array(rtrim($origin, '/'), array_map(fn($o) => rtrim($o, '/'), $allowedOrigins))) {
            service('response')
                ->setHeader('Access-Control-Allow-Origin', $origin)
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-TOKEN, HX-Request, X-Requested-With')
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->setHeader('Access-Control-Max-Age', '3600');
        }

        // Handle CORS preflight
        if ($request->getMethod() === 'options') {
            $response = service('response');
            $response->setStatusCode(204);
            return $response;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ── Anti-XSS ──
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // ── Anti-Clickjacking ──
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');

        // ── Referrer Policy ──
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── Permissions Policy (restrict sensitive APIs) ──
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // ── Content-Security-Policy ──
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' https://ui-avatars.com data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->setHeader('Content-Security-Policy', $csp);

        // ── Strict-Transport-Security (only in production) ──
        if (env('CI_ENVIRONMENT') === 'production') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
