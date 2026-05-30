<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Nba2025Seeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Insert/Retrieve the Sport "Baloncesto"
        $sport = $db->table('sports')->whereIn('slug', ['baloncesto', 'basketball'])->get()->getRowArray();
        if (!$sport) {
            $db->table('sports')->insert([
                'name'   => 'Baloncesto',
                'slug'   => 'baloncesto',
                'icon'   => '🏀',
                'active' => 1,
            ]);
            $sportId = (int) $db->insertID();
        } else {
            $sportId = (int) $sport['id'];
        }

        // 2. Insert/Retrieve the League "NBA"
        $league = $db->table('leagues')->where('name', 'NBA')->get()->getRowArray();
        if (!$league) {
            $db->table('leagues')->insert([
                'sport_id' => $sportId,
                'name'     => 'NBA',
                'country'  => 'USA',
                'active'   => 1,
            ]);
            $leagueId = (int) $db->insertID();
        } else {
            $leagueId = (int) $league['id'];
            $db->table('leagues')->where('id', $leagueId)->update([
                'sport_id' => $sportId,
                'country'  => 'USA',
                'active'   => 1,
            ]);
        }

        // 3. Clear existing events for NBA
        $existingEvents = $db->table('events')->where('league_id', $leagueId)->get()->getResultArray();
        if (!empty($existingEvents)) {
            $eventIds = array_column($existingEvents, 'id');
            // Delete odds associated with these events
            $db->table('markets')->whereIn('event_id', $eventIds)->get()->getResultArray();
            
            // To be safe, clear markets and odds in transactions
            $db->table('events')->where('league_id', $leagueId)->delete();
        }

        // 4. NBA 30 Teams & Venues Definition
        $teams = [
            // Eastern Conference
            ['Boston Celtics', 'us', 'TD Garden'],
            ['Brooklyn Nets', 'us', 'Barclays Center'],
            ['New York Knicks', 'us', 'Madison Square Garden'],
            ['Philadelphia 76ers', 'us', 'Wells Fargo Center'],
            ['Toronto Raptors', 'ca', 'Scotiabank Arena'],
            ['Chicago Bulls', 'us', 'United Center'],
            ['Cleveland Cavaliers', 'us', 'Rocket Mortgage FieldHouse'],
            ['Detroit Pistons', 'us', 'Little Caesars Arena'],
            ['Indiana Pacers', 'us', 'Gainbridge Fieldhouse'],
            ['Milwaukee Bucks', 'us', 'Fiserv Forum'],
            ['Atlanta Hawks', 'us', 'State Farm Arena'],
            ['Charlotte Hornets', 'us', 'Spectrum Center'],
            ['Miami Heat', 'us', 'Kaseya Center'],
            ['Orlando Magic', 'us', 'Kia Center'],
            ['Washington Wizards', 'us', 'Capital One Arena'],
            // Western Conference
            ['Denver Nuggets', 'us', 'Ball Arena'],
            ['Minnesota Timberwolves', 'us', 'Target Center'],
            ['Oklahoma City Thunder', 'us', 'Paycom Center'],
            ['Portland Trail Blazers', 'us', 'Moda Center'],
            ['Utah Jazz', 'us', 'Delta Center'],
            ['Golden State Warriors', 'us', 'Chase Center'],
            ['Los Angeles Clippers', 'us', 'Intuit Dome'],
            ['Los Angeles Lakers', 'us', 'Crypto.com Arena'],
            ['Phoenix Suns', 'us', 'Footprint Center'],
            ['Sacramento Kings', 'us', 'Golden 1 Center'],
            ['Dallas Mavericks', 'us', 'American Airlines Center'],
            ['Houston Rockets', 'us', 'Toyota Center'],
            ['Memphis Grizzlies', 'us', 'FedExForum'],
            ['New Orleans Pelicans', 'us', 'Smoothie King Center'],
            ['San Antonio Spurs', 'us', 'Frost Bank Center'],
        ];

        $events = [];
        $nowTime = time();

        // Let's seed:
        // A. 80 Past Matches (Season History: Oct 2025 - May 2026)
        // B. 3 Live Matches (Playing Right Now)
        // C. 37 Future Matches (Playoffs / Finals Schedule: May 2026 - June 2026)

        // A. Past Matches
        for ($i = 0; $i < 80; $i++) {
            // Pick random home and away teams
            $homeIdx = $i % count($teams);
            $awayIdx = ($i + 7) % count($teams);
            if ($homeIdx === $awayIdx) {
                $awayIdx = ($awayIdx + 1) % count($teams);
            }

            $home = $teams[$homeIdx];
            $away = $teams[$awayIdx];

            // Start time in the past: between 210 days ago and 5 days ago
            $daysAgo = 5 + ($i * 2.5);
            $startTime = date('Y-m-d H:i:s', $nowTime - ($daysAgo * 86400) - rand(0, 72000));

            // Determinisitc score generation (make sure no ties)
            $scoreHome = 95 + (($i * 13) % 35);
            $scoreAway = 95 + (($i * 17) % 35);
            if ($scoreHome === $scoreAway) {
                $scoreHome += 4; // Overtime win simulation
            }

            $events[] = [
                'league_id'  => $leagueId,
                'home_team'  => $home[0],
                'home_flag'  => $home[1],
                'away_team'  => $away[0],
                'away_flag'  => $away[1],
                'start_time' => $startTime,
                'venue'      => $home[2],
                'status'     => 'finished',
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
                'settled'    => 1,
            ];
        }

        // B. Live Matches
        $liveMatchups = [
            ['Lakers', 'Warriors'],
            ['Celtics', 'Heat'],
            ['Nuggets', 'Mavericks'],
        ];

        foreach ($liveMatchups as $idx => [$hName, $aName]) {
            // Find full team details
            $home = null;
            $away = null;
            foreach ($teams as $t) {
                if (strpos($t[0], $hName) !== false) $home = $t;
                if (strpos($t[0], $aName) !== false) $away = $t;
            }

            $home = $home ?: $teams[0];
            $away = $away ?: $teams[1];

            // Started 45 minutes ago
            $startTime = date('Y-m-d H:i:s', $nowTime - (45 * 60));

            $events[] = [
                'league_id'  => $leagueId,
                'home_team'  => $home[0],
                'home_flag'  => $home[1],
                'away_team'  => $away[0],
                'away_flag'  => $away[1],
                'start_time' => $startTime,
                'venue'      => $home[2],
                'status'     => 'live',
                'score_home' => 62 + ($idx * 3),
                'score_away' => 59 + ($idx * 2),
                'settled'    => 0,
            ];
        }

        // C. Future Matches
        for ($i = 0; $i < 37; $i++) {
            $homeIdx = ($i + 3) % count($teams);
            $awayIdx = ($i + 19) % count($teams);
            if ($homeIdx === $awayIdx) {
                $awayIdx = ($awayIdx + 1) % count($teams);
            }

            $home = $teams[$homeIdx];
            $away = $teams[$awayIdx];

            // Start time in future: between 2 hours and 25 days from now
            $hoursFuture = 2 + ($i * 12);
            $startTime = date('Y-m-d H:i:s', $nowTime + ($hoursFuture * 3600));

            $events[] = [
                'league_id'  => $leagueId,
                'home_team'  => $home[0],
                'home_flag'  => $home[1],
                'away_team'  => $away[0],
                'away_flag'  => $away[1],
                'start_time' => $startTime,
                'venue'      => $home[2],
                'status'     => 'pending',
                'score_home' => null,
                'score_away' => null,
                'settled'    => 0,
            ];
        }

        // 5. Insert events and markets
        $db->transStart();
        foreach ($events as $idx => $event) {
            $db->table('events')->insert($event);
            $eventId = (int) $db->insertID();

            // Market 1: Ganador del Partido (Moneyline)
            $db->table('markets')->insert([
                'event_id' => $eventId,
                'name'     => 'Ganador del Partido',
                'type'     => '1x2',
                'status'   => ($event['status'] === 'finished') ? 'suspended' : 'open',
            ]);
            $market1Id = (int) $db->insertID();

            // Calculate realistic odds
            $baseOddsHome = 1.40 + (($idx * 13) % 150) / 100;
            $baseOddsAway = 1.40 + (($idx * 27) % 150) / 100;
            
            // Normalize so margin is realistic
            $probHome = 1 / $baseOddsHome;
            $probAway = 1 / $baseOddsAway;
            $margin = 1.05; // 5% bookmaker margin
            $totalProb = $probHome + $probAway;
            $oddsHome = round(($baseOddsHome * $totalProb) / $margin, 2);
            $oddsAway = round(($baseOddsAway * $totalProb) / $margin, 2);

            $db->table('odds')->insert([
                'market_id'    => $market1Id,
                'selection'    => '1',
                'odds_decimal' => $oddsHome,
                'active'       => 1,
            ]);

            $db->table('odds')->insert([
                'market_id'    => $market1Id,
                'selection'    => '2',
                'odds_decimal' => $oddsAway,
                'active'       => 1,
            ]);

            // Market 2: Total de Puntos (Over/Under)
            $db->table('markets')->insert([
                'event_id' => $eventId,
                'name'     => 'Total de Puntos',
                'type'     => 'totals',
                'status'   => ($event['status'] === 'finished') ? 'suspended' : 'open',
            ]);
            $market2Id = (int) $db->insertID();

            $totalLine = 215.5 + (($idx * 3) % 7); // e.g. 215.5, 218.5, 221.5...
            
            $db->table('odds')->insert([
                'market_id'    => $market2Id,
                'selection'    => 'Over ' . $totalLine,
                'odds_decimal' => 1.90,
                'active'       => 1,
            ]);

            $db->table('odds')->insert([
                'market_id'    => $market2Id,
                'selection'    => 'Under ' . $totalLine,
                'odds_decimal' => 1.90,
                'active'       => 1,
            ]);
        }
        $db->transComplete();

        echo "Sembramos con éxito " . count($events) . " partidos de la NBA temporada 2025/26.\n";
    }
}
