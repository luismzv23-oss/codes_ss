<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\ApiFootballService;

class FetchOdds extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:fetch_odds';
    protected $description = 'Actualiza las cuotas (Odds) de los próximos partidos desde API-Football.';

    public function run(array $params)
    {
        CLI::write("Iniciando sincronización de Cuotas (Odds) desde API-Football...", 'yellow');

        $api = new ApiFootballService();
        $db = \Config\Database::connect();
        
        // Obtener eventos pendientes que tengan api_fixture_id y sean en los próximos 7 días
        $events = $db->table('events')
                     ->where('status', 'pending')
                     ->where('api_fixture_id IS NOT NULL')
                     ->where('start_time >', date('Y-m-d H:i:s'))
                     ->where('start_time <', date('Y-m-d H:i:s', strtotime('+7 days')))
                     ->get()->getResultArray();

        if (empty($events)) {
            CLI::write("No hay eventos futuros próximos para actualizar cuotas.", 'cyan');
            return;
        }

        $marketsTable = $db->table('markets');
        $oddsTable = $db->table('odds');
        
        $totalEventsUpdated = 0;

        foreach ($events as $event) {
            CLI::write("Consultando cuotas para Evento ID: {$event['id']} (API: {$event['api_fixture_id']})", 'cyan');
            
            $response = $api->getOdds($event['api_fixture_id'], 8); // 8 = Bet365 por defecto
            
            if (!isset($response['response'][0]['bookmakers'][0]['bets'])) {
                CLI::write("  No hay cuotas disponibles aún.", 'dark_gray');
                continue;
            }

            $bets = $response['response'][0]['bookmakers'][0]['bets'];
            $marketsUpdated = 0;

            foreach ($bets as $bet) {
                // Filtrar solo mercados populares para no inundar la DB
                // 1 = Match Winner (1x2), 5 = Goals Over/Under, 10 = Exact Score, 13 = Both Teams To Score
                $allowedMarkets = [1, 5, 13]; 
                if (!in_array($bet['id'], $allowedMarkets)) {
                    continue;
                }

                $marketName = $bet['name'];
                
                // Determinar el "type" interno
                $marketType = 'other';
                if ($bet['id'] == 1) $marketType = '1x2';
                if ($bet['id'] == 5) $marketType = 'over_under';
                if ($bet['id'] == 13) $marketType = 'btts';

                // Verificar si el mercado ya existe
                $existingMarket = $marketsTable->where('event_id', $event['id'])
                                               ->where('name', $marketName)
                                               ->get()->getRowArray();
                
                if (!$existingMarket) {
                    $marketsTable->insert([
                        'event_id'   => $event['id'],
                        'name'       => $marketName,
                        'type'       => $marketType,
                        'status'     => 'open',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $marketId = $db->insertID();
                } else {
                    $marketId = $existingMarket['id'];
                }

                // Ahora procesar las selecciones/cuotas (odds)
                foreach ($bet['values'] as $val) {
                    $selectionName = $val['value'];
                    $oddDecimal = (float)$val['odd'];
                    
                    $existingOdd = $oddsTable->where('market_id', $marketId)
                                             ->where('selection', $selectionName)
                                             ->get()->getRowArray();
                    
                    if (!$existingOdd) {
                        $oddsTable->insert([
                            'market_id'    => $marketId,
                            'selection'    => $selectionName,
                            'odds_decimal' => $oddDecimal,
                            'active'       => 1,
                            'status'       => 'pending',
                            'created_at'   => date('Y-m-d H:i:s'),
                            'updated_at'   => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        // Si la cuota cambió, actualizar
                        if ($existingOdd['odds_decimal'] != $oddDecimal) {
                            $oddsTable->where('id', $existingOdd['id'])->update([
                                'odds_decimal' => $oddDecimal,
                                'updated_at'   => date('Y-m-d H:i:s')
                            ]);
                            
                            // BROADCAST: Enviar al WebSocket Server en Node.js
                            $wsPayload = json_encode([
                                'event_id'  => $event['id'],
                                'odd_id'    => $existingOdd['id'],
                                'old_value' => (float)$existingOdd['odds_decimal'],
                                'new_value' => $oddDecimal,
                                'status'    => 'open'
                            ]);
                            $chWs = curl_init('http://localhost:3000/broadcast');
                            curl_setopt($chWs, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($chWs, CURLOPT_POST, true);
                            curl_setopt($chWs, CURLOPT_POSTFIELDS, $wsPayload);
                            curl_setopt($chWs, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                            curl_setopt($chWs, CURLOPT_TIMEOUT, 1); // Timeout muy corto para no bloquear el cron job
                            curl_exec($chWs);
                            curl_close($chWs);
                        }
                    }
                }
                $marketsUpdated++;
            }
            
            CLI::write("  -> Se actualizaron {$marketsUpdated} mercados.", 'green');
            $totalEventsUpdated++;
            // Respetar Rate Limit
            sleep(1);
        }

        CLI::write("Sincronización finalizada. Eventos actualizados: $totalEventsUpdated", 'green');
    }
}
