<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupBracket extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:bracket';
    protected $description = 'Avanza automaticamente todas las rondas disponibles del Mundial 2026.';

    public function run(array $params)
    {
        $result = (new WorldCupBracketService())->advanceKnockoutRoundsIfReady();

        if (! $result['completed']) {
            CLI::write('Bracket no completado: ' . $result['reason'], 'yellow');
            return;
        }

        CLI::write('Bracket actualizado automaticamente.', 'green');

        foreach ($result['results'] ?? [] as $stageResult) {
            $stage = $stageResult['stage'];
            $data = $stageResult['result'];
            CLI::write($stage . ': ' . $data['reason']);

            foreach ($data['pairings'] ?? [] as $index => [$home, $away]) {
                CLI::write(sprintf(
                    '  %02d. %s vs %s',
                    $index + 1,
                    $home['team'],
                    $away['team']
                ));
            }
        }
    }
}
