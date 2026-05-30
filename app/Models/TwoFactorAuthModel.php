<?php

namespace App\Models;

use CodeIgniter\Model;

class TwoFactorAuthModel extends Model
{
    protected $table            = 'two_factor_auths';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'method', 'secret', 'backup_codes', 'is_verified', 'verified_at'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
