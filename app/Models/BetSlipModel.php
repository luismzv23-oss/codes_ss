<?php

namespace App\Models;

use CodeIgniter\Model;

class BetSlipModel extends Model
{
    protected $table            = 'bet_slips';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'total_odds', 'stake', 'potential_payout', 'status', 'is_builder', 'correlation_discount', 'cashout_value', 'cashed_out_at'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
