<?php

namespace App\Models;

use CodeIgniter\Model;

class StagedEventModel extends Model
{
    protected $table            = 'staged_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'batch_id',
        'sport_key',
        'league_name',
        'league_country',
        'stage',
        'group_name',
        'home_team',
        'away_team',
        'score_home',
        'score_away',
        'start_time',
        'venue',
        'venue_url',
        'odds_data',
        'status',
        'reviewed_by',
        'reviewed_at',
        'event_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
