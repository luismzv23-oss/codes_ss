<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'              => CSRF::class,
        'toolbar'           => DebugToolbar::class,
        'honeypot'          => Honeypot::class,
        'invalidchars'      => InvalidChars::class,
        'secureheaders'     => SecureHeaders::class,
        'auth'              => \App\Filters\AuthFilter::class,
        'security_headers'  => \App\Filters\SecurityHeadersFilter::class,
        'jwt_auth'          => \App\Filters\JWTAuthFilter::class,
        'rate_limit'        => \App\Filters\RateLimitFilter::class,
    ];

    /**
     * Global filters applied to every request.
     */
    public array $globals = [
        'before' => [
            'invalidchars',
            // CSRF enabled for POST but excluded for JWT-auth API endpoints
            'csrf' => ['except' => [
                'api/*',
            ]],
        ],
        'after' => [
            'toolbar',
            'security_headers', // Add security headers to all responses
        ],
    ];

    /**
     * Filter by HTTP method.
     */
    public array $methods = [];

    /**
     * Filter by URI pattern.
     */
    public array $filters = [];
}
