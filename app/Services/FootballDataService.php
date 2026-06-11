<?php

namespace App\Services;

use App\Libraries\CacheManager;

/**
 * FootballDataService - Fetch real match fixtures and results from football-data.org
 * Efficient, cacheable, and reliable data source for fixtures, results, and standings.
 */
class FootballDataService
{
    private const BASE_URL = 'https://api.football-data.org/v4';
    private const CACHE_TTL = 3600; // 1 hour
    private string $apiKey;
    private CacheManager $cache;

    public function __construct()
    {
        $this->apiKey = env('FOOTBALL_DATA_API_KEY') ?: getenv('FOOTBALL_DATA_API_KEY');
        $this->cache = CacheManager::getInstance();

        if (empty($this->apiKey)) {
            throw new \Exception("FOOTBALL_DATA_API_KEY not configured in .env");
        }
    }

    /**
     * Get available competitions/leagues
     */
    public function getAvailableCompetitions(): array
    {
        $cacheKey = 'football_competitions_list';
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            $response = $this->makeRequest('/competitions');
            $data = json_decode($response, true);

            if (!isset($data['competitions'])) {
                return [];
            }

            $competitions = [];
            foreach ($data['competitions'] as $comp) {
                // Filter to main leagues and international competitions
                if (in_array($comp['code'], [
                    'PL', 'CL', 'PD', 'SA', 'BL1', 'FL1', 'PPL', // European leagues
                    'EC', 'WC', // Cups/International
                ])) {
                    $competitions[] = [
                        'id'      => $comp['id'],
                        'code'    => $comp['code'],
                        'name'    => $comp['name'],
                        'area'    => $comp['area']['name'] ?? 'International',
                        'season'  => $comp['currentSeason']['currentMatchday'] ?? 0
                    ];
                }
            }

            $this->cache->set($cacheKey, $competitions, self::CACHE_TTL);
            return $competitions;
        } catch (\Exception $e) {
            log_message('error', 'Error fetching competitions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get matches (fixtures and results) for a competition
     */
    public function getMatches(int $competitionId, ?string $status = null, int $limit = 50): array
    {
        $cacheKey = "football_matches_{$competitionId}_{$status}_{$limit}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            $params = ['limit' => $limit];
            if ($status) {
                $params['status'] = $status; // SCHEDULED, LIVE, FINISHED, POSTPONED
            }

            $query = http_build_query($params);
            $response = $this->makeRequest("/competitions/{$competitionId}/matches?{$query}");
            $data = json_decode($response, true);

            if (!isset($data['matches'])) {
                return [];
            }

            $matches = [];
            foreach ($data['matches'] as $match) {
                $matches[] = [
                    'id'         => $match['id'],
                    'homeTeam'   => $match['homeTeam']['name'] ?? 'TBD',
                    'awayTeam'   => $match['awayTeam']['name'] ?? 'TBD',
                    'status'     => $match['status'],
                    'kickoffTime' => $match['utcDate'],
                    'homeScore'  => $match['score']['fullTime']['home'] ?? null,
                    'awayScore'  => $match['score']['fullTime']['away'] ?? null,
                    'winner'     => $match['score']['winner'] ?? null, // HOME, AWAY, DRAW
                    'competition' => $match['competition']['name'] ?? '',
                    'stage'      => $match['stage'] ?? '',
                    'group'      => $match['group'] ?? '',
                    'venue'      => $match['venue'] ?? '',
                ];
            }

            // Cache for shorter time if there are live matches
            $ttl = $this->hasLiveMatches($matches) ? 300 : self::CACHE_TTL;
            $this->cache->set($cacheKey, $matches, $ttl);
            
            return $matches;
        } catch (\Exception $e) {
            log_message('error', "Error fetching matches for competition {$competitionId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get today's matches (efficient for dashboard)
     */
    public function getTodayMatches(): array
    {
        $cacheKey = 'football_today_matches';
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            $today = date('Y-m-d');
            $response = $this->makeRequest("/matches?dateFrom={$today}&dateTo={$today}");
            $data = json_decode($response, true);

            if (!isset($data['matches'])) {
                return [];
            }

            $matches = [];
            foreach ($data['matches'] as $match) {
                $matches[] = [
                    'id'         => $match['id'],
                    'homeTeam'   => $match['homeTeam']['name'] ?? 'TBD',
                    'awayTeam'   => $match['awayTeam']['name'] ?? 'TBD',
                    'status'     => $match['status'],
                    'kickoffTime' => $match['utcDate'],
                    'homeScore'  => $match['score']['fullTime']['home'] ?? null,
                    'awayScore'  => $match['score']['fullTime']['away'] ?? null,
                    'competition' => $match['competition']['name'] ?? '',
                ];
            }

            $this->cache->set($cacheKey, $matches, 300); // 5 min cache for today
            return $matches;
        } catch (\Exception $e) {
            log_message('error', 'Error fetching today matches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get standings for a competition
     */
    public function getStandings(int $competitionId): array
    {
        $cacheKey = "football_standings_{$competitionId}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            $response = $this->makeRequest("/competitions/{$competitionId}/standings");
            $data = json_decode($response, true);

            if (!isset($data['standings'])) {
                return [];
            }

            $standings = [];
            foreach ($data['standings'] as $table) {
                $standings[$table['type']] = [];
                foreach ($table['table'] as $team) {
                    $standings[$table['type']][] = [
                        'position'  => $team['position'],
                        'team'      => $team['team']['name'],
                        'played'    => $team['playedGames'],
                        'wins'      => $team['won'],
                        'draws'     => $team['draw'],
                        'losses'    => $team['lost'],
                        'goalsFor'  => $team['goalsFor'],
                        'goalsAgainst' => $team['goalsAgainst'],
                        'goalDiff'  => $team['goalDifference'],
                        'points'    => $team['points'],
                    ];
                }
            }

            $this->cache->set($cacheKey, $standings, self::CACHE_TTL);
            return $standings;
        } catch (\Exception $e) {
            log_message('error', "Error fetching standings for {$competitionId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search matches by team name
     */
    public function searchMatchesByTeam(string $teamName, ?int $limit = 20): array
    {
        $cacheKey = "football_team_search_" . md5(strtolower($teamName));
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            $response = $this->makeRequest("/matches?limit=" . ($limit * 2));
            $data = json_decode($response, true);

            if (!isset($data['matches'])) {
                return [];
            }

            $teamLower = strtolower($teamName);
            $matches = [];
            $count = 0;

            foreach ($data['matches'] as $match) {
                $home = strtolower($match['homeTeam']['name'] ?? '');
                $away = strtolower($match['awayTeam']['name'] ?? '');

                if (strpos($home, $teamLower) !== false || strpos($away, $teamLower) !== false) {
                    $matches[] = [
                        'id'         => $match['id'],
                        'homeTeam'   => $match['homeTeam']['name'],
                        'awayTeam'   => $match['awayTeam']['name'],
                        'status'     => $match['status'],
                        'kickoffTime' => $match['utcDate'],
                        'homeScore'  => $match['score']['fullTime']['home'] ?? null,
                        'awayScore'  => $match['score']['fullTime']['away'] ?? null,
                        'competition' => $match['competition']['name'] ?? '',
                    ];
                    
                    if (++$count >= $limit) break;
                }
            }

            $this->cache->set($cacheKey, $matches, 600); // 10 min cache
            return $matches;
        } catch (\Exception $e) {
            log_message('error', "Error searching matches for team '{$teamName}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Import fixture data into staged_events table
     */
    public function importFixturesToStaging(int $competitionId, ?int $reviewedBy = null): array
    {
        $matches = $this->getMatches($competitionId, 'SCHEDULED', 100);
        
        if (empty($matches)) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'No scheduled matches found'];
        }

        $batchId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stagedEventModel = new \App\Models\StagedEventModel();
        $imported = 0;
        $skipped = 0;

        foreach ($matches as $match) {
            // Check if already exists
            $eventModel = new \App\Models\EventModel();
            $existing = $eventModel->where('home_team', $match['homeTeam'])
                                    ->where('away_team', $match['awayTeam'])
                                    ->first();
            
            if ($existing) {
                $skipped++;
                continue;
            }

            // Create basic odds (will be refined by admin)
            $oddsData = [
                [
                    'name' => 'Ganador del Partido',
                    'type' => '1x2',
                    'odds' => [
                        ['selection' => '1', 'odds' => 2.10],
                        ['selection' => 'X', 'odds' => 3.20],
                        ['selection' => '2', 'odds' => 2.90],
                    ]
                ]
            ];

            $stagedEventModel->insert([
                'batch_id'       => $batchId,
                'sport_key'      => 'football_data',
                'league_name'    => $match['competition'],
                'league_country' => 'Internacional',
                'home_team'      => $match['homeTeam'],
                'away_team'      => $match['awayTeam'],
                'start_time'     => (new \DateTime($match['kickoffTime'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('America/Argentina/Buenos_Aires'))->format('Y-m-d H:i:s'),
                'stage'          => $match['stage'] ?: null,
                'group_name'     => $match['group'] ?: null,
                'venue'          => $match['venue'] ?: null,
                'venue_url'      => !empty($match['venue']) ? 'https://www.google.com/search?tbm=isch&q=' . rawurlencode($match['venue'] . ' estadio fachada') : null,
                'odds_data'      => json_encode($oddsData),
                'status'         => 'pending_review',
                'created_by'     => $reviewedBy ?? 1
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'batch_id' => $batchId
        ];
    }

    /**
     * Make HTTP request with error handling
     */
    private function makeRequest(string $endpoint): string
    {
        $url = self::BASE_URL . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Auth-Token: ' . $this->apiKey,
            'Accept: application/json',
            'User-Agent: CodexSS/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("cURL error: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new \Exception("Football-Data API HTTP {$httpCode}: {$response}");
        }

        return $response;
    }

    /**
     * Check if there are live matches in results
     */
    private function hasLiveMatches(array $matches): bool
    {
        foreach ($matches as $match) {
            if (in_array($match['status'], ['LIVE', 'IN_PLAY'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear cache (useful after imports/updates)
     */
    public function clearCache(): void
    {
        $this->cache->forget('football_competitions_list');
        $this->cache->forget('football_today_matches');
        
        // Clear all match caches
        for ($i = 1; $i <= 2000; $i++) {
            $this->cache->forget("football_matches_{$i}_null_50");
            $this->cache->forget("football_standings_{$i}");
        }
    }
}
