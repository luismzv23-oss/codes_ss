<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WorldCupOddsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        $league = $db->table('leagues')->where('name', 'Copa Mundial de la FIFA 2026')->get()->getRow();
        if (!$league) {
            echo "League 'Copa Mundial de la FIFA 2026' not found.\n";
            return;
        }

        $events = $db->table('events')->where('league_id', $league->id)->get()->getResult();
        if (empty($events)) {
            echo "No events found for the World Cup.\n";
            return;
        }

        $marketsInserted = 0;
        $oddsInserted = 0;

        foreach ($events as $event) {
            // Check if market exists
            $marketExists = $db->table('markets')
                               ->where('event_id', $event->id)
                               ->where('type', '1x2')
                               ->countAllResults();
            
            if ($marketExists == 0) {
                // Insert 1x2 Market
                $db->table('markets')->insert([
                    'event_id' => $event->id,
                    'name' => 'Ganador del Partido',
                    'type' => '1x2',
                    'status' => 'open'
                ]);
                $marketId = $db->insertID();
                $marketsInserted++;

                // Generate realistic random odds
                // Usually odds are like 1.50, 3.80, 5.50
                $p1 = mt_rand(20, 70) / 100; // Probability Home (20% to 70%)
                $pX = mt_rand(20, 35) / 100; // Probability Draw
                $p2 = 1.0 - $p1 - $pX;
                if ($p2 < 0.05) $p2 = 0.05; // Ensure at least 5%

                // Calculate odds (with a theoretical ~5% bookmaker margin)
                $margin = 1.05; 
                $odd1 = number_format($margin / $p1, 2, '.', '');
                $oddX = number_format($margin / $pX, 2, '.', '');
                $odd2 = number_format($margin / $p2, 2, '.', '');

                $db->table('odds')->insertBatch([
                    [
                        'market_id' => $marketId,
                        'selection' => '1',
                        'odds_decimal' => $odd1,
                        'active' => 1
                    ],
                    [
                        'market_id' => $marketId,
                        'selection' => 'X',
                        'odds_decimal' => $oddX,
                        'active' => 1
                    ],
                    [
                        'market_id' => $marketId,
                        'selection' => '2',
                        'odds_decimal' => $odd2,
                        'active' => 1
                    ]
                ]);
                $oddsInserted += 3;
            }
        }

        echo "Generados {$marketsInserted} mercados y {$oddsInserted} cuotas para el Mundial.\n";
    }
}
