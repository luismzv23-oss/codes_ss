<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CopaLibertadores2026Seeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $sport = $db->table('sports')->whereIn('slug', ['futbol', 'football'])->get()->getRowArray();
        if (! $sport) {
            $db->table('sports')->insert([
                'name' => 'Futbol',
                'slug' => 'futbol',
                'icon' => '⚽',
                'active' => 1,
            ]);
            $sportId = (int) $db->insertID();
        } else {
            $sportId = (int) $sport['id'];
        }

        $league = $db->table('leagues')->where('name', 'Copa Libertadores')->get()->getRowArray();
        if (! $league) {
            $db->table('leagues')->insert([
                'sport_id' => $sportId,
                'name' => 'Copa Libertadores',
                'country' => 'Sudamerica',
                'active' => 1,
            ]);
            $leagueId = (int) $db->insertID();
        } else {
            $leagueId = (int) $league['id'];
            $db->table('leagues')->where('id', $leagueId)->update([
                'sport_id' => $sportId,
                'country' => 'Sudamerica',
                'active' => 1,
            ]);
        }

        $this->clearLeagueEvents($leagueId);

        $events = array_merge(
            $this->groupStage($leagueId),
            $this->knockoutPlaceholders($leagueId)
        );

        $db->transStart();
        foreach ($events as $event) {
            $db->table('events')->insert($event);
            $eventId = (int) $db->insertID();
            $this->createMarkets($eventId, $event);
        }
        $db->transComplete();

        echo count($events) . " partidos insertados para Copa Libertadores.\n";
    }

    private function groupStage(int $leagueId): array
    {
        $groups = [
            'A' => [
                ['Flamengo', 'br'],
                ['Estudiantes de La Plata', 'ar'],
                ['Cusco', 'pe'],
                ['Independiente Medellin', 'co'],
            ],
            'B' => [
                ['Nacional', 'uy'],
                ['Universitario', 'pe'],
                ['Coquimbo Unido', 'cl'],
                ['Deportes Tolima', 'co'],
            ],
            'C' => [
                ['Fluminense', 'br'],
                ['Bolivar', 'bo'],
                ['Deportivo La Guaira', 've'],
                ['Independiente Rivadavia', 'ar'],
            ],
            'D' => [
                ['Boca Juniors', 'ar'],
                ['Cruzeiro', 'br'],
                ['Universidad Catolica', 'cl'],
                ['Barcelona SC', 'ec'],
            ],
            'E' => [
                ['Penarol', 'uy'],
                ['Corinthians', 'br'],
                ['Santa Fe', 'co'],
                ['Platense', 'ar'],
            ],
            'F' => [
                ['Palmeiras', 'br'],
                ['Cerro Porteno', 'py'],
                ['Junior', 'co'],
                ['Sporting Cristal', 'pe'],
            ],
            'G' => [
                ['Liga de Quito', 'ec'],
                ['Lanus', 'ar'],
                ['Always Ready', 'bo'],
                ['Mirassol', 'br'],
            ],
            'H' => [
                ['Independiente del Valle', 'ec'],
                ['Libertad', 'py'],
                ['Rosario Central', 'ar'],
                ['Universidad Central', 've'],
            ],
        ];

        $roundDates = [
            1 => '2026-04-07 19:00:00',
            2 => '2026-04-14 19:00:00',
            3 => '2026-04-28 19:00:00',
            4 => '2026-05-05 19:00:00',
            5 => '2026-05-21 19:00:00',
            6 => '2026-05-26 19:00:00',
        ];

        $roundRobin = [
            1 => [[2, 3], [1, 4]],
            2 => [[4, 2], [3, 1]],
            3 => [[1, 2], [3, 4]],
            4 => [[2, 1], [4, 3]],
            5 => [[3, 2], [4, 1]],
            6 => [[3, 1], [2, 4]],
        ];

        $events = [];
        $matchNumber = 1;

        foreach ($roundRobin as $round => $matches) {
            $groupIndex = 0;
            foreach ($groups as $groupName => $teams) {
                foreach ($matches as $matchIndex => [$homePos, $awayPos]) {
                    $home = $teams[$homePos - 1];
                    $away = $teams[$awayPos - 1];
                    $status = $round <= 4 ? 'finished' : 'pending';
                    [$scoreHome, $scoreAway] = $status === 'finished'
                        ? $this->deterministicScore($matchNumber, $home[0], $away[0])
                        : [null, null];

                    $events[] = [
                        'league_id' => $leagueId,
                        'stage' => 'CONMEBOL Libertadores 2026 - Fase de grupos',
                        'group_name' => 'Fecha ' . $round . ' - Grupo ' . $groupName,
                        'match_number' => $matchNumber,
                        'home_team' => $home[0],
                        'home_flag' => $home[1],
                        'away_team' => $away[0],
                        'away_flag' => $away[1],
                        'start_time' => date('Y-m-d H:i:s', strtotime($roundDates[$round] . ' +' . ($groupIndex + $matchIndex) . ' hours')),
                        'venue' => $this->venueFor($home[0]),
                        'status' => $status,
                        'settled' => $status === 'finished' ? 1 : 0,
                        'score_home' => $scoreHome,
                        'score_away' => $scoreAway,
                    ];
                    $matchNumber++;
                }
                $groupIndex++;
            }
        }

        return $events;
    }

    private function knockoutPlaceholders(int $leagueId): array
    {
        $rounds = [
            ['Octavos de final', 8, '2026-08-11 19:00:00', 'Clasificado', 'Clasificado'],
            ['Cuartos de final', 4, '2026-09-08 19:00:00', 'Ganador octavos', 'Ganador octavos'],
            ['Semifinales', 2, '2026-10-13 19:00:00', 'Ganador cuartos', 'Ganador cuartos'],
        ];

        $events = [];
        $matchNumber = 200;

        foreach ($rounds as [$stage, $count, $startDate, $home, $away]) {
            $base = strtotime($startDate);
            for ($i = 0; $i < $count; $i++) {
                $events[] = [
                    'league_id' => $leagueId,
                    'stage' => 'CONMEBOL Libertadores 2026 - ' . $stage,
                    'group_name' => null,
                    'match_number' => $matchNumber++,
                    'home_team' => $home,
                    'home_flag' => null,
                    'away_team' => $away,
                    'away_flag' => null,
                    'start_time' => date('Y-m-d H:i:s', $base + (($i % 4) * 86400)),
                    'venue' => 'Sede por confirmar',
                    'status' => 'pending',
                    'settled' => 0,
                    'score_home' => null,
                    'score_away' => null,
                ];
            }
        }

        $events[] = [
            'league_id' => $leagueId,
            'stage' => 'CONMEBOL Libertadores 2026 - Final',
            'group_name' => null,
            'match_number' => $matchNumber,
            'home_team' => 'Finalista 1',
            'home_flag' => null,
            'away_team' => 'Finalista 2',
            'away_flag' => null,
            'start_time' => '2026-11-28 17:00:00',
            'venue' => 'Montevideo, Uruguay',
            'status' => 'pending',
            'settled' => 0,
            'score_home' => null,
            'score_away' => null,
        ];

        return $events;
    }

    private function clearLeagueEvents(int $leagueId): void
    {
        $db = \Config\Database::connect();
        $events = $db->table('events')->where('league_id', $leagueId)->get()->getResultArray();

        foreach ($events as $event) {
            $referencedSelections = $db->table('bet_selections bs')
                ->join('odds o', 'o.id = bs.odd_id')
                ->join('markets m', 'm.id = o.market_id')
                ->where('m.event_id', $event['id'])
                ->countAllResults();

            if ($referencedSelections > 0) {
                $archiveLeagueId = $this->archiveLeagueId($leagueId);
                $db->table('events')->where('id', $event['id'])->update([
                    'league_id' => $archiveLeagueId,
                    'status' => 'cancelled',
                ]);
                $this->closeEventMarkets((int) $event['id']);
                continue;
            }

            $marketIds = array_column(
                $db->table('markets')->select('id')->where('event_id', $event['id'])->get()->getResultArray(),
                'id'
            );

            if ($marketIds !== []) {
                $db->table('odds')->whereIn('market_id', $marketIds)->delete();
                $db->table('markets')->whereIn('id', $marketIds)->delete();
            }

            $db->table('events')->where('id', $event['id'])->delete();
        }
    }

    private function archiveLeagueId(int $sourceLeagueId): int
    {
        $db = \Config\Database::connect();
        $source = $db->table('leagues')->where('id', $sourceLeagueId)->get()->getRowArray();
        $name = 'Copa Libertadores (Archivado)';
        $archive = $db->table('leagues')->where('name', $name)->get()->getRowArray();

        if ($archive) {
            return (int) $archive['id'];
        }

        $db->table('leagues')->insert([
            'sport_id' => $source['sport_id'] ?? 1,
            'name' => $name,
            'country' => 'Sudamerica',
            'active' => 0,
        ]);

        return (int) $db->insertID();
    }

    private function closeEventMarkets(int $eventId): void
    {
        $db = \Config\Database::connect();
        $marketIds = array_column(
            $db->table('markets')->select('id')->where('event_id', $eventId)->get()->getResultArray(),
            'id'
        );

        if ($marketIds === []) {
            return;
        }

        $db->table('odds')->whereIn('market_id', $marketIds)->update(['active' => 0, 'status' => 'void']);
        $db->table('markets')->whereIn('id', $marketIds)->update(['status' => 'closed']);
    }

    private function createMarkets(int $eventId, array $event): void
    {
        $this->createWinnerMarket($eventId, $event);

        if ($event['status'] === 'pending' && str_contains($event['stage'], 'Final')) {
            $this->createChampionMarket($eventId, $event);
        }
    }

    private function createWinnerMarket(int $eventId, array $event): void
    {
        $db = \Config\Database::connect();

        $db->table('markets')->insert([
            'event_id' => $eventId,
            'name' => 'Ganador del Partido',
            'type' => '1x2',
            'status' => $event['status'] === 'pending' ? 'open' : 'closed',
        ]);
        $marketId = (int) $db->insertID();

        $homeOdds = round(1.62 + (($event['match_number'] * 13) % 145) / 100, 2);
        $drawOdds = round(2.85 + (($event['match_number'] * 5) % 130) / 100, 2);
        $awayOdds = round(1.70 + (($event['match_number'] * 17) % 155) / 100, 2);

        $winner = null;
        if ($event['score_home'] !== null && $event['score_away'] !== null) {
            if ((int) $event['score_home'] > (int) $event['score_away']) {
                $winner = $event['home_team'];
            } elseif ((int) $event['score_home'] < (int) $event['score_away']) {
                $winner = $event['away_team'];
            } else {
                $winner = 'Empate';
            }
        }

        foreach ([[$event['home_team'], $homeOdds], ['Empate', $drawOdds], [$event['away_team'], $awayOdds]] as [$selection, $odds]) {
            $db->table('odds')->insert([
                'market_id' => $marketId,
                'selection' => $selection,
                'odds_decimal' => $odds,
                'active' => $event['status'] === 'pending' ? 1 : 0,
                'status' => $winner === null ? 'pending' : ($winner === $selection ? 'won' : 'lost'),
            ]);
        }
    }

    private function createChampionMarket(int $eventId, array $event): void
    {
        $db = \Config\Database::connect();

        $db->table('markets')->insert([
            'event_id' => $eventId,
            'name' => 'Campeon de la CONMEBOL Libertadores',
            'type' => 'outright',
            'status' => 'open',
        ]);
        $marketId = (int) $db->insertID();

        foreach ([[$event['home_team'], 1.90], [$event['away_team'], 1.90]] as [$selection, $odds]) {
            $db->table('odds')->insert([
                'market_id' => $marketId,
                'selection' => $selection,
                'odds_decimal' => $odds,
                'active' => 1,
                'status' => 'pending',
            ]);
        }
    }

    private function deterministicScore(int $matchNumber, string $home, string $away): array
    {
        $homeScore = ($matchNumber + strlen($home)) % 4;
        $awayScore = ($matchNumber + strlen($away) + 2) % 3;

        return [$homeScore, $awayScore];
    }

    private function venueFor(string $club): string
    {
        $map = [
            'Always Ready' => 'Estadio Municipal de Villa Ingenio',
            'Barcelona SC' => 'Estadio Monumental Banco Pichincha',
            'Boca Juniors' => 'La Bombonera',
            'Bolivar' => 'Estadio Hernando Siles',
            'Cerro Porteno' => 'Estadio Ueno La Nueva Olla',
            'Coquimbo Unido' => 'Estadio Francisco Sanchez Rumoroso',
            'Corinthians' => 'Neo Quimica Arena',
            'Cruzeiro' => 'Estadio Mineirao',
            'Cusco' => 'Estadio Inca Garcilaso de la Vega',
            'Deportes Tolima' => 'Estadio Manuel Murillo Toro',
            'Deportivo La Guaira' => 'Estadio Olimpico de la U.C.V.',
            'Estudiantes de La Plata' => 'Estadio UNO Jorge Luis Hirschi',
            'Flamengo' => 'Maracana',
            'Fluminense' => 'Maracana',
            'Independiente del Valle' => 'Estadio Banco Guayaquil',
            'Independiente Medellin' => 'Estadio Atanasio Girardot',
            'Independiente Rivadavia' => 'Estadio Malvinas Argentinas',
            'Junior' => 'Estadio Olimpico Jaime Moron Leon',
            'Lanus' => 'Estadio Ciudad de Lanus',
            'Libertad' => 'Estadio La Huerta',
            'Liga de Quito' => 'Estadio Rodrigo Paz Delgado',
            'Mirassol' => 'Estadio Jose Maria de Campos Maia',
            'Nacional' => 'Gran Parque Central',
            'Palmeiras' => 'Allianz Parque',
            'Penarol' => 'Estadio Campeon del Siglo',
            'Platense' => 'Estadio Ciudad de Vicente Lopez',
            'Rosario Central' => 'Estadio Gigante de Arroyito',
            'Santa Fe' => 'Estadio Nemesio Camacho El Campin',
            'Sporting Cristal' => 'Estadio Alberto Gallardo',
            'Universidad Catolica' => 'Claro Arena',
            'Universidad Central' => 'Estadio Olimpico de la U.C.V.',
            'Universitario' => 'Estadio Monumental U',
        ];

        return $map[$club] ?? 'Estadio local';
    }
}
