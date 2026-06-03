<?php

namespace App\Services;

/**
 * Servicio para obtener marcadores reales de partidos finalizados.
 *
 * Fuente: TheSportsDB (https://www.thesportsdb.com)
 *   - GRATUITA, sin límite de cuota
 *   - No requiere API key (usa key "3" para acceso público)
 *   - Cubre Copa Libertadores, Champions League, Liga Argentina, etc.
 *
 * Endpoint principal: eventspastleague.php → últimos 15 partidos terminados de una liga
 */
class ScoreFetcherService
{
    private const BASE_URL = 'https://www.thesportsdb.com/api/v1/json/3';

    /**
     * Mapping de api_sport_key (de tu BD) → idLeague de TheSportsDB.
     * Se puede extender fácilmente con más ligas.
     */
    private const LEAGUE_MAP = [
        'soccer_conmebol_copa_libertadores'     => '4501',
        'soccer_argentina_primera_division'     => '4406',
        'soccer_uefa_champs_league'             => '4480',
        'soccer_fifa_world_cup'                 => '4429',
        'soccer_brazil_campeonato'              => '4351',
        'soccer_epl'                            => '4328',
        'soccer_spain_la_liga'                  => '4335',
        'soccer_italy_serie_a'                  => '4332',
        'soccer_germany_bundesliga'             => '4331',
        'soccer_france_ligue_one'               => '4334',
        'soccer_conmebol_copa_sudamericana'     => '4502',
        'soccer_mexico_ligamx'                  => '4350',
        'soccer_usa_mls'                        => '4346',
        'soccer_colombia_primera_a'             => '4406', // Ajustar si hay ID diferente
    ];

    /**
     * Busca el marcador real de un evento usando TheSportsDB.
     *
     * @param array  $event       Debe tener 'home_team' y 'away_team'
     * @param string $apiSportKey Clave de deporte/liga (ej. 'soccer_conmebol_copa_libertadores')
     * @return string|null        "1-0" si encontró el marcador, null si no
     */
    public function fetchScoreForEvent(array $event, string $apiSportKey): ?string
    {
        $leagueId = self::LEAGUE_MAP[$apiSportKey] ?? null;

        if ($leagueId) {
            // Endpoint: últimos 15 partidos finalizados de la liga
            $url = self::BASE_URL . "/eventspastleague.php?id={$leagueId}";

            try {
                $client = \Config\Services::curlrequest();
                $response = $client->request('GET', $url, [
                    'http_errors' => false,
                    'timeout' => 8
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody(), true);
                    $matches = $data['events'] ?? [];

                    if (!empty($matches)) {
                        $homeTeamDb = strtolower(trim($event['home_team']));
                        $awayTeamDb = strtolower(trim($event['away_team']));

                        foreach ($matches as $match) {
                            $homeApi = strtolower(trim($match['strHomeTeam'] ?? ''));
                            $awayApi = strtolower(trim($match['strAwayTeam'] ?? ''));

                            if ($match['intHomeScore'] === null || $match['intAwayScore'] === null) {
                                continue;
                            }

                            $normalMatch = $this->isMatch($homeTeamDb, $homeApi) && $this->isMatch($awayTeamDb, $awayApi);
                            $reversedMatch = $this->isMatch($homeTeamDb, $awayApi) && $this->isMatch($awayTeamDb, $homeApi);

                            if ($normalMatch || $reversedMatch) {
                                if ($normalMatch) {
                                    $homeScore = (int) $match['intHomeScore'];
                                    $awayScore = (int) $match['intAwayScore'];
                                } else {
                                    $homeScore = (int) $match['intAwayScore'];
                                    $awayScore = (int) $match['intHomeScore'];
                                }

                                return "{$homeScore}-{$awayScore}";
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'ScoreFetcherService TheSportsDB Exception: ' . $e->getMessage());
            }
        }

        // Si no encontró con TheSportsDB o la liga no estaba mapeada, intentar búsqueda directa por Google (SerpApi)
        $serpApiResult = $this->searchByGoogleSerpApi($event);
        if ($serpApiResult) {
            return $serpApiResult;
        }

        // Fallback a TheSportsDB direct search
        return $this->searchByEventName($event);
    }


    /**
     * Búsqueda del resultado en Google usando SerpApi.
     */
    private function searchByGoogleSerpApi(array $event): ?string
    {
        $serpApiKey = env('SERPAPI_KEY') ?: getenv('SERPAPI_KEY') ?: ($_ENV['SERPAPI_KEY'] ?? $_SERVER['SERPAPI_KEY'] ?? null);
        if (!$serpApiKey) {
            log_message('error', 'ScoreFetcher: No SERPAPI_KEY found in .env');
            return null;
        }

        $query = urlencode(trim($event['home_team']) . ' vs ' . trim($event['away_team']) . ' resultado');
        $url = "https://serpapi.com/search.json?engine=google&q={$query}&api_key={$serpApiKey}";

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->request('GET', $url, [
                'http_errors' => false,
                'timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                $homeTeamDb = strtolower(trim($event['home_team']));
                $awayTeamDb = strtolower(trim($event['away_team']));

                // Strategy 1: game_spotlight
                if (isset($data['sports_results']['game_spotlight']['teams'])) {
                    $teams = $data['sports_results']['game_spotlight']['teams'];
                    
                    if (count($teams) >= 2) {
                        $homeApi = strtolower(trim($teams[0]['name']));
                        $awayApi = strtolower(trim($teams[1]['name']));
                        
                        $score0 = $teams[0]['score'] ?? null;
                        $score1 = $teams[1]['score'] ?? null;

                        if ($score0 !== null && $score1 !== null) {
                            $homeMatch = $this->isMatch($homeTeamDb, $homeApi);
                            $awayMatch = $this->isMatch($awayTeamDb, $awayApi);

                            if ($homeMatch || $awayMatch) {
                                $homeScore = (int)$score0;
                                $awayScore = (int)$score1;
                            } else {
                                $homeScore = (int)$score1;
                                $awayScore = (int)$score0;
                            }

                            log_message('info', "ScoreFetcher: ✓ Marcador (Google SerpApi spotlight): {$homeScore}-{$awayScore}");
                            return "{$homeScore}-{$awayScore}";
                        }
                    }
                }

                // Strategy 2: games list
                if (isset($data['sports_results']['games'])) {
                    foreach ($data['sports_results']['games'] as $game) {
                        if (isset($game['teams']) && count($game['teams']) >= 2) {
                            $homeApi = strtolower(trim($game['teams'][0]['name']));
                            $awayApi = strtolower(trim($game['teams'][1]['name']));
                            
                            $normalMatch = $this->isMatch($homeTeamDb, $homeApi) && $this->isMatch($awayTeamDb, $awayApi);
                            $reversedMatch = $this->isMatch($homeTeamDb, $awayApi) && $this->isMatch($awayTeamDb, $homeApi);
                            
                            if ($normalMatch || $reversedMatch) {
                                $score0 = $game['teams'][0]['score'] ?? null;
                                $score1 = $game['teams'][1]['score'] ?? null;
                                
                                if ($score0 !== null && $score1 !== null) {
                                    if ($normalMatch) {
                                        $homeScore = (int)$score0;
                                        $awayScore = (int)$score1;
                                    } else {
                                        $homeScore = (int)$score1;
                                        $awayScore = (int)$score0;
                                    }

                                    log_message('info', "ScoreFetcher: ✓ Marcador (Google SerpApi games list): {$homeScore}-{$awayScore}");
                                    return "{$homeScore}-{$awayScore}";
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'ScoreFetcher searchByGoogleSerpApi Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Búsqueda directa por nombre del evento (fallback si eventspastleague no lo tiene).
     */
    private function searchByEventName(array $event): ?string
    {
        $searchTerm = urlencode(trim($event['home_team']) . '_vs_' . trim($event['away_team']));
        $url = self::BASE_URL . "/searchevents.php?e={$searchTerm}";

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->request('GET', $url, [
                'http_errors' => false,
                'timeout' => 8
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody(), true);
            $results = $data['event'] ?? [];

            if (empty($results)) {
                // Intentar con orden invertido
                $searchTerm2 = urlencode(trim($event['away_team']) . '_vs_' . trim($event['home_team']));
                $url2 = self::BASE_URL . "/searchevents.php?e={$searchTerm2}";
                $response2 = $client->request('GET', $url2, ['http_errors' => false, 'timeout' => 8]);
                if ($response2->getStatusCode() === 200) {
                    $data2 = json_decode($response2->getBody(), true);
                    $results = $data2['event'] ?? [];
                }
            }

            if (empty($results)) {
                log_message('info', "ScoreFetcher: Búsqueda directa no encontró '{$event['home_team']} vs {$event['away_team']}'");
                return null;
            }

            // Tomar el resultado más reciente que tenga marcador
            foreach ($results as $match) {
                if ($match['intHomeScore'] !== null && $match['intAwayScore'] !== null) {
                    $homeApi = strtolower(trim($match['strHomeTeam'] ?? ''));
                    $homeTeamDb = strtolower(trim($event['home_team']));

                    // Determinar si el home de la BD corresponde al home de la API
                    if ($this->isMatch($homeTeamDb, $homeApi)) {
                        $homeScore = (int) $match['intHomeScore'];
                        $awayScore = (int) $match['intAwayScore'];
                    } else {
                        $homeScore = (int) $match['intAwayScore'];
                        $awayScore = (int) $match['intHomeScore'];
                    }

                    log_message('info', "ScoreFetcher: ✓ Marcador (búsqueda directa): {$homeScore}-{$awayScore}");
                    return "{$homeScore}-{$awayScore}";
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'ScoreFetcher searchByEventName Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Normaliza un nombre quitando palabras genéricas.
     */
    private function normalize(string $name): string
    {
        $name = strtolower(trim($name));
        $noise = ['club', 'fc', 'cf', 'sc', 'de', 'deportes', 'deportivo', 'atletico', 'atlético', 'real', 'cd', 'ca', 'ac'];
        $words = explode(' ', $name);
        $filtered = array_filter($words, fn($w) => !in_array($w, $noise) && strlen($w) > 1);
        return implode(' ', $filtered);
    }

    /**
     * Compara nombres de equipos con múltiples estrategias.
     */
    private function isMatch(string $str1, string $str2): bool
    {
        $s1 = strtolower(trim($str1));
        $s2 = strtolower(trim($str2));

        if ($s1 === $s2) return true;
        if (str_contains($s1, $s2) || str_contains($s2, $s1)) return true;

        similar_text($s1, $s2, $percent);
        if ($percent >= 55) return true;

        $n1 = $this->normalize($s1);
        $n2 = $this->normalize($s2);
        if ($n1 === $n2 && $n1 !== '') return true;
        similar_text($n1, $n2, $pNorm);
        if ($pNorm >= 65) return true;

        $words1 = explode(' ', $s1);
        $words2 = explode(' ', $s2);
        foreach ($words1 as $w1) {
            if (strlen($w1) >= 4) {
                foreach ($words2 as $w2) {
                    if (strlen($w2) >= 4 && $w1 === $w2) return true;
                }
            }
        }

        return false;
    }
}
