<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SettlementService;
use App\Services\ApiFootballService;

class SettleBets extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:settle';
    protected $description = 'Resuelve los partidos finalizados usando API-Football y paga los boletos ganadores a los usuarios.';

    public function run(array $params)
    {
        CLI::write('Iniciando Settlement Automático (API-Football -> Settlement Engine)...', 'yellow');

        $api = new ApiFootballService();
        $db = \Config\Database::connect();
        
        // Buscar eventos pendientes/live cuya fecha de inicio ya pasó
        $events = $db->table('events')
                     ->whereIn('status', ['pending', 'live'])
                     ->where('api_fixture_id IS NOT NULL')
                     ->where('start_time <=', date('Y-m-d H:i:s', strtotime('-105 minutes')))
                     ->get()->getResultArray();

        $finishedCount = 0;

        if (!empty($events)) {
            $eventsTable = $db->table('events');
            
            foreach ($events as $event) {
                CLI::write("Verificando resultado para Evento ID: {$event['id']} (API: {$event['api_fixture_id']})", 'cyan');
                
                $response = $api->getFixtureById($event['api_fixture_id']);
                
                if (!isset($response['response'][0])) {
                    CLI::write("  No se encontró info del fixture.", 'dark_gray');
                    continue;
                }

                $fixtureData = $response['response'][0];
                $statusShort = $fixtureData['fixture']['status']['short'];
                
                // Estados finales en API-Football
                if (in_array($statusShort, ['FT', 'AET', 'PEN'])) {
                    $goalsHome = $fixtureData['goals']['home'];
                    $goalsAway = $fixtureData['goals']['away'];
                    
                    if ($goalsHome !== null && $goalsAway !== null) {
                        $eventsTable->where('id', $event['id'])->update([
                            'score_home' => $goalsHome,
                            'score_away' => $goalsAway,
                            'status'     => 'finished',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $finishedCount++;
                        CLI::write("  -> Finalizado: $goalsHome - $goalsAway", 'green');
                    }
                } else {
                    CLI::write("  Aún no finaliza (Status: $statusShort).", 'dark_gray');
                }

                sleep(1); // Rate Limit
            }
        } else {
            CLI::write('No hay eventos próximos a finalizar según la hora de inicio.', 'cyan');
        }

        CLI::write('Buscando marcadores de eventos manuales en Google (SerpApi)...', 'yellow');
        $fetcher = new \App\Services\ScoreFetcherService();
        $eventsWithoutScore = $db->table('events e')
            ->select('e.id, e.home_team, e.away_team, l.api_sport_key')
            ->join('leagues l', 'l.id = e.league_id', 'left')
            ->groupStart()
                ->where('e.status', 'finished')
                ->orGroupStart()
                    ->where('e.status', 'pending')
                    ->where('e.start_time <=', date('Y-m-d H:i:s'))
                ->groupEnd()
            ->groupEnd()
            ->where('e.score_home IS NULL', null, false)
            ->where('e.start_time >=', date('Y-m-d H:i:s', strtotime('-7 days'))) // Limitar a ultimos 7 dias
            ->limit(15) // Limitar cantidad en el cron job
            ->orderBy('e.start_time', 'DESC')
            ->get()->getResultArray();
            
        foreach ($eventsWithoutScore as $ev) {
            $score = null;
            if (!empty($ev['api_sport_key'])) {
                $score = $fetcher->fetchScoreForEvent($ev, $ev['api_sport_key']);
            }
            if ($score) {
                [$home, $away] = explode('-', $score);
                $db->table('events')->where('id', $ev['id'])->update([
                    'score_home' => (int)$home,
                    'score_away' => (int)$away,
                    'status'     => 'finished',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $finishedCount++;
                CLI::write("  ✓ SerpApi: {$ev['home_team']} vs {$ev['away_team']}: {$home}-{$away}", 'green');
            } else {
                CLI::write("  ✗ SerpApi: {$ev['home_team']} vs {$ev['away_team']} - No disponible.", 'dark_gray');
            }
        }

        if ($finishedCount > 0) {
            CLI::write('Iniciando Motor de Liquidación Local (Settlement Engine)...', 'yellow');
            $service = new SettlementService();
            
            try {
                $service->settleEvents();
                CLI::write('✓ Boletos liquidados y pagos emitidos correctamente.', 'green');
            } catch (\Exception $e) {
                CLI::error('Error en el Settlement Engine: ' . $e->getMessage());
                CLI::error($e->getTraceAsString());
            }
        } else {
            // Aún así correr settlement por si hay eventos manuales finalizados
            CLI::write('Buscando eventos manuales por liquidar...', 'yellow');
            (new SettlementService())->settleEvents();
        }
    }
}
