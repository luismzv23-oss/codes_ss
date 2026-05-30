<?php

namespace App\Models;

use CodeIgniter\Model;

class BetSelectionModel extends Model
{
    protected $table            = 'bet_selections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['bet_slip_id', 'odd_id', 'odd_at_bet_time', 'status'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
