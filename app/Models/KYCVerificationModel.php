<?php

namespace App\Models;

use CodeIgniter\Model;

class KYCVerificationModel extends Model
{
    protected $table            = 'kyc_verifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'status', 'document_type', 'document_number',
        'document_image', 'verified_at', 'verified_by', 'rejection_reason', 'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
