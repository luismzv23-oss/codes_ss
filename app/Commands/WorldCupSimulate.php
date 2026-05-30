<?php

namespace App\Commands;

use App\Services\WorldCupBracketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WorldCupSimulate extends BaseCommand
{
    protected $group = 'Sportsbook';
    protected $name = 'worldcup:simulate';
    protected $description = '[TESTING] Completa el Mundial 2026 con marcadores deterministas y avanza todo el bracket.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $league = $db->table('leagues')->where('name', 'Copa Mundial de la FIFA 2026')->get()->getRowArray();

        if (! $league) {
            CLI::error('No existe la liga Copa Mundial de la FIFA 2026.');
            return;
        }

        $service = new WorldCupBracketService();

        $this->finishStage((int) $league['id'], 'Fase de grupos', false);
        $this->advance($service, 'Fase de grupos -> 16vos');

        foreach ([
            'Dieciseisavos de final' => '16vos -> 8vos',
            'Octavos de final' => '8vos -> Cuartos',
            'Cuartos de final' => 'Cuartos -> Semifinales',
            'Semifinales' => 'Semifinales -> Final y tercer puesto',
        ] as $stage => $label) {
            $this->finishStage((int) $league['id'], $stage, true);
            $this->advance($service, $label);
        }

        $this->finishStage((int) $league['id'], 'Tercer puesto', true);
        $this->finishStage((int) $league['id'], 'Final', true);

        CLI::write('Mundial 2026 completado para pruebas.', 'green');
    }

    private function finishStage(int $leagueId, string $stage, bool $avoidDraws): void
    {
        $db = \Config\Database::connect();
        $matches = $db->table('events')
            ->where('league_id', $leagueId)
            ->where('stage', $stage)
            ->orderBy('match_number', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($matches as $match) {
            [$home, $away] = $this->scoreForMatch((int) $match['match_number'], $avoidDraws);

            $db->table('events')->where('id', $match['id'])->update([
                'score_home' => $home,
                'score_away' => $away,
                'status' => 'finished',
                'settled' => 0,
            ]);
        }

        CLI::write($stage . ': ' . count($matches) . ' partidos finalizados.', 'cyan');
    }

    private function scoreForMatch(int $matchNumber, bool $avoidDraws): array
    {
        $home = ($matchNumber * 7) % 5;
        $away = ($matchNumber * 11) % 5;

        if ($avoidDraws && $home === $away) {
            $home = ($home + 1) % 5;
        }

        return [$home, $away];
    }

    private function advance(WorldCupBracketService $service, string $label): void
    {
        $result = $service->advanceKnockoutRoundsIfReady();
        $color = $result['completed'] ? 'green' : 'yellow';
        CLI::write($label . ': ' . $result['reason'], $color);
    }
}
