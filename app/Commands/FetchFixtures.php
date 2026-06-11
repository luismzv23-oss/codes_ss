<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\ApiFootballService;
use CodeIgniter\Database\Exceptions\DatabaseException;

class FetchFixtures extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:fetch_fixtures';
    protected $description = 'Importa los fixtures venideros desde API-Football a la base de datos local.';

    public function run(array $params)
    {
        CLI::write("Iniciando importación de Fixtures desde API-Football...", 'yellow');

        $api = new ApiFootballService();
        $db = \Config\Database::connect();
        
        // Obtenemos las ligas activas que tengan api_sport_key configurado
        $leagues = $db->table('leagues')
                      ->where('active', 1)
                      ->where('api_sport_key IS NOT NULL')
                      ->get()->getResultArray();

        if (empty($leagues)) {
            CLI::write("No hay ligas activas con un 'api_sport_key' válido configurado.", 'red');
            return;
        }

        $eventsTable = $db->table('events');
        $totalImported = 0;

        foreach ($leagues as $league) {
            CLI::write("Consultando partidos para la liga: " . $league['name'], 'cyan');
            
            // Traer futuros eventos para esa liga (API-Football)
            $response = $api->getUpcomingFixtures($league['api_sport_key']);
            
            if (!isset($response['response']) || empty($response['response'])) {
                CLI::write("  No se encontraron fixtures futuros para esta liga o se excedió límite de API.", 'dark_gray');
                continue;
            }

            foreach ($response['response'] as $fixtureData) {
                $f = $fixtureData['fixture'];
                $teams = $fixtureData['teams'];
                
                // Ignorar partidos cancelados por defecto en primera pasada
                if (in_array($f['status']['short'], ['CANC', 'PST'])) {
                    continue;
                }

                $apiFixtureId = (string)$f['id'];
                
                // Verificar si ya existe en la DB
                $existing = $eventsTable->where('api_fixture_id', $apiFixtureId)->get()->getRowArray();
                
                $data = [
                    'league_id'      => $league['id'],
                    'api_fixture_id' => $apiFixtureId,
                    'home_team'      => $teams['home']['name'],
                    'away_team'      => $teams['away']['name'],
                    // start_time en UTC convertido a zona local o guardado en UTC según config
                    // La API devuelve timezone local o UTC dependiendo del parámetro.
                    'start_time'     => (new \DateTime())->setTimestamp((int)$f['timestamp'])->setTimezone(new \DateTimeZone('America/Argentina/Buenos_Aires'))->format('Y-m-d H:i:s'),
                    'status'         => 'pending',
                    'updated_at'     => date('Y-m-d H:i:s')
                ];

                if (!$existing) {
                    $data['created_at'] = date('Y-m-d H:i:s');
                    try {
                        $eventsTable->insert($data);
                        $totalImported++;
                    } catch (\Exception $e) {
                        CLI::write("Error al insertar fixture $apiFixtureId: " . $e->getMessage(), 'red');
                    }
                } else {
                    // Actualizar fecha por si hubo reprogramación
                    $eventsTable->where('id', $existing['id'])->update($data);
                }
            }
            
            CLI::write("  -> Procesados para " . $league['name'], 'green');
            // Sleep corto para no saturar el límite de rate limit de API-Football gratuito/básico
            sleep(1);
        }

        CLI::write("Importación finalizada. Nuevos eventos agregados: $totalImported", 'green');
    }
}
