<?php

namespace App\Models;

use CodeIgniter\Model;

class WithdrawalRequestModel extends Model
{
    protected $table = 'withdrawal_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'wallet_id',
        'amount',
        'target_account',
        'account_holder',
        'account_document',
        'own_account_confirmed',
        'status',
        'user_note',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
