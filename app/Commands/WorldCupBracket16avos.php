<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupBracket16avos extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:bracket16avos';
    protected $description = 'Cierra fase de grupos y completa los partidos de 16avos del Mundial 2026.';

    public function run(array $params)
    {
        $result = (new WorldCupBracketService())->bracket16avos();
        CLI::write($result['reason'], $result['completed'] ? 'green' : 'yellow');
    }
}
