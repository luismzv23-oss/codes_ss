<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupBracketSemis extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:bracketsemis';
    protected $description = 'Cierra cuartos y completa los partidos de semifinales del Mundial 2026.';

    public function run(array $params)
    {
        $result = (new WorldCupBracketService())->bracketSemifinales();
        CLI::write($result['reason'], $result['completed'] ? 'green' : 'yellow');
    }
}
