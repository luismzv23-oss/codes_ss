<?php

namespace App\Models;

use CodeIgniter\Model;

class LeagueModel extends Model
{
    protected $table            = 'leagues';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sport_id', 'name', 'country', 'active', 'sort_order'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
