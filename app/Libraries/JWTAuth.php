<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTAuth
{
    /**
     * Generate a new JWT token for a user
     */
    public static function generateToken(array $userData): string
    {
        $key = self::getKey();
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600 * 8; // 8 hours valid
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'uid' => $userData['id'],
            'role' => $userData['role_id'],
            'username' => $userData['username'],
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Validate and decode the JWT token
     */
    public static function validateToken(string $token)
    {
        try {
            $key = self::getKey();
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the JWT secret key
     */
    private static function getKey(): string
    {
        $key = getenv('JWT_SECRET');
        if (empty($key)) {
            // Fallback for development, in production it must be in .env
            $key = 'super_secret_codex_ss_jwt_key_2026';
        }
        return $key;
    }
}
