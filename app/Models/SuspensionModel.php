<?php

namespace App\Models;

use CodeIgniter\Model;

class SuspensionModel extends Model
{
    protected $table            = 'suspensions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'reason', 'suspension_type',
        'suspended_at', 'expires_at', 'is_active', 'created_by', 'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
}
