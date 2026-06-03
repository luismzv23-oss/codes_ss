<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_id', 'username', 'email', 'password_hash', 'is_active',
        'google_id', 'oauth_provider',
        'phone', 'country', 'birthdate', 'document_type', 'document_number',
        'kyc_status', 'is_2fa_enabled', 'last_login_at', 'last_login_ip',
        'failed_login_attempts', 'locked_until',
        // Email verification fields
        'email_verified_at', 'email_verification_token', 'email_verification_sent_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'username'      => 'required|alpha_numeric_space|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password_hash' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $beforeUpdate   = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password_hash'])) {
            // Check if it's already hashed (starts with $2y$)
            if (!str_starts_with($data['data']['password_hash'], '$2y$')) {
                $data['data']['password_hash'] = password_hash($data['data']['password_hash'], PASSWORD_DEFAULT);
            }
        }
        return $data;
    }
}
