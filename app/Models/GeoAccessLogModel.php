<?php

namespace App\Models;

use CodeIgniter\Model;

class GeoAccessLogModel extends Model
{
    protected $table = 'geo_access_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'ip_address', 'country_code', 'allowed', 'reason', 'created_at'];
}
