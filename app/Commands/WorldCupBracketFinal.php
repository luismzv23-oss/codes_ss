<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupBracketFinal extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:bracketfinal';
    protected $description = 'Cierra semifinales y completa final y tercer puesto del Mundial 2026.';

    public function run(array $params)
    {
        $result = (new WorldCupBracketService())->bracketFinal();
        CLI::write($result['reason'], $result['completed'] ? 'green' : 'yellow');

        $champion = (new WorldCupBracketService())->champion();
        if ($champion) {
            CLI::write('Campeon: ' . $champion['team'], 'green');
        }
    }
}
