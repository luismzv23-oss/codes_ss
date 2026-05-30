<?php

namespace App\Libraries;

use App\Models\TwoFactorAuthModel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class TwoFactorAuth
{
    private const TOTP_PERIOD = 30;      // 30 segundos
    private const TOTP_DIGITS = 6;       // 6 dígitos
    private const WINDOW = 1;            // Permitir código anterior/siguiente

    /**
     * Generar un nuevo secret TOTP
     * Retorna un array con secret, QR code URL y backup codes
     */
    public static function generateSecret(string $userEmail, string $appName = 'Codex SS'): array
    {
        // Generar secret aleatorio (32 caracteres base32)
        $secret = self::generateRandomSecret();

        // Generar códigos de backup
        $backupCodes = self::generateBackupCodes(8);

        // Generar URL de provisioning TOTP (compatible con Google Authenticator)
        $otpProvisioningUri = self::getProvisioningUri($userEmail, $secret, $appName);

        // Generar código QR
        $qrCodeUrl = self::generateQRCode($otpProvisioningUri);

        return [
            'secret'           => $secret,
            'backup_codes'     => $backupCodes,
            'qr_code_url'      => $qrCodeUrl,
            'provisioning_uri' => $otpProvisioningUri
        ];
    }

    /**
     * Validar un código TOTP del usuario
     */
    public static function verifyToken(string $secret, string $token): bool
    {
        $token = preg_replace('/[^0-9]/', '', $token); // Remover espacios
        
        if (strlen($token) !== self::TOTP_DIGITS) {
            return false;
        }

        $time = floor(time() / self::TOTP_PERIOD);

        // Permitir código actual y anteriores/posteriores (ventana)
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $expectedToken = self::generateToken($secret, $time + $i);
            if (hash_equals($expectedToken, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validar contra backup codes
     */
    public static function verifyBackupCode(string $secret, string $backupCodesJson, string $code): bool
    {
        $codes = json_decode($backupCodesJson, true) ?? [];
        
        $code = preg_replace('/[^a-zA-Z0-9]/', '', $code);

        foreach ($codes as $key => $storedCode) {
            if (hash_equals(self::hashCode($storedCode), self::hashCode($code))) {
                // Remover código usado
                unset($codes[$key]);
                return true; // Retornar códigos restantes
            }
        }

        return false;
    }

    /**
     * Generar un nuevo token TOTP
     */
    private static function generateToken(string $secret, int $time): string
    {
        $secretBinary = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('N2', 0, $time), $secretBinary, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff) % pow(10, self::TOTP_DIGITS);

        return str_pad((string)$code, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Generar secret aleatorio (32 caracteres base32)
     */
    private static function generateRandomSecret(): string
    {
        $bytes = random_bytes(32);
        return self::base32Encode($bytes);
    }

    /**
     * Generar códigos de backup
     */
    private static function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = bin2hex(random_bytes(4)); // 8 caracteres hexadecimales
            $codes[] = strtoupper($code);
        }
        return $codes;
    }

    /**
     * URI de provisioning TOTP (RFC 6238)
     * Compatible con Google Authenticator, Microsoft Authenticator, Authy, etc.
     */
    private static function getProvisioningUri(string $accountName, string $secret, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&period=%d&digits=%d&algorithm=SHA1',
            rawurlencode($issuer . ':' . $accountName),
            rawurlencode($secret),
            rawurlencode($issuer),
            self::TOTP_PERIOD,
            self::TOTP_DIGITS
        );
    }

    /**
     * Generar código QR basado en provisioning URI
     */
    private static function generateQRCode(string $uri): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'   => QRCode::ECC_H,
        ]);

        $qr = new QRCode($options);
        return $qr->render($uri);
    }

    /**
     * Base32 Encode (RFC 4648)
     */
    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $binary = str_pad($binary, ceil(strlen($binary) / 5) * 5, '0', STR_PAD_RIGHT);
        $encoded = '';

        foreach (str_split($binary, 5) as $chunk) {
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    /**
     * Base32 Decode (RFC 4648)
     */
    private static function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        foreach (str_split(strtoupper($data)) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid base32 character: ' . $char);
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $binary = substr($binary, 0, floor(strlen($binary) / 8) * 8);
        $decoded = '';

        foreach (str_split($binary, 8) as $chunk) {
            $decoded .= chr(bindec($chunk));
        }

        return $decoded;
    }

    /**
     * Hash de código para almacenamiento seguro
     */
    private static function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    /**
     * Verificar si usuario tiene 2FA habilitado
     */
    public static function isEnabledForUser(int $userId): bool
    {
        $model = new TwoFactorAuthModel();
        $record = $model->where('user_id', $userId)->first();
        return $record && $record['is_verified'];
    }

    /**
     * Obtener método de 2FA del usuario
     */
    public static function getUserMethod(int $userId): ?string
    {
        $model = new TwoFactorAuthModel();
        $record = $model->where('user_id', $userId)->first();
        return $record['method'] ?? null;
    }
}
