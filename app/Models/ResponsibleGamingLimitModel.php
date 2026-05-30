<?php

namespace App\Models;

use CodeIgniter\Model;

class ResponsibleGamingLimitModel extends Model
{
    protected $table = 'responsible_gaming_limits';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'daily_deposit_limit',
        'monthly_deposit_limit',
        'daily_loss_limit',
        'monthly_loss_limit',
        'session_limit_minutes',
        'self_excluded_until',
        'self_exclusion_reason',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
