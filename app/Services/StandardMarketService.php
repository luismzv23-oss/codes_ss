<?php

namespace App\Services;

class StandardMarketService
{
    public function ensureForEvent(int $eventId): array
    {
        $db = \Config\Database::connect();
        $event = $db->table('events e')
            ->select('e.*, l.name as league_name, s.slug as sport_slug, s.name as sport_name')
            ->join('leagues l', 'l.id = e.league_id')
            ->join('sports s', 's.id = l.sport_id')
            ->where('e.id', $eventId)
            ->get()
            ->getRowArray();

        if (! $event) {
            return ['created' => 0, 'skipped' => 0, 'message' => 'Evento no encontrado.'];
        }

        $sportSlug = strtolower((string) ($event['sport_slug'] ?? ''));
        $isBasketball = str_contains($sportSlug, 'basket') || str_contains($sportSlug, 'baloncesto');
        $definitions = $isBasketball
            ? $this->basketballDefinitions($event)
            : $this->footballDefinitions($event);

        $created = 0;
        $skipped = 0;

        $db->transStart();
        foreach ($definitions as $definition) {
            $exists = $db->table('markets')
                ->where('event_id', $eventId)
                ->where('type', $definition['type'])
                ->countAllResults();

            if ($exists > 0) {
                $skipped++;
                continue;
            }

            $marketId = $this->createMarket($eventId, $definition);
            foreach ($definition['odds'] as $odd) {
                $db->table('odds')->insert([
                    'market_id' => $marketId,
                    'selection' => $odd['selection'],
                    'odds_decimal' => $odd['odds_decimal'],
                    'active' => $event['status'] === 'pending' || $event['status'] === 'live' ? 1 : 0,
                    'status' => 'pending',
                ]);
            }

            $created++;
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['created' => 0, 'skipped' => $skipped, 'message' => 'No se pudieron generar los mercados.'];
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'message' => $created > 0
                ? "Se generaron {$created} mercados nuevos."
                : 'El evento ya tenia los mercados estandar.',
        ];
    }

    private function createMarket(int $eventId, array $definition): int
    {
        $db = \Config\Database::connect();
        $db->table('markets')->insert([
            'event_id' => $eventId,
            'name' => $definition['name'],
            'type' => $definition['type'],
            'status' => 'open',
        ]);

        return (int) $db->insertID();
    }

    private function footballDefinitions(array $event): array
    {
        return [
            [
                'name' => 'Total de Goles',
                'type' => 'totals',
                'odds' => [
                    ['selection' => 'Over 1.5', 'odds_decimal' => 1.38],
                    ['selection' => 'Under 1.5', 'odds_decimal' => 2.90],
                    ['selection' => 'Over 2.5', 'odds_decimal' => 1.86],
                    ['selection' => 'Under 2.5', 'odds_decimal' => 1.94],
                    ['selection' => 'Over 3.5', 'odds_decimal' => 2.75],
                    ['selection' => 'Under 3.5', 'odds_decimal' => 1.42],
                ],
            ],
            [
                'name' => 'Ambos Equipos Anotan',
                'type' => 'btts',
                'odds' => [
                    ['selection' => 'Si', 'odds_decimal' => 1.78],
                    ['selection' => 'No', 'odds_decimal' => 1.98],
                ],
            ],
            [
                'name' => 'Doble Oportunidad',
                'type' => 'double_chance',
                'odds' => [
                    ['selection' => '1X', 'odds_decimal' => 1.34],
                    ['selection' => '12', 'odds_decimal' => 1.28],
                    ['selection' => 'X2', 'odds_decimal' => 1.46],
                ],
            ],
            [
                'name' => 'Handicap Asiatico',
                'type' => 'handicap',
                'odds' => [
                    ['selection' => '1 -1.5', 'odds_decimal' => 3.10],
                    ['selection' => '2 +1.5', 'odds_decimal' => 1.36],
                    ['selection' => '1 +1.5', 'odds_decimal' => 1.30],
                    ['selection' => '2 -1.5', 'odds_decimal' => 3.35],
                ],
            ],
            [
                'name' => 'Resultado Exacto',
                'type' => 'correct_score',
                'odds' => [
                    ['selection' => '0-0', 'odds_decimal' => 8.50],
                    ['selection' => '1-0', 'odds_decimal' => 7.00],
                    ['selection' => '1-1', 'odds_decimal' => 6.20],
                    ['selection' => '2-1', 'odds_decimal' => 8.80],
                    ['selection' => '0-1', 'odds_decimal' => 7.80],
                    ['selection' => '1-2', 'odds_decimal' => 9.40],
                    ['selection' => '2-2', 'odds_decimal' => 12.00],
                ],
            ],
            [
                'name' => 'Total de Goles Local',
                'type' => 'team_totals',
                'odds' => [
                    ['selection' => 'Local Over 0.5', 'odds_decimal' => 1.32],
                    ['selection' => 'Local Under 0.5', 'odds_decimal' => 3.20],
                    ['selection' => 'Local Over 1.5', 'odds_decimal' => 2.05],
                    ['selection' => 'Local Under 1.5', 'odds_decimal' => 1.72],
                ],
            ],
            [
                'name' => 'Total de Goles Visitante',
                'type' => 'team_totals_away',
                'odds' => [
                    ['selection' => 'Visitante Over 0.5', 'odds_decimal' => 1.42],
                    ['selection' => 'Visitante Under 0.5', 'odds_decimal' => 2.75],
                    ['selection' => 'Visitante Over 1.5', 'odds_decimal' => 2.35],
                    ['selection' => 'Visitante Under 1.5', 'odds_decimal' => 1.58],
                ],
            ],
            [
                'name' => 'Props del Partido',
                'type' => 'props',
                'odds' => [
                    ['selection' => 'Habra penal', 'odds_decimal' => 3.10],
                    ['selection' => 'No habra penal', 'odds_decimal' => 1.34],
                    ['selection' => 'Tarjeta roja - Si', 'odds_decimal' => 4.20],
                    ['selection' => 'Tarjeta roja - No', 'odds_decimal' => 1.20],
                ],
            ],
            [
                'name' => 'Futuro de Competicion',
                'type' => 'outright',
                'odds' => [
                    ['selection' => 'Local campeon', 'odds_decimal' => 7.50],
                    ['selection' => 'Visitante campeon', 'odds_decimal' => 9.00],
                ],
            ],
        ];
    }

    private function basketballDefinitions(array $event): array
    {
        return [
            [
                'name' => 'Total de Puntos',
                'type' => 'totals',
                'odds' => [
                    ['selection' => 'Over 218.5', 'odds_decimal' => 1.90],
                    ['selection' => 'Under 218.5', 'odds_decimal' => 1.90],
                ],
            ],
            [
                'name' => 'Handicap',
                'type' => 'handicap',
                'odds' => [
                    ['selection' => '1 -5.5', 'odds_decimal' => 1.92],
                    ['selection' => '2 +5.5', 'odds_decimal' => 1.88],
                    ['selection' => '1 +5.5', 'odds_decimal' => 1.76],
                    ['selection' => '2 -5.5', 'odds_decimal' => 2.05],
                ],
            ],
            [
                'name' => 'Total de Puntos Local',
                'type' => 'team_totals',
                'odds' => [
                    ['selection' => 'Local Over 109.5', 'odds_decimal' => 1.88],
                    ['selection' => 'Local Under 109.5', 'odds_decimal' => 1.92],
                ],
            ],
            [
                'name' => 'Total de Puntos Visitante',
                'type' => 'team_totals_away',
                'odds' => [
                    ['selection' => 'Visitante Over 106.5', 'odds_decimal' => 1.90],
                    ['selection' => 'Visitante Under 106.5', 'odds_decimal' => 1.90],
                ],
            ],
            [
                'name' => 'Props del Partido',
                'type' => 'props',
                'odds' => [
                    ['selection' => 'Tiempo extra - Si', 'odds_decimal' => 11.00],
                    ['selection' => 'Tiempo extra - No', 'odds_decimal' => 1.04],
                    ['selection' => 'Mayor anotador local', 'odds_decimal' => 1.95],
                    ['selection' => 'Mayor anotador visitante', 'odds_decimal' => 1.95],
                ],
            ],
        ];
    }
}
