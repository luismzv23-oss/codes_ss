<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table            = 'events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'league_id',
        'stage',
        'group_name',
        'match_number',
        'home_team',
        'home_flag',
        'away_team',
        'away_flag',
        'api_fixture_id',
        'api_provider',
        'api_status',
        'api_elapsed',
        'api_last_score_sync_at',
        'score_source',
        'start_time',
        'venue',
        'status',
        'settled',
        'score_home',
        'score_away',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
