<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupBracket8vos extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:bracket8vos';
    protected $description = 'Cierra 16avos y completa los partidos de 8vos del Mundial 2026.';

    public function run(array $params)
    {
        $result = (new WorldCupBracketService())->bracket8vos();
        CLI::write($result['reason'], $result['completed'] ? 'green' : 'yellow');
    }
}
