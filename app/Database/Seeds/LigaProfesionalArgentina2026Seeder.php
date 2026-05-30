<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LigaProfesionalArgentina2026Seeder extends Seeder
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

        $league = $db->table('leagues')->where('name', 'Liga Profesional Argentina')->get()->getRowArray();
        if (! $league) {
            $db->table('leagues')->insert([
                'sport_id' => $sportId,
                'name' => 'Liga Profesional Argentina',
                'country' => 'Argentina',
                'active' => 1,
            ]);
            $leagueId = (int) $db->insertID();
        } else {
            $leagueId = (int) $league['id'];
            $db->table('leagues')->where('id', $leagueId)->update([
                'sport_id' => $sportId,
                'country' => 'Argentina',
                'active' => 1,
            ]);
        }

        $this->clearLeagueEvents($leagueId);

        $events = array_merge(
            $this->regularSeason($leagueId),
            $this->playoffs($leagueId),
            $this->final($leagueId)
        );

        $db->transStart();
        foreach ($events as $event) {
            $db->table('events')->insert($event);
            $eventId = (int) $db->insertID();
            $this->createMarkets($eventId, $event);
        }
        $db->transComplete();

        echo count($events) . " partidos insertados para Liga Profesional Argentina.\n";
    }

    private function regularSeason(int $leagueId): array
    {
        $fixture = <<<'TXT'
Fecha 1|2026-01-25 17:00:00
Interzonal: Aldosivi – Defensa y Justicia
Zona A: Boca – Deportivo Riestra
Zona A: Independiente – Estudiantes
Zona A: Talleres – Newell's
Zona A: Instituto – Velez
Zona A: Union – Platense
Zona A: San Lorenzo – Lanus
Zona A: Central Cordoba – Gimnasia (Mza.)
Zona B: Barracas Central – River
Zona B: Gimnasia – Racing
Zona B: Rosario Central – Belgrano
Zona B: Tigre – Estudiantes (Rio Cuarto)
Zona B: Argentinos – Sarmiento
Zona B: Banfield – Huracan
Zona B: Independiente Rivadavia Mza. – Atletico Tucuman
Fecha 2|2026-01-28 19:00:00
Interzonal: Atletico Tucuman – Central Cordoba
Zona A: Gimnasia (Mza.) – San Lorenzo
Zona A: Lanus – Union
Zona A: Platense – Instituto
Zona A: Velez – Talleres
Zona A: Newell's – Independiente
Zona A: Estudiantes – Boca
Zona A: Deportivo Riestra – Defensa y Justicia
Zona B: Huracan – Independiente Rivadavia Mza.
Zona B: Sarmiento – Banfield
Zona B: Estudiantes (Rio Cuarto) – Argentinos
Zona B: Belgrano – Tigre
Zona B: Racing – Rosario Central
Zona B: River – Gimnasia
Zona B: Aldosivi – Barracas Central
Fecha 3|2026-02-01 17:00:00
Interzonal: Barracas Central – Deportivo Riestra
Zona A: Defensa y Justicia – Estudiantes
Zona A: Boca – Newell's
Zona A: Independiente – Velez
Zona A: Talleres – Platense
Zona A: Instituto – Lanus
Zona A: Union – Gimnasia (Mza.)
Zona A: San Lorenzo – Central Cordoba
Zona B: Gimnasia – Aldosivi
Zona B: Rosario Central – River
Zona B: Tigre – Racing
Zona B: Argentinos – Belgrano
Zona B: Banfield – Estudiantes (Rio Cuarto)
Zona B: Independiente Rivadavia Mza. – Sarmiento
Zona B: Atletico Tucuman – Huracan
Fecha 4|2026-02-08 17:00:00
Interzonal: Huracan – San Lorenzo
Zona A: Central Cordoba – Union
Zona A: Gimnasia (Mza.) – Instituto
Zona A: Lanus – Talleres
Zona A: Platense – Independiente
Zona A: Velez – Boca
Zona A: Newell's – Defensa y Justicia
Zona A: Estudiantes – Deportivo Riestra
Zona B: Sarmiento – Atletico Tucuman
Zona B: Estudiantes (Rio Cuarto) – Independiente Rivadavia Mza.
Zona B: Belgrano – Banfield
Zona B: Racing – Argentinos
Zona B: River – Tigre
Zona B: Aldosivi – Rosario Central
Zona B: Barracas Central – Gimnasia
Fecha 5|2026-02-15 17:00:00
Interzonal: Gimnasia – Estudiantes
Zona A: Deportivo Riestra – Newell's
Zona A: Defensa y Justicia – Velez
Zona A: Boca – Platense
Zona A: Independiente – Lanus
Zona A: Talleres – Gimnasia (Mza.)
Zona A: Instituto – Central Cordoba
Zona A: Union – San Lorenzo
Zona B: Rosario Central – Barracas Central
Zona B: Tigre – Aldosivi
Zona B: Argentinos – River
Zona B: Banfield – Racing
Zona B: Independiente Rivadavia Mza. – Belgrano
Zona B: Atletico Tucuman – Estudiantes (Rio Cuarto)
Zona B: Huracan – Sarmiento
Fecha 6|2026-02-22 17:00:00
Interzonal: Velez – River
Interzonal: Platense – Barracas Central
Interzonal: Rosario Central – Talleres
Interzonal: Estudiantes – Sarmiento
Interzonal: Defensa y Justicia – Belgrano
Interzonal: Argentinos – Lanus
Interzonal: Boca – Racing
Interzonal: Independiente Rivadavia Mza. – Independiente
Interzonal: Union – Aldosivi
Interzonal: Instituto – Atletico Tucuman
Interzonal: San Lorenzo – Estudiantes (Rio Cuarto)
Interzonal: Gimnasia (Mza.) – Gimnasia
Interzonal: Central Cordoba – Tigre
Interzonal: Deportivo Riestra – Huracan
Interzonal: Banfield – Newell's
Fecha 7|2026-02-25 19:00:00
Interzonal: Sarmiento – Union
Zona A: San Lorenzo – Instituto
Zona A: Central Cordoba – Talleres
Zona A: Gimnasia (Mza.) – Independiente
Zona A: Lanus – Boca
Zona A: Platense – Defensa y Justicia
Zona A: Velez – Deportivo Riestra
Zona A: Newell's – Estudiantes
Zona B: Estudiantes (Rio Cuarto) – Huracan
Zona B: Belgrano – Atletico Tucuman
Zona B: Racing – Independiente Rivadavia Mza.
Zona B: River – Banfield
Zona B: Aldosivi – Argentinos
Zona B: Barracas Central – Tigre
Zona B: Gimnasia – Rosario Central
Fecha 8|2026-03-01 17:00:00
Interzonal: Newell's – Rosario Central
Zona A: Estudiantes – Velez
Zona A: Deportivo Riestra – Platense
Zona A: Defensa y Justicia – Lanus
Zona A: Boca – Gimnasia (Mza.)
Zona A: Independiente – Central Cordoba
Zona A: Talleres – San Lorenzo
Zona A: Instituto – Union
Zona B: Tigre – Gimnasia
Zona B: Argentinos – Barracas Central
Zona B: Banfield – Aldosivi
Zona B: Independiente Rivadavia Mza. – River
Zona B: Atletico Tucuman – Racing
Zona B: Huracan – Belgrano
Zona B: Sarmiento – Estudiantes (Rio Cuarto)
Fecha 9|2026-03-08 17:00:00
Interzonal: Estudiantes (Rio Cuarto) – Instituto
Zona A: Union – Talleres
Zona A: San Lorenzo – Independiente
Zona A: Central Cordoba – Boca
Zona A: Gimnasia (Mza.) – Defensa y Justicia
Zona A: Lanus – Deportivo Riestra
Zona A: Platense – Estudiantes
Zona A: Velez – Newell's
Zona B: Belgrano – Sarmiento
Zona B: Racing – Huracan
Zona B: River – Atletico Tucuman
Zona B: Aldosivi – Independiente Rivadavia Mza.
Zona B: Barracas Central – Banfield
Zona B: Gimnasia – Argentinos
Zona B: Rosario Central – Tigre
Fecha 10|2026-03-11 19:00:00
Interzonal: Tigre – Velez
Zona A: Newell's – Platense
Zona A: Estudiantes – Lanus
Zona A: Deportivo Riestra – Gimnasia (Mza.)
Zona A: Defensa y Justicia – Central Cordoba
Zona A: Boca – San Lorenzo
Zona A: Independiente – Union
Zona A: Talleres – Instituto
Zona B: Argentinos – Rosario Central
Zona B: Banfield – Gimnasia
Zona B: Independiente Rivadavia Mza. – Barracas Central
Zona B: Atletico Tucuman – Aldosivi
Zona B: Huracan – River
Zona B: Sarmiento – Racing
Zona B: Estudiantes (Rio Cuarto) – Belgrano
Fecha 11|2026-03-15 17:00:00
Interzonal: Belgrano – Talleres
Zona A: Instituto – Independiente
Zona A: Union – Boca
Zona A: San Lorenzo – Defensa y Justicia
Zona A: Central Cordoba – Deportivo Riestra
Zona A: Gimnasia (Mza.) – Estudiantes
Zona A: Lanus – Newell's
Zona A: Platense – Velez
Zona B: Racing – Estudiantes (Rio Cuarto)
Zona B: River – Sarmiento
Zona B: Aldosivi – Huracan
Zona B: Barracas Central – Atletico Tucuman
Zona B: Gimnasia – Independiente Rivadavia Mza.
Zona B: Rosario Central – Banfield
Zona B: Tigre – Argentinos
Fecha 12|2026-03-22 17:00:00
Interzonal: Argentinos – Platense
Zona A: Velez – Lanus
Zona A: Newell's – Gimnasia (Mza.)
Zona A: Estudiantes – Central Cordoba
Zona A: Deportivo Riestra – San Lorenzo
Zona A: Defensa y Justicia – Union
Zona A: Boca – Instituto
Zona A: Independiente – Talleres
Zona B: Banfield – Tigre
Zona B: Independiente Rivadavia Mza. – Rosario Central
Zona B: Atletico Tucuman – Gimnasia
Zona B: Huracan – Barracas Central
Zona B: Sarmiento – Aldosivi
Zona B: Estudiantes (Rio Cuarto) – River
Zona B: Belgrano – Racing
Fecha 13|2026-04-05 17:00:00
Interzonal: Independiente – Racing
Zona A: Talleres – Boca
Zona A: Instituto – Defensa y Justicia
Zona A: Union – Deportivo Riestra
Zona A: San Lorenzo – Estudiantes
Zona A: Central Cordoba – Newell's
Zona A: Gimnasia (Mza.) – Velez
Zona A: Lanus – Platense
Zona B: River – Belgrano
Zona B: Aldosivi – Estudiantes (Rio Cuarto)
Zona B: Barracas Central – Sarmiento
Zona B: Gimnasia – Huracan
Zona B: Rosario Central – Atletico Tucuman
Zona B: Tigre – Independiente Rivadavia Mza.
Zona B: Argentinos – Banfield
Fecha 14|2026-04-12 17:00:00
Interzonal: Lanus – Banfield
Zona A: Platense – Gimnasia (Mza.)
Zona A: Velez – Central Cordoba
Zona A: Newell's – San Lorenzo
Zona A: Estudiantes – Union
Zona A: Deportivo Riestra – Instituto
Zona A: Defensa y Justicia – Talleres
Zona A: Boca – Independiente
Zona B: Independiente Rivadavia Mza. – Argentinos
Zona B: Atletico Tucuman – Tigre
Zona B: Huracan – Rosario Central
Zona B: Sarmiento – Gimnasia
Zona B: Estudiantes (Rio Cuarto) – Barracas Central
Zona B: Belgrano – Aldosivi
Zona B: Racing – River
Fecha 15|2026-04-19 17:00:00
Interzonal: River – Boca
Zona A: Independiente – Defensa y Justicia
Zona A: Talleres – Deportivo Riestra
Zona A: Instituto – Estudiantes
Zona A: Union – Newell's
Zona A: San Lorenzo – Velez
Zona A: Central Cordoba – Platense
Zona A: Gimnasia (Mza.) – Lanus
Zona B: Aldosivi – Racing
Zona B: Barracas Central – Belgrano
Zona B: Gimnasia – Estudiantes (Rio Cuarto)
Zona B: Rosario Central – Sarmiento
Zona B: Tigre – Huracan
Zona B: Argentinos – Atletico Tucuman
Zona B: Banfield – Independiente Rivadavia Mza.
Fecha 16|2026-04-26 17:00:00
Interzonal: Independiente Rivadavia Mza. – Gimnasia (Mza.)
Zona A: Lanus – Central Cordoba
Zona A: Platense – San Lorenzo
Zona A: Velez – Union
Zona A: Newell's – Instituto
Zona A: Estudiantes – Talleres
Zona A: Deportivo Riestra – Independiente
Zona A: Defensa y Justicia – Boca
Zona B: Atletico Tucuman – Banfield
Zona B: Huracan – Argentinos
Zona B: Sarmiento – Tigre
Zona B: Estudiantes (Rio Cuarto) – Rosario Central
Zona B: Belgrano – Gimnasia
Zona B: Racing – Barracas Central
Zona B: River – Aldosivi
TXT;

        $events = [];
        $currentRound = '';
        $currentDate = '';
        $matchNumber = 1;

        foreach (preg_split('/\r\n|\r|\n/', trim($fixture)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, 'Fecha ')) {
                [$currentRound, $currentDate] = explode('|', $line);
                continue;
            }

            [$group, $match] = explode(': ', $line, 2);
            [$home, $away] = preg_split('/\s+–\s+/', $match);

            [$scoreHome, $scoreAway] = $this->deterministicScore($matchNumber, $home, $away);

            $events[] = [
                'league_id' => $leagueId,
                'stage' => 'Torneo Apertura 2026 - Fase regular',
                'group_name' => $currentRound . ' - ' . $group,
                'match_number' => $matchNumber,
                'home_team' => $home,
                'home_flag' => 'ar',
                'away_team' => $away,
                'away_flag' => 'ar',
                'start_time' => date('Y-m-d H:i:s', strtotime($currentDate . ' +' . (($matchNumber - 1) % 5) . ' hours')),
                'venue' => $this->venueFor($home),
                'status' => 'finished',
                'settled' => 1,
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
            ];
            $matchNumber++;
        }

        return $events;
    }

    private function playoffs(int $leagueId): array
    {
        $rows = [
            ['2026-05-09 16:30:00', 'Octavos de final', 'Talleres', 'Belgrano', 1, 2],
            ['2026-05-09 19:00:00', 'Octavos de final', 'Boca', 'Huracan', 2, 1],
            ['2026-05-09 21:30:00', 'Octavos de final', 'Argentinos', 'Lanus', 1, 0],
            ['2026-05-09 21:30:00', 'Octavos de final', 'Independiente Rivadavia Mza.', 'Union', 0, 1],
            ['2026-05-10 15:00:00', 'Octavos de final', 'Rosario Central', 'Independiente', 2, 0],
            ['2026-05-10 17:00:00', 'Octavos de final', 'Estudiantes', 'Racing', 1, 0],
            ['2026-05-10 19:00:00', 'Octavos de final', 'River', 'San Lorenzo', 2, 0],
            ['2026-05-10 21:30:00', 'Octavos de final', 'Velez', 'Gimnasia', 0, 1],
            ['2026-05-12 19:00:00', 'Cuartos de final', 'Estudiantes', 'Gimnasia', 0, 1],
            ['2026-05-12 21:30:00', 'Cuartos de final', 'Rosario Central', 'River', 1, 2],
            ['2026-05-13 19:00:00', 'Cuartos de final', 'Boca', 'Argentinos', 0, 1],
            ['2026-05-13 21:30:00', 'Cuartos de final', 'Belgrano', 'Union', 2, 1],
            ['2026-05-16 19:00:00', 'Semifinales', 'River', 'Gimnasia', 2, 1],
            ['2026-05-17 19:00:00', 'Semifinales', 'Belgrano', 'Argentinos', 1, 1],
        ];

        $events = [];
        foreach ($rows as $index => [$date, $stage, $home, $away, $scoreHome, $scoreAway]) {
            $events[] = [
                'league_id' => $leagueId,
                'stage' => 'Torneo Apertura 2026 - ' . $stage,
                'group_name' => null,
                'match_number' => 300 + $index,
                'home_team' => $home,
                'home_flag' => 'ar',
                'away_team' => $away,
                'away_flag' => 'ar',
                'start_time' => $date,
                'venue' => $this->venueFor($home),
                'status' => 'finished',
                'settled' => 1,
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
            ];
        }

        return $events;
    }

    private function final(int $leagueId): array
    {
        return [[
            'league_id' => $leagueId,
            'stage' => 'Torneo Apertura 2026 - Final',
            'group_name' => null,
            'match_number' => 400,
            'home_team' => 'River',
            'home_flag' => 'ar',
            'away_team' => 'Belgrano',
            'away_flag' => 'ar',
            'start_time' => '2026-05-24 15:30:00',
            'venue' => 'Estadio Mario Alberto Kempes, Cordoba',
            'status' => 'pending',
            'settled' => 0,
            'score_home' => null,
            'score_away' => null,
        ]];
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
        $name = 'Liga Profesional Argentina (Archivado)';
        $archive = $db->table('leagues')->where('name', $name)->get()->getRowArray();

        if ($archive) {
            return (int) $archive['id'];
        }

        $db->table('leagues')->insert([
            'sport_id' => $source['sport_id'] ?? 1,
            'name' => $name,
            'country' => 'Argentina',
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

        if ($event['status'] === 'pending') {
            $this->createQualifiesMarket($eventId, $event);
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

        $homeOdds = round(1.65 + (($event['match_number'] * 11) % 130) / 100, 2);
        $drawOdds = round(2.80 + (($event['match_number'] * 7) % 130) / 100, 2);
        $awayOdds = round(1.75 + (($event['match_number'] * 17) % 150) / 100, 2);

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

    private function createQualifiesMarket(int $eventId, array $event): void
    {
        $db = \Config\Database::connect();

        $db->table('markets')->insert([
            'event_id' => $eventId,
            'name' => 'Campeon del Torneo Apertura',
            'type' => 'outright',
            'status' => 'open',
        ]);
        $marketId = (int) $db->insertID();

        foreach ([[$event['home_team'], 1.88], [$event['away_team'], 1.96]] as [$selection, $odds]) {
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
        $awayScore = ($matchNumber + strlen($away) + 1) % 3;

        return [$homeScore, $awayScore];
    }

    private function venueFor(string $club): string
    {
        $map = [
            'Argentinos' => 'Estadio Diego Armando Maradona',
            'Aldosivi' => 'Estadio Jose Maria Minella',
            'Atletico Tucuman' => 'Estadio Monumental Jose Fierro',
            'Banfield' => 'Estadio Florencio Sola',
            'Barracas Central' => 'Estadio Claudio Tapia',
            'Belgrano' => 'Estadio Julio Cesar Villagra',
            'Boca' => 'La Bombonera',
            'Central Cordoba' => 'Estadio Madre de Ciudades',
            'Defensa y Justicia' => 'Estadio Norberto Tomaghello',
            'Deportivo Riestra' => 'Estadio Guillermo Laza',
            'Estudiantes' => 'Estadio UNO Jorge Luis Hirschi',
            'Estudiantes (Rio Cuarto)' => 'Estadio Antonio Candini',
            'Gimnasia' => 'Estadio Juan Carmelo Zerillo',
            'Gimnasia (Mza.)' => 'Estadio Victor Legrotaglie',
            'Huracan' => 'Estadio Tomas Adolfo Duco',
            'Independiente' => 'Estadio Libertadores de America',
            'Independiente Rivadavia Mza.' => 'Estadio Bautista Gargantini',
            'Instituto' => 'Estadio Juan Domingo Peron',
            'Lanus' => 'Estadio Ciudad de Lanus',
            "Newell's" => 'Estadio Marcelo Bielsa',
            'Platense' => 'Estadio Ciudad de Vicente Lopez',
            'Racing' => 'Estadio Presidente Peron',
            'River' => 'Estadio Mas Monumental',
            'Rosario Central' => 'Estadio Gigante de Arroyito',
            'San Lorenzo' => 'Estadio Pedro Bidegain',
            'Sarmiento' => 'Estadio Eva Peron',
            'Talleres' => 'Estadio Mario Alberto Kempes',
            'Tigre' => 'Estadio Jose Dellagiovanna',
            'Union' => 'Estadio 15 de Abril',
            'Velez' => 'Estadio Jose Amalfitani',
        ];

        return $map[$club] ?? 'Estadio local';
    }
}
