<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ChampionsLeague2026Seeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $sport = $db->table('sports')->whereIn('slug', ['futbol', 'football'])->get()->getRowArray();
        if (! $sport) {
            $sportId = $db->table('sports')->insert([
                'name' => 'Futbol',
                'slug' => 'futbol',
                'icon' => '⚽',
                'active' => 1,
            ]);
        } else {
            $sportId = $sport['id'];
        }

        $db->table('leagues')
            ->where('name', 'Champions League')
            ->update(['name' => 'UEFA Champions League 2025/26']);

        $league = $db->table('leagues')->where('name', 'UEFA Champions League 2025/26')->get()->getRowArray();
        if (! $league) {
            $leagueId = $db->table('leagues')->insert([
                'sport_id' => $sportId,
                'name' => 'UEFA Champions League 2025/26',
                'country' => 'Europa',
                'active' => 1,
            ]);
        } else {
            $leagueId = $league['id'];
            $db->table('leagues')->where('id', $leagueId)->update([
                'sport_id' => $sportId,
                'country' => 'Europa',
                'active' => 1,
            ]);
        }

        $this->clearLeagueEvents($leagueId);

        $events = array_merge(
            $this->leaguePhase($leagueId),
            $this->playoffs($leagueId),
            $this->roundOf16($leagueId),
            $this->quarterFinals($leagueId),
            $this->semiFinals($leagueId),
            $this->final($leagueId)
        );

        $db->transStart();
        foreach ($events as $event) {
            $db->table('events')->insert($event);
            $eventId = $db->insertID();
            $this->createWinnerMarket($eventId, $event);
        }
        $db->transComplete();

        echo count($events) . " partidos insertados para UEFA Champions League 2025/26.\n";
    }

    private function leaguePhase(int $leagueId): array
    {
        $rows = [
            ['2025-09-16 21:00:00', 'Jornada 1', 'Athletic Club', 'Arsenal', 0, 2],
            ['2025-09-16 21:00:00', 'Jornada 1', 'PSV Eindhoven', 'Union Saint-Gilloise', 1, 3],
            ['2025-09-16 21:00:00', 'Jornada 1', 'Juventus', 'Borussia Dortmund', 4, 4],
            ['2025-09-16 21:00:00', 'Jornada 1', 'Real Madrid', 'Marseille', 2, 1],
            ['2025-09-16 21:00:00', 'Jornada 1', 'Benfica', 'Qarabag', 2, 3],
            ['2025-09-16 21:00:00', 'Jornada 1', 'Tottenham', 'Villarreal', 1, 0],
            ['2025-09-17 21:00:00', 'Jornada 1', 'Olympiacos', 'Pafos', 0, 0],
            ['2025-09-17 21:00:00', 'Jornada 1', 'Slavia Praha', 'Bodo/Glimt', 2, 2],
            ['2025-09-17 21:00:00', 'Jornada 1', 'Ajax', 'Inter', 0, 2],
            ['2025-09-17 21:00:00', 'Jornada 1', 'Bayern Munchen', 'Chelsea', 3, 1],
            ['2025-09-17 21:00:00', 'Jornada 1', 'Liverpool', 'Atletico de Madrid', 3, 2],
            ['2025-09-17 21:00:00', 'Jornada 1', 'Paris Saint-Germain', 'Atalanta', 4, 0],
            ['2025-09-18 21:00:00', 'Jornada 1', 'Club Brugge', 'Monaco', 4, 1],
            ['2025-09-18 21:00:00', 'Jornada 1', 'Copenhagen', 'Leverkusen', 2, 2],
            ['2025-09-18 21:00:00', 'Jornada 1', 'Frankfurt', 'Galatasaray', 5, 1],
            ['2025-09-18 21:00:00', 'Jornada 1', 'Manchester City', 'Napoli', 2, 0],
            ['2025-09-18 21:00:00', 'Jornada 1', 'Newcastle United', 'Barcelona', 1, 2],
            ['2025-09-18 21:00:00', 'Jornada 1', 'Sporting CP', 'Kairat Almaty', 4, 1],

            ['2025-09-30 21:00:00', 'Jornada 2', 'Atalanta', 'Club Brugge', 2, 1],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Kairat Almaty', 'Real Madrid', 0, 5],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Atletico de Madrid', 'Frankfurt', 5, 1],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Chelsea', 'Benfica', 1, 0],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Inter', 'Slavia Praha', 3, 0],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Bodo/Glimt', 'Tottenham', 2, 2],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Galatasaray', 'Liverpool', 1, 0],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Marseille', 'Ajax', 4, 0],
            ['2025-09-30 21:00:00', 'Jornada 2', 'Pafos', 'Bayern Munchen', 1, 5],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Qarabag', 'Copenhagen', 2, 0],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Union Saint-Gilloise', 'Newcastle United', 0, 4],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Arsenal', 'Olympiacos', 2, 0],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Monaco', 'Manchester City', 2, 2],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Leverkusen', 'PSV Eindhoven', 1, 1],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Borussia Dortmund', 'Athletic Club', 4, 1],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Barcelona', 'Paris Saint-Germain', 1, 2],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Napoli', 'Sporting CP', 2, 1],
            ['2025-10-01 21:00:00', 'Jornada 2', 'Villarreal', 'Juventus', 2, 2],

            ['2025-10-21 21:00:00', 'Jornada 3', 'Barcelona', 'Olympiacos', 6, 1],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Kairat Almaty', 'Pafos', 0, 0],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Arsenal', 'Atletico de Madrid', 4, 0],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Leverkusen', 'Paris Saint-Germain', 2, 7],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Copenhagen', 'Borussia Dortmund', 2, 4],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Newcastle United', 'Benfica', 3, 0],
            ['2025-10-21 21:00:00', 'Jornada 3', 'PSV Eindhoven', 'Napoli', 6, 2],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Union Saint-Gilloise', 'Inter', 0, 4],
            ['2025-10-21 21:00:00', 'Jornada 3', 'Villarreal', 'Manchester City', 0, 2],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Athletic Club', 'Qarabag', 3, 1],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Galatasaray', 'Bodo/Glimt', 3, 1],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Monaco', 'Tottenham', 0, 0],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Atalanta', 'Slavia Praha', 0, 0],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Chelsea', 'Ajax', 5, 1],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Frankfurt', 'Liverpool', 1, 5],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Bayern Munchen', 'Club Brugge', 4, 0],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Real Madrid', 'Juventus', 1, 0],
            ['2025-10-22 21:00:00', 'Jornada 3', 'Sporting CP', 'Marseille', 2, 1],

            ['2025-11-04 21:00:00', 'Jornada 4', 'Slavia Praha', 'Arsenal', 0, 3],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Napoli', 'Frankfurt', 0, 0],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Atletico de Madrid', 'Union Saint-Gilloise', 3, 1],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Bodo/Glimt', 'Monaco', 0, 1],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Juventus', 'Sporting CP', 1, 1],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Liverpool', 'Real Madrid', 1, 0],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Olympiacos', 'PSV Eindhoven', 1, 1],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Paris Saint-Germain', 'Bayern Munchen', 1, 2],
            ['2025-11-04 21:00:00', 'Jornada 4', 'Tottenham', 'Copenhagen', 4, 0],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Pafos', 'Villarreal', 1, 0],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Qarabag', 'Chelsea', 2, 2],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Ajax', 'Galatasaray', 0, 3],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Club Brugge', 'Barcelona', 3, 3],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Inter', 'Kairat Almaty', 2, 1],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Manchester City', 'Borussia Dortmund', 4, 1],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Newcastle United', 'Athletic Club', 2, 0],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Marseille', 'Atalanta', 0, 1],
            ['2025-11-05 21:00:00', 'Jornada 4', 'Benfica', 'Leverkusen', 0, 1],

            ['2025-11-25 21:00:00', 'Jornada 5', 'Ajax', 'Benfica', 0, 2],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Galatasaray', 'Union Saint-Gilloise', 0, 1],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Borussia Dortmund', 'Villarreal', 4, 0],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Chelsea', 'Barcelona', 3, 0],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Bodo/Glimt', 'Juventus', 2, 3],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Manchester City', 'Leverkusen', 0, 2],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Marseille', 'Newcastle United', 2, 1],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Slavia Praha', 'Athletic Club', 0, 0],
            ['2025-11-25 21:00:00', 'Jornada 5', 'Napoli', 'Qarabag', 2, 0],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Copenhagen', 'Kairat Almaty', 3, 2],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Pafos', 'Monaco', 2, 2],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Arsenal', 'Bayern Munchen', 3, 1],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Atletico de Madrid', 'Inter', 2, 1],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Frankfurt', 'Atalanta', 0, 3],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Liverpool', 'PSV Eindhoven', 1, 4],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Olympiacos', 'Real Madrid', 3, 4],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Paris Saint-Germain', 'Tottenham', 5, 3],
            ['2025-11-26 21:00:00', 'Jornada 5', 'Sporting CP', 'Club Brugge', 3, 0],

            ['2025-12-09 21:00:00', 'Jornada 6', 'Kairat Almaty', 'Olympiacos', 0, 1],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Bayern Munchen', 'Sporting CP', 3, 1],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Monaco', 'Galatasaray', 1, 0],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Atalanta', 'Chelsea', 2, 1],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Barcelona', 'Frankfurt', 2, 1],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Inter', 'Liverpool', 0, 1],
            ['2025-12-09 21:00:00', 'Jornada 6', 'PSV Eindhoven', 'Atletico de Madrid', 2, 3],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Union Saint-Gilloise', 'Marseille', 2, 3],
            ['2025-12-09 21:00:00', 'Jornada 6', 'Tottenham', 'Slavia Praha', 3, 0],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Qarabag', 'Ajax', 2, 4],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Villarreal', 'Copenhagen', 2, 3],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Athletic Club', 'Paris Saint-Germain', 0, 0],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Leverkusen', 'Newcastle United', 2, 2],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Borussia Dortmund', 'Bodo/Glimt', 2, 2],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Club Brugge', 'Arsenal', 0, 3],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Juventus', 'Pafos', 2, 0],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Real Madrid', 'Manchester City', 1, 2],
            ['2025-12-10 21:00:00', 'Jornada 6', 'Benfica', 'Napoli', 2, 0],

            ['2026-01-20 21:00:00', 'Jornada 7', 'Kairat Almaty', 'Club Brugge', 1, 4],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Bodo/Glimt', 'Manchester City', 3, 1],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Copenhagen', 'Napoli', 1, 1],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Inter', 'Arsenal', 1, 3],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Olympiacos', 'Leverkusen', 2, 0],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Real Madrid', 'Monaco', 6, 1],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Sporting CP', 'Paris Saint-Germain', 2, 1],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Tottenham', 'Borussia Dortmund', 2, 0],
            ['2026-01-20 21:00:00', 'Jornada 7', 'Villarreal', 'Ajax', 1, 2],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Galatasaray', 'Atletico de Madrid', 1, 1],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Qarabag', 'Frankfurt', 3, 2],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Atalanta', 'Athletic Club', 2, 3],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Chelsea', 'Pafos', 1, 0],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Bayern Munchen', 'Union Saint-Gilloise', 2, 0],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Juventus', 'Benfica', 2, 0],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Newcastle United', 'PSV Eindhoven', 3, 0],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Marseille', 'Liverpool', 0, 3],
            ['2026-01-21 21:00:00', 'Jornada 7', 'Slavia Praha', 'Barcelona', 2, 4],

            ['2026-01-28 21:00:00', 'Jornada 8', 'Ajax', 'Olympiacos', 1, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Arsenal', 'Kairat Almaty', 3, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Monaco', 'Juventus', 0, 0],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Athletic Club', 'Sporting CP', 2, 3],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Atletico de Madrid', 'Bodo/Glimt', 1, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Leverkusen', 'Villarreal', 3, 0],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Borussia Dortmund', 'Inter', 0, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Club Brugge', 'Marseille', 3, 0],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Frankfurt', 'Tottenham', 0, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Barcelona', 'Copenhagen', 4, 1],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Liverpool', 'Qarabag', 6, 0],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Manchester City', 'Galatasaray', 2, 0],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Pafos', 'Slavia Praha', 4, 1],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Paris Saint-Germain', 'Newcastle United', 1, 1],
            ['2026-01-28 21:00:00', 'Jornada 8', 'PSV Eindhoven', 'Bayern Munchen', 1, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Union Saint-Gilloise', 'Atalanta', 1, 0],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Benfica', 'Real Madrid', 4, 2],
            ['2026-01-28 21:00:00', 'Jornada 8', 'Napoli', 'Chelsea', 2, 3],
        ];

        return $this->eventsFromRows($leagueId, 'Fase liga', $rows, 1);
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
                $marketIds = array_column(
                    $db->table('markets')->select('id')->where('event_id', $event['id'])->get()->getResultArray(),
                    'id'
                );

                if ($marketIds !== []) {
                    $db->table('odds')->whereIn('market_id', $marketIds)->update(['active' => 0, 'status' => 'void']);
                    $db->table('markets')->whereIn('id', $marketIds)->update(['status' => 'closed']);
                }
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
        $name = 'UEFA Champions League 2025/26 (Archivado)';
        $archive = $db->table('leagues')->where('name', $name)->get()->getRowArray();

        if ($archive) {
            return (int) $archive['id'];
        }

        $db->table('leagues')->insert([
            'sport_id' => $source['sport_id'] ?? 1,
            'name' => $name,
            'country' => 'Europa',
            'active' => 0,
        ]);

        return (int) $db->insertID();
    }

    private function playoffs(int $leagueId): array
    {
        $rows = [
            ['2026-02-17 21:00:00', 'Ida', 'Galatasaray', 'Juventus', 5, 2],
            ['2026-02-17 21:00:00', 'Ida', 'Monaco', 'Paris Saint-Germain', 2, 3],
            ['2026-02-17 21:00:00', 'Ida', 'Borussia Dortmund', 'Atalanta', 2, 0],
            ['2026-02-17 21:00:00', 'Ida', 'Benfica', 'Real Madrid', 0, 1],
            ['2026-02-18 21:00:00', 'Ida', 'Qarabag', 'Newcastle United', 1, 6],
            ['2026-02-18 21:00:00', 'Ida', 'Club Brugge', 'Atletico de Madrid', 3, 3],
            ['2026-02-18 21:00:00', 'Ida', 'Bodo/Glimt', 'Inter', 3, 1],
            ['2026-02-18 21:00:00', 'Ida', 'Olympiacos', 'Leverkusen', 0, 2],
            ['2026-02-24 21:00:00', 'Vuelta', 'Atletico de Madrid', 'Club Brugge', 4, 1],
            ['2026-02-24 21:00:00', 'Vuelta', 'Leverkusen', 'Olympiacos', 0, 0],
            ['2026-02-24 21:00:00', 'Vuelta', 'Inter', 'Bodo/Glimt', 1, 2],
            ['2026-02-24 21:00:00', 'Vuelta', 'Newcastle United', 'Qarabag', 3, 2],
            ['2026-02-25 21:00:00', 'Vuelta', 'Atalanta', 'Borussia Dortmund', 4, 1],
            ['2026-02-25 21:00:00', 'Vuelta', 'Juventus', 'Galatasaray', 3, 2],
            ['2026-02-25 21:00:00', 'Vuelta', 'Paris Saint-Germain', 'Monaco', 2, 2],
            ['2026-02-25 21:00:00', 'Vuelta', 'Real Madrid', 'Benfica', 2, 1],
        ];

        return $this->eventsFromRows($leagueId, 'Play-offs eliminatorios', $rows, 200);
    }

    private function roundOf16(int $leagueId): array
    {
        $rows = [
            ['2026-03-10 21:00:00', 'Ida', 'Galatasaray', 'Liverpool', 1, 0],
            ['2026-03-10 21:00:00', 'Ida', 'Atalanta', 'Bayern Munchen', 1, 6],
            ['2026-03-10 21:00:00', 'Ida', 'Atletico de Madrid', 'Tottenham', 5, 2],
            ['2026-03-10 21:00:00', 'Ida', 'Newcastle United', 'Barcelona', 1, 1],
            ['2026-03-11 21:00:00', 'Ida', 'Leverkusen', 'Arsenal', 1, 1],
            ['2026-03-11 21:00:00', 'Ida', 'Bodo/Glimt', 'Sporting CP', 3, 0],
            ['2026-03-11 21:00:00', 'Ida', 'Paris Saint-Germain', 'Chelsea', 5, 2],
            ['2026-03-11 21:00:00', 'Ida', 'Real Madrid', 'Manchester City', 3, 0],
            ['2026-03-17 21:00:00', 'Vuelta', 'Sporting CP', 'Bodo/Glimt', 5, 0],
            ['2026-03-17 21:00:00', 'Vuelta', 'Arsenal', 'Leverkusen', 2, 0],
            ['2026-03-17 21:00:00', 'Vuelta', 'Chelsea', 'Paris Saint-Germain', 0, 3],
            ['2026-03-17 21:00:00', 'Vuelta', 'Manchester City', 'Real Madrid', 1, 2],
            ['2026-03-18 21:00:00', 'Vuelta', 'Barcelona', 'Newcastle United', 7, 2],
            ['2026-03-18 21:00:00', 'Vuelta', 'Bayern Munchen', 'Atalanta', 4, 1],
            ['2026-03-18 21:00:00', 'Vuelta', 'Liverpool', 'Galatasaray', 4, 0],
            ['2026-03-18 21:00:00', 'Vuelta', 'Tottenham', 'Atletico de Madrid', 3, 2],
        ];

        return $this->eventsFromRows($leagueId, 'Octavos de final', $rows, 300);
    }

    private function quarterFinals(int $leagueId): array
    {
        $rows = [
            ['2026-04-07 21:00:00', 'Ida', 'Sporting CP', 'Arsenal', 0, 1],
            ['2026-04-07 21:00:00', 'Ida', 'Real Madrid', 'Bayern Munchen', 1, 2],
            ['2026-04-08 21:00:00', 'Ida', 'Barcelona', 'Atletico de Madrid', 0, 2],
            ['2026-04-08 21:00:00', 'Ida', 'Paris Saint-Germain', 'Liverpool', 2, 0],
            ['2026-04-14 21:00:00', 'Vuelta', 'Atletico de Madrid', 'Barcelona', 1, 2],
            ['2026-04-14 21:00:00', 'Vuelta', 'Liverpool', 'Paris Saint-Germain', 0, 2],
            ['2026-04-15 21:00:00', 'Vuelta', 'Arsenal', 'Sporting CP', 0, 0],
            ['2026-04-15 21:00:00', 'Vuelta', 'Bayern Munchen', 'Real Madrid', 4, 3],
        ];

        return $this->eventsFromRows($leagueId, 'Cuartos de final', $rows, 400);
    }

    private function semiFinals(int $leagueId): array
    {
        $rows = [
            ['2026-04-28 21:00:00', 'Ida', 'Paris Saint-Germain', 'Bayern Munchen', 5, 4],
            ['2026-04-29 21:00:00', 'Ida', 'Atletico de Madrid', 'Arsenal', 1, 1],
            ['2026-05-05 21:00:00', 'Vuelta', 'Arsenal', 'Atletico de Madrid', 1, 0],
            ['2026-05-06 21:00:00', 'Vuelta', 'Bayern Munchen', 'Paris Saint-Germain', 1, 1],
        ];

        return $this->eventsFromRows($leagueId, 'Semifinales', $rows, 500);
    }

    private function final(int $leagueId): array
    {
        return [[
            'league_id' => $leagueId,
            'stage' => 'Final',
            'group_name' => null,
            'match_number' => 600,
            'home_team' => 'Paris Saint-Germain',
            'home_flag' => 'fr',
            'away_team' => 'Arsenal',
            'away_flag' => 'gb-eng',
            'start_time' => '2026-05-30 13:00:00',
            'venue' => 'Puskas Arena, Budapest',
            'status' => 'pending',
            'settled' => 0,
            'score_home' => null,
            'score_away' => null,
        ]];
    }

    private function eventsFromRows(int $leagueId, string $stage, array $rows, int $startNumber): array
    {
        $events = [];
        foreach ($rows as $index => [$date, $group, $home, $away, $scoreHome, $scoreAway]) {
            $events[] = [
                'league_id' => $leagueId,
                'stage' => $stage,
                'group_name' => $group,
                'match_number' => $startNumber + $index,
                'home_team' => $home,
                'home_flag' => $this->clubCountry($home),
                'away_team' => $away,
                'away_flag' => $this->clubCountry($away),
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

    private function createWinnerMarket(int $eventId, array $event): void
    {
        $db = \Config\Database::connect();

        $db->table('markets')->insert([
            'event_id' => $eventId,
            'name' => 'Ganador del Partido',
            'type' => '1x2',
            'status' => $event['status'] === 'pending' ? 'open' : 'closed',
        ]);
        $marketId = $db->insertID();

        $homeOdds = round(1.55 + (($event['match_number'] * 13) % 140) / 100, 2);
        $drawOdds = round(2.90 + (($event['match_number'] * 7) % 120) / 100, 2);
        $awayOdds = round(1.70 + (($event['match_number'] * 19) % 160) / 100, 2);

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

    private function clubCountry(string $club): string
    {
        $map = [
            'Ajax' => 'nl', 'Arsenal' => 'gb-eng', 'Aston Villa' => 'gb-eng', 'Atalanta' => 'it',
            'Athletic Club' => 'es', 'Atletico de Madrid' => 'es', 'Barcelona' => 'es',
            'Bayern Munchen' => 'de', 'Benfica' => 'pt', 'Bodo/Glimt' => 'no',
            'Borussia Dortmund' => 'de', 'Chelsea' => 'gb-eng', 'Club Brugge' => 'be',
            'Copenhagen' => 'dk', 'Frankfurt' => 'de', 'Galatasaray' => 'tr',
            'Inter' => 'it', 'Juventus' => 'it', 'Kairat Almaty' => 'kz',
            'Leverkusen' => 'de', 'Liverpool' => 'gb-eng', 'Manchester City' => 'gb-eng',
            'Marseille' => 'fr', 'Monaco' => 'mc', 'Napoli' => 'it', 'Newcastle United' => 'gb-eng',
            'Olympiacos' => 'gr', 'Pafos' => 'cy', 'Paris Saint-Germain' => 'fr',
            'PSV Eindhoven' => 'nl', 'Qarabag' => 'az', 'Real Madrid' => 'es',
            'Slavia Praha' => 'cz', 'Sporting CP' => 'pt', 'Tottenham' => 'gb-eng',
            'Union Saint-Gilloise' => 'be', 'Villarreal' => 'es',
        ];

        return $map[$club] ?? 'eu';
    }

    private function venueFor(string $club): string
    {
        $map = [
            'Paris Saint-Germain' => 'Parc des Princes',
            'Arsenal' => 'Arsenal Stadium',
            'Bayern Munchen' => 'Football Arena Munich',
            'Atletico de Madrid' => 'Metropolitano Stadium',
            'Barcelona' => 'Estadi Olimpic Lluis Companys',
            'Real Madrid' => 'Santiago Bernabeu',
            'Liverpool' => 'Anfield',
            'Sporting CP' => 'Estadio Jose Alvalade',
        ];

        return $map[$club] ?? 'Estadio local';
    }
}
