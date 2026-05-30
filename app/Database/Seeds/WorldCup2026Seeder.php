<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WorldCup2026Seeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $football = $db->table('sports')->where('slug', 'futbol')->get()->getRowArray();
        if (! $football) {
            $sportId = $db->table('sports')->insert([
                'name' => 'Futbol',
                'slug' => 'futbol',
                'icon' => '⚽',
                'active' => 1,
            ]);
        } else {
            $sportId = $football['id'];
        }

        $db->table('leagues')
            ->where('name', 'Mundial de la copa del mundo 2026')
            ->update(['name' => 'Copa Mundial de la FIFA 2026']);

        $league = $db->table('leagues')->where('name', 'Copa Mundial de la FIFA 2026')->get()->getRowArray();
        if (! $league) {
            $leagueId = $db->table('leagues')->insert([
                'sport_id' => $sportId,
                'name' => 'Copa Mundial de la FIFA 2026',
                'country' => 'Canada, Mexico y Estados Unidos',
                'active' => 1,
            ]);
        } else {
            $leagueId = $league['id'];
            $db->table('leagues')->where('id', $leagueId)->update([
                'sport_id' => $sportId,
                'country' => 'Canada, Mexico y Estados Unidos',
                'active' => 1,
            ]);
        }

        $db->table('events')->where('league_id', $leagueId)->delete();

        $groups = [
            'A' => [
                ['Mexico', 'mx'],
                ['Sudafrica', 'za'],
                ['Corea del Sur', 'kr'],
                ['Chequia', 'cz'],
            ],
            'B' => [
                ['Canada', 'ca'],
                ['Bosnia y Herzegovina', 'ba'],
                ['Qatar', 'qa'],
                ['Suiza', 'ch'],
            ],
            'C' => [
                ['Brasil', 'br'],
                ['Marruecos', 'ma'],
                ['Haiti', 'ht'],
                ['Escocia', 'gb-sct'],
            ],
            'D' => [
                ['Estados Unidos', 'us'],
                ['Paraguay', 'py'],
                ['Australia', 'au'],
                ['Turquia', 'tr'],
            ],
            'E' => [
                ['Alemania', 'de'],
                ['Curazao', 'cw'],
                ['Costa de Marfil', 'ci'],
                ['Ecuador', 'ec'],
            ],
            'F' => [
                ['Paises Bajos', 'nl'],
                ['Japon', 'jp'],
                ['Suecia', 'se'],
                ['Tunez', 'tn'],
            ],
            'G' => [
                ['Belgica', 'be'],
                ['Egipto', 'eg'],
                ['Iran', 'ir'],
                ['Nueva Zelanda', 'nz'],
            ],
            'H' => [
                ['Espana', 'es'],
                ['Cabo Verde', 'cv'],
                ['Arabia Saudita', 'sa'],
                ['Uruguay', 'uy'],
            ],
            'I' => [
                ['Francia', 'fr'],
                ['Senegal', 'sn'],
                ['Irak', 'iq'],
                ['Noruega', 'no'],
            ],
            'J' => [
                ['Argentina', 'ar'],
                ['Argelia', 'dz'],
                ['Austria', 'at'],
                ['Jordania', 'jo'],
            ],
            'K' => [
                ['Portugal', 'pt'],
                ['RD Congo', 'cd'],
                ['Uzbekistan', 'uz'],
                ['Colombia', 'co'],
            ],
            'L' => [
                ['Inglaterra', 'gb-eng'],
                ['Croacia', 'hr'],
                ['Ghana', 'gh'],
                ['Panama', 'pa'],
            ],
        ];

        $venues = [
            'Mexico City Stadium',
            'Guadalajara Stadium',
            'Monterrey Stadium',
            'Toronto Stadium',
            'BC Place Vancouver',
            'Los Angeles Stadium',
            'Seattle Stadium',
            'New York New Jersey Stadium',
            'Atlanta Stadium',
            'Dallas Stadium',
            'Houston Stadium',
            'Miami Stadium',
            'Philadelphia Stadium',
            'San Francisco Bay Area Stadium',
            'Boston Stadium',
            'Kansas City Stadium',
        ];

        $roundRobin = [
            [0, 1],
            [2, 3],
            [0, 2],
            [3, 1],
            [3, 0],
            [1, 2],
        ];

        $events = [];
        $matchNumber = 1;
        $groupIndex = 0;

        foreach ($groups as $group => $teams) {
            $firstRoundDay = 11 + intdiv($groupIndex, 2);
            $dates = [
                sprintf('2026-06-%02d 15:00:00', $firstRoundDay),
                sprintf('2026-06-%02d 18:00:00', $firstRoundDay),
                sprintf('2026-06-%02d 15:00:00', min($firstRoundDay + 6, 23)),
                sprintf('2026-06-%02d 18:00:00', min($firstRoundDay + 6, 23)),
                sprintf('2026-06-%02d 15:00:00', min($firstRoundDay + 12, 27)),
                sprintf('2026-06-%02d 18:00:00', min($firstRoundDay + 12, 27)),
            ];

            foreach ($roundRobin as $index => [$homeIndex, $awayIndex]) {
                $home = $teams[$homeIndex];
                $away = $teams[$awayIndex];

                $events[] = [
                    'league_id' => $leagueId,
                    'stage' => 'Fase de grupos',
                    'group_name' => 'Grupo ' . $group,
                    'match_number' => $matchNumber,
                    'home_team' => $home[0],
                    'home_flag' => $home[1],
                    'away_team' => $away[0],
                    'away_flag' => $away[1],
                    'start_time' => $dates[$index],
                    'venue' => $venues[($matchNumber - 1) % count($venues)],
                    'status' => 'pending',
                ];
                $matchNumber++;
            }

            $groupIndex++;
        }

        $knockoutRounds = [
            ['Dieciseisavos de final', 16, '2026-06-28 15:00:00', 'Clasificado', 'Clasificado'],
            ['Octavos de final', 8, '2026-07-04 15:00:00', 'Ganador 16avos', 'Ganador 16avos'],
            ['Cuartos de final', 4, '2026-07-09 15:00:00', 'Ganador octavos', 'Ganador octavos'],
            ['Semifinales', 2, '2026-07-14 20:00:00', 'Ganador cuartos', 'Ganador cuartos'],
        ];

        foreach ($knockoutRounds as [$stage, $count, $startDate, $homeName, $awayName]) {
            $base = strtotime($startDate);
            for ($i = 0; $i < $count; $i++) {
                $events[] = [
                    'league_id' => $leagueId,
                    'stage' => $stage,
                    'group_name' => null,
                    'match_number' => $matchNumber,
                    'home_team' => $homeName,
                    'home_flag' => null,
                    'away_team' => $awayName,
                    'away_flag' => null,
                    'start_time' => date('Y-m-d H:i:s', $base + (($i % max(1, intdiv($count, 2))) * 86400)),
                    'venue' => $venues[($matchNumber - 1) % count($venues)],
                    'status' => 'pending',
                ];
                $matchNumber++;
            }
        }

        $events[] = [
            'league_id' => $leagueId,
            'stage' => 'Tercer puesto',
            'group_name' => null,
            'match_number' => $matchNumber++,
            'home_team' => 'Perdedor semifinal',
            'away_team' => 'Perdedor semifinal',
            'start_time' => '2026-07-18 16:00:00',
            'venue' => 'Miami Stadium',
            'status' => 'pending',
        ];

        $events[] = [
            'league_id' => $leagueId,
            'stage' => 'Final',
            'group_name' => null,
            'match_number' => $matchNumber,
            'home_team' => 'Ganador semifinal',
            'away_team' => 'Ganador semifinal',
            'start_time' => '2026-07-19 16:00:00',
            'venue' => 'New York New Jersey Stadium',
            'status' => 'pending',
        ];

        $db->transStart();
        foreach ($events as $event) {
            $db->table('events')->insert($event);
            $eventId = $db->insertID();

            $db->table('markets')->insert([
                'event_id' => $eventId,
                'name' => 'Ganador del Partido',
                'type' => '1x2',
                'status' => 'open',
            ]);
            $marketId = $db->insertID();

            foreach ($this->oddsForMatch((int) $event['match_number'], $event['home_team'], $event['away_team']) as $odd) {
                $db->table('odds')->insert([
                    'market_id' => $marketId,
                    'selection' => $odd['selection'],
                    'odds_decimal' => $odd['odds_decimal'],
                    'active' => 1,
                ]);
            }
        }
        $db->transComplete();

        echo count($events) . " partidos insertados para la Copa Mundial de la FIFA 2026.\n";
    }

    private function oddsForMatch(int $matchNumber, string $homeTeam, string $awayTeam): array
    {
        $homeOdds = round(1.65 + (($matchNumber * 17) % 150) / 100, 2);
        $drawOdds = round(2.85 + (($matchNumber * 11) % 115) / 100, 2);
        $awayOdds = round(1.75 + (($matchNumber * 23) % 160) / 100, 2);

        return [
            ['selection' => $homeTeam, 'odds_decimal' => $homeOdds],
            ['selection' => 'Empate', 'odds_decimal' => $drawOdds],
            ['selection' => $awayTeam, 'odds_decimal' => $awayOdds],
        ];
    }
}
