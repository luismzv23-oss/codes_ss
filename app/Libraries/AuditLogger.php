<?php

namespace App\Libraries;

use App\Models\AuditLogModel;

class AuditLogger
{
    /**
     * Registrar evento de auditoría
     */
    public static function log(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $status = 'success'
    ): void {
        $request = service('request');
        
        $model = new AuditLogModel();
        $model->insert([
            'user_id'    => $userId,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registrar evento de login fallido
     */
    public static function logFailedLogin(string $email, string $reason = 'invalid_credentials'): void
    {
        $request = service('request');
        $model = new AuditLogModel();

        $model->insert([
            'user_id'    => null,
            'action'     => 'login_failed',
            'entity'     => 'user',
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'status'     => 'failure',
            'created_at' => date('Y-m-d H:i:s'),
            'new_values' => json_encode(['email' => $email, 'reason' => $reason])
        ]);
    }

    /**
     * Registrar evento de login exitoso
     */
    public static function logSuccessfulLogin(int $userId): void
    {
        $request = service('request');
        $model = new AuditLogModel();

        $model->insert([
            'user_id'    => $userId,
            'action'     => 'login_success',
            'entity'     => 'user',
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'status'     => 'success',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registrar retiro de fondos
     */
    public static function logWithdrawal(int $userId, float $amount): void
    {
        self::log($userId, 'withdrawal', 'wallet', null, null, [
            'amount' => $amount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registrar depósito
     */
    public static function logDeposit(int $userId, float $amount): void
    {
        self::log($userId, 'deposit', 'wallet', null, null, [
            'amount' => $amount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registrar apuesta grande (para detección de fraude)
     */
    public static function logLargeBet(int $userId, float $amount, int $eventId): void
    {
        self::log($userId, 'large_bet_placed', 'bet', $eventId, null, [
            'amount' => $amount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener logs de usuario
     */
    public static function getUserLogs(int $userId, int $limit = 50): array
    {
        $model = new AuditLogModel();
        return $model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Obtener logs por IP
     */
    public static function getLogsByIP(string $ip, int $limit = 50): array
    {
        $model = new AuditLogModel();
        return $model
            ->where('ip_address', $ip)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Obtener logs por acción
     */
    public static function getLogsByAction(string $action, int $limit = 100): array
    {
        $model = new AuditLogModel();
        return $model
            ->where('action', $action)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Obtener eventos fallidos/sospechosos
     */
    public static function getSuspiciousLogs(int $limit = 50): array
    {
        $model = new AuditLogModel();
        return $model
            ->where('status !=', 'success')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
