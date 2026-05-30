<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupBracket4tos extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:bracket4tos';
    protected $description = 'Cierra 8vos y completa los partidos de cuartos del Mundial 2026.';

    public function run(array $params)
    {
        $result = (new WorldCupBracketService())->bracket4tos();
        CLI::write($result['reason'], $result['completed'] ? 'green' : 'yellow');
    }
}
