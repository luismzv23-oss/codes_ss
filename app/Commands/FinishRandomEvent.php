<?php

namespace App\Commands;

use App\Models\EventModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FinishRandomEvent extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:finish-random';
    protected $description = '[TESTING] Marca un evento al azar como finalizado con marcador para probar el Settlement Engine.';

    public function run(array $params)
    {
        $eventModel = new EventModel();
        $pendingEvents = $eventModel->where('status', 'pending')->findAll();

        if (empty($pendingEvents)) {
            CLI::write('No hay eventos pendientes para finalizar.', 'yellow');
            return;
        }

        $event = $pendingEvents[array_rand($pendingEvents)];
        $scoreHome = random_int(0, 4);
        $scoreAway = random_int(0, 4);

        $eventModel->update($event['id'], [
            'status' => 'finished',
            'settled' => 0,
            'score_home' => $scoreHome,
            'score_away' => $scoreAway,
        ]);

        CLI::write("Evento '{$event['home_team']} vs {$event['away_team']}' finalizado {$scoreHome}-{$scoreAway}.", 'green');
        CLI::write("Ahora puedes correr 'php spark sportsbook:settle' para liquidar con marcador real.", 'cyan');
    }
}
