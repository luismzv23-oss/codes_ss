<?php

namespace App\Models;

use CodeIgniter\Model;

class OddModel extends Model
{
    protected $table            = 'odds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['market_id', 'selection', 'odds_decimal', 'active', 'status'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
