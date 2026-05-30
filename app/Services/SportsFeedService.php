<?php

namespace App\Services;

use App\Models\SportModel;
use App\Models\LeagueModel;
use App\Models\EventModel;
use App\Models\MarketModel;
use App\Models\OddModel;

class SportsFeedService
{
    /**
     * Sincroniza datos desde un proveedor externo (Simulado para entorno de desarrollo)
     * En producción, aquí se haría un Guzzle Request a The-Odds-API, Sportradar, etc.
     */
    public function syncLiveFeed()
    {
        // 1. Obtener datos de la API externa
        $apiData = $this->fetchFromExternalAPI();

        $sportModel  = new SportModel();
        $leagueModel = new LeagueModel();
        $eventModel  = new EventModel();
        $marketModel = new MarketModel();
        $oddModel    = new OddModel();

        // 2. Procesar los datos relacionalmente
        foreach ($apiData as $sportData) {
            // Upsert Sport
            $sport = $sportModel->where('slug', $sportData['slug'])->first();
            if (!$sport) {
                $sportId = $sportModel->insert([
                    'name' => $sportData['name'],
                    'slug' => $sportData['slug'],
                    'icon' => $sportData['icon']
                ]);
            } else {
                $sportId = $sport['id'];
            }

            foreach ($sportData['leagues'] as $leagueData) {
                // Upsert League
                $league = $leagueModel->where('name', $leagueData['name'])->where('sport_id', $sportId)->first();
                if (!$league) {
                    $leagueId = $leagueModel->insert([
                        'sport_id' => $sportId,
                        'name'     => $leagueData['name'],
                        'country'  => $leagueData['country']
                    ]);
                } else {
                    $leagueId = $league['id'];
                }

                foreach ($leagueData['events'] as $eventData) {
                    // Upsert Event
                    $event = $eventModel->where('home_team', $eventData['home_team'])
                                        ->where('away_team', $eventData['away_team'])
                                        ->first();
                    
                    if (!$event) {
                        $eventId = $eventModel->insert([
                            'league_id'  => $leagueId,
                            'home_team'  => $eventData['home_team'],
                            'away_team'  => $eventData['away_team'],
                            'start_time' => $eventData['start_time'],
                            'status'     => $eventData['status']
                        ]);
                    } else {
                        $eventId = $event['id'];
                        // Actualizar estado y fecha para que no queden obsoletos en este entorno de pruebas
                        $eventModel->update($eventId, [
                            'status'     => $eventData['status'],
                            'start_time' => $eventData['start_time']
                        ]);
                    }

                    foreach ($eventData['markets'] as $marketData) {
                        // Upsert Market
                        $market = $marketModel->where('event_id', $eventId)
                                              ->where('type', $marketData['type'])
                                              ->first();
                        
                        if (!$market) {
                            $marketId = $marketModel->insert([
                                'event_id' => $eventId,
                                'name'     => $marketData['name'],
                                'type'     => $marketData['type']
                            ]);
                        } else {
                            $marketId = $market['id'];
                        }

                        foreach ($marketData['odds'] as $oddData) {
                            // Upsert Odd
                            $odd = $oddModel->where('market_id', $marketId)
                                            ->where('selection', $oddData['selection'])
                                            ->first();
                            
                            if (!$odd) {
                                $oddModel->insert([
                                    'market_id'    => $marketId,
                                    'selection'    => $oddData['selection'],
                                    'odds_decimal' => $oddData['odds']
                                ]);
                            } else {
                                // Real-Time check: Si la cuota cambió, deberíamos notificar (SSE / Redis)
                                if ((float)$odd['odds_decimal'] !== (float)$oddData['odds']) {
                                    $oddModel->update($odd['id'], ['odds_decimal' => $oddData['odds']]);
                                    
                                    // Guardamos el cambio en caché para que el SSE endpoint lo recoja
                                    $changedOdds = cache('realtime_odds_changes') ?: [];
                                    $changedOdds[] = [
                                        'odd_id'    => $odd['id'],
                                        'event_id'  => $eventId,
                                        'selection' => $oddData['selection'],
                                        'new_odds'  => $oddData['odds'],
                                        'direction' => ((float)$oddData['odds'] > (float)$odd['odds_decimal']) ? 'up' : 'down'
                                    ];
                                    cache()->save('realtime_odds_changes', $changedOdds, 60);
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Generador de datos (Toma cuotas de The-Odds-API si la clave está configurada, de lo contrario simula)
     */
    private function fetchFromExternalAPI()
    {
        $apiKey = env('THE_ODDS_API_KEY') ?: getenv('THE_ODDS_API_KEY');
        
        if (!empty($apiKey)) {
            try {
                $url = "https://api.the-odds-api.com/v4/sports/upcoming/odds/?apiKey=" . urlencode($apiKey) . "&regions=us,eu&markets=h2h,totals,btts&oddsFormat=decimal";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'User-Agent: CodexSS/1.0'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($response === false) {
                    log_message('error', 'The Odds API cURL error: ' . $curlError);
                } elseif ($httpCode !== 200) {
                    log_message('error', 'The Odds API returned HTTP Code ' . $httpCode . ': ' . $response);
                } else {
                    $data = json_decode($response, true);
                    if (is_array($data) && !isset($data['success'])) {
                        // Success! Map the API response
                        return $this->mapTheOddsApiResponse($data);
                    } else {
                        log_message('error', 'The Odds API returned unexpected JSON: ' . $response);
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Exception fetching from The Odds API: ' . $e->getMessage());
            }
        }

        // Fallback to simulated/mock data
        return $this->generateMockData();
    }

    /**
     * Mapea la respuesta cruda de The Odds API a la estructura interna de Codex SS
     */
    private function mapTheOddsApiResponse(array $apiEvents): array
    {
        $mappedSports = [];

        foreach ($apiEvents as $event) {
            $sportKey = $event['sport_key'] ?? '';
            $sportTitle = $event['sport_title'] ?? 'Otros';
            
            if (empty($event['home_team']) || empty($event['away_team'])) {
                continue;
            }

            // Determinar deporte
            $sportName = 'Otros';
            $sportSlug = 'otros';
            $sportIcon = '🎯';

            if (strpos($sportKey, 'soccer') !== false) {
                $sportName = 'Fútbol';
                $sportSlug = 'futbol';
                $sportIcon = '⚽';
            } elseif (strpos($sportKey, 'basketball') !== false) {
                $sportName = 'Baloncesto';
                $sportSlug = 'baloncesto';
                $sportIcon = '🏀';
            } elseif (strpos($sportKey, 'americanfootball') !== false) {
                $sportName = 'Fútbol Americano';
                $sportSlug = 'futbol-americano';
                $sportIcon = '🏈';
            } elseif (strpos($sportKey, 'tennis') !== false) {
                $sportName = 'Tenis';
                $sportSlug = 'tenis';
                $sportIcon = '🎾';
            } elseif (strpos($sportKey, 'baseball') !== false) {
                $sportName = 'Béisbol';
                $sportSlug = 'beisbol';
                $sportIcon = '⚾';
            }

            // Determinar país
            $country = 'Internacional';
            if (strpos($sportKey, '_spain') !== false) {
                $country = 'España';
            } elseif (strpos($sportKey, '_england') !== false) {
                $country = 'Inglaterra';
            } elseif (strpos($sportKey, '_italy') !== false) {
                $country = 'Italia';
            } elseif (strpos($sportKey, '_germany') !== false) {
                $country = 'Alemania';
            } elseif (strpos($sportKey, '_france') !== false) {
                $country = 'Francia';
            } elseif (strpos($sportKey, '_uefa') !== false) {
                $country = 'Europa';
            } elseif (strpos($sportKey, '_usa') !== false || strpos($sportKey, 'nba') !== false || strpos($sportKey, 'nfl') !== false) {
                $country = 'USA';
            }

            $leagueName = $sportTitle;

            if (!isset($mappedSports[$sportSlug])) {
                $mappedSports[$sportSlug] = [
                    'name' => $sportName,
                    'slug' => $sportSlug,
                    'icon' => $sportIcon,
                    'leagues' => []
                ];
            }

            $leagueIndex = -1;
            foreach ($mappedSports[$sportSlug]['leagues'] as $idx => $l) {
                if ($l['name'] === $leagueName) {
                    $leagueIndex = $idx;
                    break;
                }
            }

            if ($leagueIndex === -1) {
                $mappedSports[$sportSlug]['leagues'][] = [
                    'name' => $leagueName,
                    'country' => $country,
                    'events' => []
                ];
                $leagueIndex = count($mappedSports[$sportSlug]['leagues']) - 1;
            }

            $markets = [];
            if (!empty($event['bookmakers']) && is_array($event['bookmakers'])) {
                $selectedBookmaker = null;
                foreach ($event['bookmakers'] as $bm) {
                    if (in_array(strtolower($bm['key']), ['draftkings', 'pinnacle', 'betmgm'])) {
                        $selectedBookmaker = $bm;
                        break;
                    }
                }
                if (!$selectedBookmaker) {
                    $selectedBookmaker = $event['bookmakers'][0];
                }

                if (!empty($selectedBookmaker['markets']) && is_array($selectedBookmaker['markets'])) {
                    foreach ($selectedBookmaker['markets'] as $m) {
                        $mKey = strtolower($m['key']);
                        $mName = '';
                        $mType = '';
                        $oddsList = [];

                        if ($mKey === 'h2h') {
                            $mName = 'Ganador del Partido';
                            $mType = '1x2';
                            foreach ($m['outcomes'] as $out) {
                                $sel = $out['name'];
                                if ($sel === $event['home_team']) {
                                    $sel = '1';
                                } elseif ($sel === $event['away_team']) {
                                    $sel = '2';
                                } elseif (in_array(strtolower($sel), ['draw', 'empate'])) {
                                    $sel = 'X';
                                }
                                $oddsList[] = [
                                    'selection' => $sel,
                                    'odds' => (float)$out['price']
                                ];
                            }
                        } elseif ($mKey === 'totals') {
                            $mName = ($sportSlug === 'baloncesto') ? 'Total de Puntos' : 'Total de Goles';
                            $mType = 'totals';
                            foreach ($m['outcomes'] as $out) {
                                $point = $out['point'] ?? 2.5;
                                $name = strtolower($out['name']);
                                $selPrefix = (strpos($name, 'over') !== false || strpos($name, 'más') !== false || strpos($name, 'mas') !== false) ? 'Over' : 'Under';
                                $oddsList[] = [
                                    'selection' => $selPrefix . ' ' . $point,
                                    'odds' => (float)$out['price']
                                ];
                            }
                        } elseif ($mKey === 'btts') {
                            $mName = 'Ambos Equipos Anotan';
                            $mType = 'btts';
                            foreach ($m['outcomes'] as $out) {
                                $name = strtolower($out['name']);
                                $sel = (in_array($name, ['yes', 'si', 'sí', 'sí'])) ? 'Sí' : 'No';
                                $oddsList[] = [
                                    'selection' => $sel,
                                    'odds' => (float)$out['price']
                                ];
                            }
                        }

                        if (!empty($mType) && !empty($oddsList)) {
                            $markets[] = [
                                'name' => $mName,
                                'type' => $mType,
                                'odds' => $oddsList
                            ];
                        }
                    }
                }
            }

            if (empty($markets)) {
                $markets[] = [
                    'name' => 'Ganador del Partido',
                    'type' => '1x2',
                    'odds' => [
                        ['selection' => '1', 'odds' => 2.10],
                        ['selection' => 'X', 'odds' => 3.20],
                        ['selection' => '2', 'odds' => 2.90],
                    ]
                ];
            }

            $startTime = date('Y-m-d H:i:s', strtotime($event['commence_time'] ?? 'now'));

            $mappedSports[$sportSlug]['leagues'][$leagueIndex]['events'][] = [
                'home_team' => $event['home_team'],
                'away_team' => $event['away_team'],
                'start_time' => $startTime,
                'status' => 'pending',
                'markets' => $markets
            ];
        }

        return array_values($mappedSports);
    }

    /**
     * Generador de datos simulados para fallback
     */
    private function generateMockData()
    {
        $fluctuate = function($base) {
            $change = (rand(0, 100) > 70) ? (rand(-15, 15) / 100) : 0;
            return round(max(1.01, $base + $change), 2);
        };

        return [
            [
                'name' => 'Fútbol', 'slug' => 'futbol', 'icon' => '⚽',
                'leagues' => [
                    [
                        'name' => 'Champions League', 'country' => 'Europa',
                        'events' => [
                            [
                                'home_team' => 'Barcelona', 'away_team' => 'PSG',
                                'start_time' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                                'status' => 'live',
                                'markets' => [
                                    [
                                        'name' => 'Ganador del Partido', 'type' => '1x2',
                                        'odds' => [
                                            ['selection' => '1', 'odds' => $fluctuate(2.15)],
                                            ['selection' => 'X', 'odds' => $fluctuate(3.30)],
                                            ['selection' => '2', 'odds' => $fluctuate(3.10)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Total de Goles', 'type' => 'totals',
                                        'odds' => [
                                            ['selection' => 'Over 2.5', 'odds' => $fluctuate(1.75)],
                                            ['selection' => 'Under 2.5', 'odds' => $fluctuate(2.05)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Ambos Equipos Anotan', 'type' => 'btts',
                                        'odds' => [
                                            ['selection' => 'Sí', 'odds' => $fluctuate(1.60)],
                                            ['selection' => 'No', 'odds' => $fluctuate(2.20)],
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'home_team' => 'Real Madrid', 'away_team' => 'Manchester City',
                                'start_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
                                'status' => 'pending',
                                'markets' => [
                                    [
                                        'name' => 'Ganador del Partido', 'type' => '1x2',
                                        'odds' => [
                                            ['selection' => '1', 'odds' => $fluctuate(2.85)],
                                            ['selection' => 'X', 'odds' => $fluctuate(3.40)],
                                            ['selection' => '2', 'odds' => $fluctuate(2.30)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Total de Goles', 'type' => 'totals',
                                        'odds' => [
                                            ['selection' => 'Over 2.5', 'odds' => $fluctuate(1.85)],
                                            ['selection' => 'Under 2.5', 'odds' => $fluctuate(1.95)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Ambos Equipos Anotan', 'type' => 'btts',
                                        'odds' => [
                                            ['selection' => 'Sí', 'odds' => $fluctuate(1.65)],
                                            ['selection' => 'No', 'odds' => $fluctuate(2.15)],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'name' => 'Premier League', 'country' => 'Inglaterra',
                        'events' => [
                            [
                                'home_team' => 'Arsenal', 'away_team' => 'Liverpool',
                                'start_time' => date('Y-m-d H:i:s', strtotime('+4 hours')),
                                'status' => 'pending',
                                'markets' => [
                                    [
                                        'name' => 'Ganador del Partido', 'type' => '1x2',
                                        'odds' => [
                                            ['selection' => '1', 'odds' => $fluctuate(2.50)],
                                            ['selection' => 'X', 'odds' => $fluctuate(3.20)],
                                            ['selection' => '2', 'odds' => $fluctuate(2.75)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Total de Goles', 'type' => 'totals',
                                        'odds' => [
                                            ['selection' => 'Over 2.5', 'odds' => $fluctuate(1.70)],
                                            ['selection' => 'Under 2.5', 'odds' => $fluctuate(2.10)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Ambos Equipos Anotan', 'type' => 'btts',
                                        'odds' => [
                                            ['selection' => 'Sí', 'odds' => $fluctuate(1.55)],
                                            ['selection' => 'No', 'odds' => $fluctuate(2.30)],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Baloncesto', 'slug' => 'baloncesto', 'icon' => '🏀',
                'leagues' => [
                    [
                        'name' => 'NBA', 'country' => 'USA',
                        'events' => [
                            [
                                'home_team' => 'Boston Celtics', 'away_team' => 'Miami Heat',
                                'start_time' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
                                'status' => 'live',
                                'markets' => [
                                    [
                                        'name' => 'Ganador del Partido', 'type' => '1x2',
                                        'odds' => [
                                            ['selection' => '1', 'odds' => $fluctuate(1.45)],
                                            ['selection' => '2', 'odds' => $fluctuate(2.80)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Total de Puntos', 'type' => 'totals',
                                        'odds' => [
                                            ['selection' => 'Over 218.5', 'odds' => $fluctuate(1.90)],
                                            ['selection' => 'Under 218.5', 'odds' => $fluctuate(1.90)],
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'home_team' => 'Lakers', 'away_team' => 'Warriors',
                                'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
                                'status' => 'pending',
                                'markets' => [
                                    [
                                        'name' => 'Ganador del Partido', 'type' => '1x2',
                                        'odds' => [
                                            ['selection' => '1', 'odds' => $fluctuate(1.85)],
                                            ['selection' => '2', 'odds' => $fluctuate(1.95)],
                                        ]
                                    ],
                                    [
                                        'name' => 'Total de Puntos', 'type' => 'totals',
                                        'odds' => [
                                            ['selection' => 'Over 225.5', 'odds' => $fluctuate(1.90)],
                                            ['selection' => 'Under 225.5', 'odds' => $fluctuate(1.90)],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
