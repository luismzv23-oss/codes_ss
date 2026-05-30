<?php

namespace App\Controllers\Api;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\TwoFactorAuth;
use App\Libraries\RateLimiter;
use App\Libraries\AuditLogger;
use App\Models\TwoFactorAuthModel;
use App\Models\KYCVerificationModel;
use App\Models\UserModel;

class SecurityController extends \CodeIgniter\Controller
{
    use ResponseTrait;

    /**
     * POST /api/security/2fa/enable
     * Inicia el proceso de setup de 2FA (TOTP)
     */
    public function enable2FA()
    {
        $userId = $this->request->user_id ?? null;
        if (!$userId) {
            return $this->respond(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->respond(['status' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        // Generar nuevo secret
        $secretData = TwoFactorAuth::generateSecret($user['email']);

        // Guardar temporalmente en sesión (sin verificar aún)
        session()->set('2fa_pending_secret', $secretData['secret']);
        session()->set('2fa_pending_backup_codes', $secretData['backup_codes']);

        AuditLogger::log($userId, '2fa_setup_initiated', 'user', $userId);

        return $this->respond([
            'status' => 'success',
            'message' => '2FA setup iniciado. Escanea el código QR o ingresa el secret.',
            'data' => [
                'secret'           => $secretData['secret'],
                'qr_code_url'      => $secretData['qr_code_url'],
                'provisioning_uri' => $secretData['provisioning_uri'],
                'backup_codes'     => $secretData['backup_codes'],
            ]
        ]);
    }

    /**
     * POST /api/security/2fa/verify
     * Verifica el código TOTP para completar setup
     */
    public function verify2FA()
    {
        $userId = $this->request->user_id ?? null;
        if (!$userId) {
            return $this->respond(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $json = $this->request->getJSON(true);
        $code = $json['code'] ?? null;

        if (!$code || strlen(preg_replace('/[^0-9]/', '', $code)) !== 6) {
            return $this->respond(['status' => 'error', 'message' => 'Código inválido'], 400);
        }

        // Obtener secret del pending
        $pendingSecret = session()->get('2fa_pending_secret');
        $backupCodes = session()->get('2fa_pending_backup_codes');

        if (!$pendingSecret) {
            return $this->respond(['status' => 'error', 'message' => 'Setup de 2FA no iniciado'], 400);
        }

        // Validar código TOTP
        if (!TwoFactorAuth::verifyToken($pendingSecret, $code)) {
            return $this->respond(['status' => 'error', 'message' => 'Código incorrecto o expirado'], 400);
        }

        // Guardar en BD
        $model = new TwoFactorAuthModel();
        $model->where('user_id', $userId)->delete(); // Eliminar anterior si existe

        $model->insert([
            'user_id'      => $userId,
            'method'       => 'totp',
            'secret'       => $pendingSecret,
            'backup_codes' => json_encode($backupCodes),
            'is_verified'  => 1,
            'verified_at'  => date('Y-m-d H:i:s')
        ]);

        // Actualizar usuario
        $userModel = new UserModel();
        $userModel->update($userId, ['is_2fa_enabled' => 1]);

        // Limpiar sesión
        session()->remove('2fa_pending_secret');
        session()->remove('2fa_pending_backup_codes');

        AuditLogger::log($userId, '2fa_enabled', 'user', $userId);

        return $this->respond([
            'status' => 'success',
            'message' => '2FA habilitado exitosamente',
            'backup_codes' => $backupCodes
        ]);
    }

    /**
     * POST /api/security/2fa/disable
     * Deshabilita 2FA del usuario
     */
    public function disable2FA()
    {
        $userId = $this->request->user_id ?? null;
        if (!$userId) {
            return $this->respond(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $json = $this->request->getJSON(true);
        $password = $json['password'] ?? null;

        if (!$password) {
            return $this->respond(['status' => 'error', 'message' => 'Contraseña requerida'], 400);
        }

        // Verificar contraseña
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!password_verify($password, $user['password_hash'])) {
            return $this->respond(['status' => 'error', 'message' => 'Contraseña incorrecta'], 400);
        }

        // Deshabilitar
        $model = new TwoFactorAuthModel();
        $model->where('user_id', $userId)->delete();

        $userModel->update($userId, ['is_2fa_enabled' => 0]);

        AuditLogger::log($userId, '2fa_disabled', 'user', $userId);

        return $this->respond(['status' => 'success', 'message' => '2FA deshabilitado']);
    }

    /**
     * GET /api/security/2fa/status
     * Obtiene estado de 2FA del usuario
     */
    public function get2FAStatus()
    {
        $userId = $this->request->user_id ?? null;
        if (!$userId) {
            return $this->respond(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $model = new TwoFactorAuthModel();
        $record = $model->where('user_id', $userId)->first();

        return $this->respond([
            'status' => 'success',
            'data' => [
                'is_enabled'  => $record && $record['is_verified'],
                'method'      => $record['method'] ?? null,
                'verified_at' => $record['verified_at'] ?? null,
            ]
        ]);
    }

    /**
     * POST /api/security/kyc/submit
     * Envía documento para verificación KYC
     */
    public function submitKYC()
    {
        $userId = $this->request->user_id ?? null;
        if (!$userId) {
            return $this->respond(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $json = $this->request->getJSON(true);
        $documentType = $json['document_type'] ?? null; // dni, passport, license
        $documentNumber = $json['document_number'] ?? null;

        if (!$documentType || !$documentNumber) {
            return $this->respond(['status' => 'error', 'message' => 'Datos incompletos'], 400);
        }

        $validTypes = ['dni', 'passport', 'license'];
        if (!in_array($documentType, $validTypes)) {
            return $this->respond(['status' => 'error', 'message' => 'Tipo de documento inválido'], 400);
        }

        // Guardar KYC como pending
        $model = new KYCVerificationModel();
        $existing = $model->where('user_id', $userId)->first();

        $data = [
            'user_id'       => $userId,
            'status'        => 'pending',
            'document_type' => $documentType,
            'document_number' => $documentNumber,
        ];

        if ($existing) {
            $model->update($existing['id'], $data);
        } else {
            $model->insert($data);
        }

        // Actualizar usuario
        $userModel = new UserModel();
        $userModel->update($userId, [
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'kyc_status' => 'pending'
        ]);

        AuditLogger::log($userId, 'kyc_submitted', 'user', $userId);

        return $this->respond([
            'status' => 'success',
            'message' => 'KYC enviado para verificación. Revisaremos en 24-48 horas.',
        ]);
    }

    /**
     * GET /api/security/kyc/status
     * Obtiene estado de KYC del usuario
     */
    public function getKYCStatus()
    {
        $userId = $this->request->user_id ?? null;
        if (!$userId) {
            return $this->respond(['status' => 'error', 'message' => 'No autenticado'], 401);
        }

        $model = new KYCVerificationModel();
        $record = $model->where('user_id', $userId)->first();

        if (!$record) {
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'status' => 'not_submitted',
                    'message' => 'No has enviado documentos de verificación',
                ]
            ]);
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'status'           => $record['status'],
                'document_type'    => $record['document_type'],
                'verified_at'      => $record['verified_at'],
                'rejection_reason' => $record['rejection_reason'],
            ]
        ]);
    }
}
