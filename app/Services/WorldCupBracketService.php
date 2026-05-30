<?php

namespace App\Services;

class WorldCupBracketService
{
    private const LEAGUE_NAME = 'Copa Mundial de la FIFA 2026';
    private const GROUP_STAGE = 'Fase de grupos';
    private const ROUND_OF_32 = 'Dieciseisavos de final';
    private const ROUND_OF_16 = 'Octavos de final';
    private const QUARTER_FINALS = 'Cuartos de final';
    private const SEMI_FINALS = 'Semifinales';
    private const THIRD_PLACE = 'Tercer puesto';
    private const FINAL = 'Final';

    public function completeRoundOf32IfReady(): array
    {
        $db = \Config\Database::connect();
        $league = $db->table('leagues')->where('name', self::LEAGUE_NAME)->get()->getRowArray();

        if (! $league) {
            return ['completed' => false, 'reason' => 'No existe la liga del Mundial 2026.'];
        }

        $groupMatches = $db->table('events')
            ->where('league_id', $league['id'])
            ->where('stage', self::GROUP_STAGE)
            ->orderBy('match_number', 'ASC')
            ->get()
            ->getResultArray();

        if (count($groupMatches) !== 72) {
            return ['completed' => false, 'reason' => 'La fase de grupos no tiene 72 partidos cargados.'];
        }

        foreach ($groupMatches as $match) {
            if ($match['status'] !== 'finished') {
                return ['completed' => false, 'reason' => 'Todavia hay partidos de grupos sin finalizar.'];
            }

            if ($match['score_home'] === null || $match['score_away'] === null) {
                return ['completed' => false, 'reason' => 'Hay partidos finalizados sin marcador.'];
            }
        }

        $standings = $this->calculateGroupStandings($groupMatches);
        $qualified = $this->qualifiedTeams($standings);
        $roundOf32Matches = $db->table('events')
            ->where('league_id', $league['id'])
            ->where('stage', self::ROUND_OF_32)
            ->orderBy('match_number', 'ASC')
            ->get()
            ->getResultArray();

        if (count($roundOf32Matches) !== 16) {
            return ['completed' => false, 'reason' => 'No hay 16 partidos de dieciseisavos cargados.'];
        }

        if (! $this->hasPlaceholderTeams($roundOf32Matches)) {
            return [
                'completed' => true,
                'reason' => 'Dieciseisavos ya estaban completados.',
                'qualified_count' => count($qualified),
                'pairings' => [],
            ];
        }

        $pairings = $this->pairRoundOf32($qualified);

        $db->transStart();
        foreach ($roundOf32Matches as $index => $match) {
            [$home, $away] = $pairings[$index];

            $db->table('events')->where('id', $match['id'])->update([
                'home_team' => $home['team'],
                'home_flag' => $home['flag'],
                'away_team' => $away['team'],
                'away_flag' => $away['flag'],
                'status' => 'pending',
                'settled' => 0,
                'score_home' => null,
                'score_away' => null,
            ]);

            $this->replaceWinnerMarket((int) $match['id'], $home['team'], $away['team'], (int) $match['match_number']);
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['completed' => false, 'reason' => 'Error de base de datos al completar dieciseisavos.'];
        }

        return [
            'completed' => true,
            'reason' => 'Dieciseisavos completados.',
            'qualified_count' => count($qualified),
            'pairings' => $pairings,
        ];
    }

    public function advanceKnockoutRoundsIfReady(): array
    {
        $results = [];
        $roundOf32 = $this->bracket16avos();
        $results[] = ['stage' => self::ROUND_OF_32, 'result' => $roundOf32];

        if (! $roundOf32['completed']) {
            return [
                'completed' => false,
                'reason' => $roundOf32['reason'],
                'results' => $results,
            ];
        }

        foreach ([
            [self::ROUND_OF_16, 'bracket8vos'],
            [self::QUARTER_FINALS, 'bracket4tos'],
            [self::SEMI_FINALS, 'bracketSemifinales'],
        ] as [$targetStage, $method]) {
            $result = $this->{$method}();
            $results[] = ['stage' => $targetStage, 'result' => $result];

            if (! $result['completed']) {
                return [
                    'completed' => false,
                    'reason' => $result['reason'],
                    'results' => $results,
                ];
            }
        }

        $finals = $this->bracketFinal();
        $results[] = ['stage' => self::FINAL, 'result' => $finals];

        return [
            'completed' => $finals['completed'],
            'reason' => $finals['reason'],
            'results' => $results,
        ];
    }

    public function bracket16avos(): array
    {
        return $this->completeRoundOf32IfReady();
    }

    public function bracket8vos(): array
    {
        return $this->advanceWinnersToNextRound(self::ROUND_OF_32, self::ROUND_OF_16);
    }

    public function bracket4tos(): array
    {
        return $this->advanceWinnersToNextRound(self::ROUND_OF_16, self::QUARTER_FINALS);
    }

    public function bracketSemifinales(): array
    {
        return $this->advanceWinnersToNextRound(self::QUARTER_FINALS, self::SEMI_FINALS);
    }

    public function bracketFinal(): array
    {
        return $this->advanceSemiFinalsToFinals();
    }

    public function champion(): ?array
    {
        $league = $this->worldCupLeague();
        if (! $league) {
            return null;
        }

        $finals = $this->stageMatches((int) $league['id'], self::FINAL);
        if (count($finals) !== 1) {
            return null;
        }

        return $this->winnerFromMatch($finals[0]);
    }

    public function bracketActionStates(): array
    {
        $league = $this->worldCupLeague();
        if (! $league) {
            return [];
        }

        return [
            '16avos' => $this->actionState(self::GROUP_STAGE, self::ROUND_OF_32, true),
            '8vos' => $this->actionState(self::ROUND_OF_32, self::ROUND_OF_16, false),
            '4tos' => $this->actionState(self::ROUND_OF_16, self::QUARTER_FINALS, false),
            'semis' => $this->actionState(self::QUARTER_FINALS, self::SEMI_FINALS, false),
            'final' => $this->finalActionState(),
        ];
    }

    public function calculateGroupStandings(array $matches): array
    {
        $groups = [];

        foreach ($matches as $match) {
            $group = $match['group_name'] ?? 'Grupo ?';

            $this->ensureTeam($groups, $group, $match['home_team'], $match['home_flag'] ?? null);
            $this->ensureTeam($groups, $group, $match['away_team'], $match['away_flag'] ?? null);

            $homeGoals = (int) $match['score_home'];
            $awayGoals = (int) $match['score_away'];

            $this->applyResult($groups[$group][$match['home_team']], $homeGoals, $awayGoals);
            $this->applyResult($groups[$group][$match['away_team']], $awayGoals, $homeGoals);
        }

        foreach ($groups as $group => $teams) {
            usort($teams, static function (array $a, array $b): int {
                return [$b['points'], $b['gd'], $b['gf'], $a['team']]
                    <=> [$a['points'], $a['gd'], $a['gf'], $b['team']];
            });
            $groups[$group] = $teams;
        }

        ksort($groups);

        return $groups;
    }

    private function qualifiedTeams(array $standings): array
    {
        $qualified = [];
        $thirds = [];

        foreach ($standings as $group => $teams) {
            $qualified[] = $this->withSeedMeta($teams[0], $group, 1);
            $qualified[] = $this->withSeedMeta($teams[1], $group, 2);
            $thirds[] = $this->withSeedMeta($teams[2], $group, 3);
        }

        usort($thirds, static function (array $a, array $b): int {
            return [$b['points'], $b['gd'], $b['gf'], $a['team']]
                <=> [$a['points'], $a['gd'], $a['gf'], $b['team']];
        });

        return array_merge($qualified, array_slice($thirds, 0, 8));
    }

    private function pairRoundOf32(array $qualified): array
    {
        usort($qualified, static function (array $a, array $b): int {
            return [$b['points'], $b['gd'], $b['gf'], -$a['group_rank'], $a['group']]
                <=> [$a['points'], $a['gd'], $a['gf'], -$b['group_rank'], $b['group']];
        });

        $pairings = [];
        for ($i = 0; $i < 16; $i++) {
            $pairings[] = [$qualified[$i], $qualified[31 - $i]];
        }

        return $pairings;
    }

    private function advanceWinnersToNextRound(string $sourceStage, string $targetStage): array
    {
        $db = \Config\Database::connect();
        $league = $this->worldCupLeague();

        if (! $league) {
            return ['completed' => false, 'reason' => 'No existe la liga del Mundial 2026.'];
        }

        $sourceMatches = $this->stageMatches((int) $league['id'], $sourceStage);
        $targetMatches = $this->stageMatches((int) $league['id'], $targetStage);

        if (count($sourceMatches) === 0 || count($targetMatches) === 0) {
            return ['completed' => false, 'reason' => "No hay partidos cargados para {$sourceStage} o {$targetStage}."];
        }

        if (count($targetMatches) !== intdiv(count($sourceMatches), 2)) {
            return ['completed' => false, 'reason' => "La cantidad de partidos de {$targetStage} no coincide con {$sourceStage}."];
        }

        if (! $this->hasPlaceholderTeams($targetMatches)) {
            return [
                'completed' => true,
                'reason' => "{$targetStage} ya estaba completado.",
                'pairings' => [],
            ];
        }

        $winners = [];
        foreach ($sourceMatches as $match) {
            $winner = $this->winnerFromMatch($match);
            if (! $winner) {
                return ['completed' => false, 'reason' => "{$sourceStage} todavia no esta completo."];
            }
            $winners[] = $winner;
        }

        $db->transStart();
        foreach ($targetMatches as $index => $match) {
            $home = $winners[$index * 2];
            $away = $winners[$index * 2 + 1];

            $this->updateKnockoutMatch($match, $home, $away);
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['completed' => false, 'reason' => "Error al completar {$targetStage}."];
        }

        return [
            'completed' => true,
            'reason' => "{$targetStage} completado.",
            'pairings' => $this->pairingsFromTarget($targetMatches, $winners),
        ];
    }

    private function advanceSemiFinalsToFinals(): array
    {
        $db = \Config\Database::connect();
        $league = $this->worldCupLeague();

        if (! $league) {
            return ['completed' => false, 'reason' => 'No existe la liga del Mundial 2026.'];
        }

        $semis = $this->stageMatches((int) $league['id'], self::SEMI_FINALS);
        $finals = $this->stageMatches((int) $league['id'], self::FINAL);
        $thirdPlace = $this->stageMatches((int) $league['id'], self::THIRD_PLACE);

        if (count($semis) !== 2 || count($finals) !== 1 || count($thirdPlace) !== 1) {
            return ['completed' => false, 'reason' => 'No estan cargadas semifinales, final o tercer puesto correctamente.'];
        }

        if (! $this->hasPlaceholderTeams($finals) && ! $this->hasPlaceholderTeams($thirdPlace)) {
            return ['completed' => true, 'reason' => 'Final y tercer puesto ya estaban completados.'];
        }

        $winners = [];
        $losers = [];
        foreach ($semis as $match) {
            $winner = $this->winnerFromMatch($match);
            $loser = $this->loserFromMatch($match);

            if (! $winner || ! $loser) {
                return ['completed' => false, 'reason' => 'Semifinales todavia no estan completas.'];
            }

            $winners[] = $winner;
            $losers[] = $loser;
        }

        $db->transStart();
        $this->updateKnockoutMatch($finals[0], $winners[0], $winners[1]);
        $this->updateKnockoutMatch($thirdPlace[0], $losers[0], $losers[1]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['completed' => false, 'reason' => 'Error al completar final y tercer puesto.'];
        }

        return [
            'completed' => true,
            'reason' => 'Final y tercer puesto completados.',
            'final' => [[$winners[0], $winners[1]]],
            'third_place' => [[$losers[0], $losers[1]]],
        ];
    }

    private function worldCupLeague(): ?array
    {
        return \Config\Database::connect()
            ->table('leagues')
            ->where('name', self::LEAGUE_NAME)
            ->get()
            ->getRowArray();
    }

    private function stageMatches(int $leagueId, string $stage): array
    {
        return \Config\Database::connect()
            ->table('events')
            ->where('league_id', $leagueId)
            ->where('stage', $stage)
            ->orderBy('match_number', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function actionState(string $sourceStage, string $targetStage, bool $allowDraws): array
    {
        $league = $this->worldCupLeague();
        if (! $league) {
            return ['enabled' => false, 'completed' => false, 'reason' => 'No existe la liga del Mundial 2026.'];
        }

        $source = $this->stageMatches((int) $league['id'], $sourceStage);
        $target = $this->stageMatches((int) $league['id'], $targetStage);
        $completed = $target !== [] && ! $this->hasPlaceholderTeams($target);

        if ($completed) {
            return ['enabled' => false, 'completed' => true, 'reason' => "{$targetStage} ya esta completado."];
        }

        $ready = $this->stageReady($source, $allowDraws);

        return [
            'enabled' => $ready,
            'completed' => false,
            'reason' => $ready
                ? "Listo para completar {$targetStage}."
                : "{$sourceStage} debe estar finalizada y con todos los marcadores cargados.",
        ];
    }

    private function finalActionState(): array
    {
        $league = $this->worldCupLeague();
        if (! $league) {
            return ['enabled' => false, 'completed' => false, 'reason' => 'No existe la liga del Mundial 2026.'];
        }

        $semis = $this->stageMatches((int) $league['id'], self::SEMI_FINALS);
        $finals = $this->stageMatches((int) $league['id'], self::FINAL);
        $thirdPlace = $this->stageMatches((int) $league['id'], self::THIRD_PLACE);
        $completed = $finals !== [] && $thirdPlace !== []
            && ! $this->hasPlaceholderTeams($finals)
            && ! $this->hasPlaceholderTeams($thirdPlace);

        if ($completed) {
            return ['enabled' => false, 'completed' => true, 'reason' => 'Final y tercer puesto ya estan completados.'];
        }

        $ready = $this->stageReady($semis, false);

        return [
            'enabled' => $ready,
            'completed' => false,
            'reason' => $ready
                ? 'Listo para completar final y tercer puesto.'
                : 'Semifinales debe estar finalizada y con todos los marcadores cargados.',
        ];
    }

    private function stageReady(array $matches, bool $allowDraws): bool
    {
        if ($matches === []) {
            return false;
        }

        foreach ($matches as $match) {
            if ($match['status'] !== 'finished' || $match['score_home'] === null || $match['score_away'] === null) {
                return false;
            }

            if (! $allowDraws && (int) $match['score_home'] === (int) $match['score_away']) {
                return false;
            }
        }

        return true;
    }

    private function winnerFromMatch(array $match): ?array
    {
        if ($match['status'] !== 'finished' || $match['score_home'] === null || $match['score_away'] === null) {
            return null;
        }

        $homeScore = (int) $match['score_home'];
        $awayScore = (int) $match['score_away'];

        if ($homeScore === $awayScore) {
            return null;
        }

        return $homeScore > $awayScore
            ? ['team' => $match['home_team'], 'flag' => $match['home_flag']]
            : ['team' => $match['away_team'], 'flag' => $match['away_flag']];
    }

    private function loserFromMatch(array $match): ?array
    {
        if ($match['status'] !== 'finished' || $match['score_home'] === null || $match['score_away'] === null) {
            return null;
        }

        $homeScore = (int) $match['score_home'];
        $awayScore = (int) $match['score_away'];

        if ($homeScore === $awayScore) {
            return null;
        }

        return $homeScore < $awayScore
            ? ['team' => $match['home_team'], 'flag' => $match['home_flag']]
            : ['team' => $match['away_team'], 'flag' => $match['away_flag']];
    }

    private function updateKnockoutMatch(array $match, array $home, array $away): void
    {
        \Config\Database::connect()
            ->table('events')
            ->where('id', $match['id'])
            ->update([
                'home_team' => $home['team'],
                'home_flag' => $home['flag'],
                'away_team' => $away['team'],
                'away_flag' => $away['flag'],
                'status' => 'pending',
                'settled' => 0,
                'score_home' => null,
                'score_away' => null,
            ]);

        $this->replaceWinnerMarket((int) $match['id'], $home['team'], $away['team'], (int) $match['match_number']);
    }

    private function pairingsFromTarget(array $targetMatches, array $teams): array
    {
        $pairings = [];
        foreach ($targetMatches as $index => $match) {
            $pairings[] = [$teams[$index * 2], $teams[$index * 2 + 1]];
        }

        return $pairings;
    }

    private function hasPlaceholderTeams(array $matches): bool
    {
        foreach ($matches as $match) {
            foreach (['home_team', 'away_team'] as $field) {
                $team = strtolower((string) ($match[$field] ?? ''));
                if (
                    str_contains($team, 'clasificado')
                    || str_contains($team, 'ganador')
                    || str_contains($team, 'perdedor')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ensureTeam(array &$groups, string $group, string $team, ?string $flag): void
    {
        if (! isset($groups[$group])) {
            $groups[$group] = [];
        }

        if (! isset($groups[$group][$team])) {
            $groups[$group][$team] = [
                'team' => $team,
                'flag' => $flag,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0,
                'points' => 0,
            ];
        }
    }

    private function applyResult(array &$team, int $goalsFor, int $goalsAgainst): void
    {
        $team['played']++;
        $team['gf'] += $goalsFor;
        $team['ga'] += $goalsAgainst;
        $team['gd'] = $team['gf'] - $team['ga'];

        if ($goalsFor > $goalsAgainst) {
            $team['won']++;
            $team['points'] += 3;
        } elseif ($goalsFor === $goalsAgainst) {
            $team['drawn']++;
            $team['points']++;
        } else {
            $team['lost']++;
        }
    }

    private function withSeedMeta(array $team, string $group, int $rank): array
    {
        $team['group'] = $group;
        $team['group_rank'] = $rank;

        return $team;
    }

    private function replaceWinnerMarket(int $eventId, string $homeTeam, string $awayTeam, int $matchNumber): void
    {
        $db = \Config\Database::connect();

        $markets = $db->table('markets')->where('event_id', $eventId)->get()->getResultArray();
        foreach ($markets as $market) {
            $db->table('odds')->where('market_id', $market['id'])->update(['active' => 0, 'status' => 'void']);
            $db->table('markets')->where('id', $market['id'])->update(['status' => 'closed']);
        }

        $db->table('markets')->insert([
            'event_id' => $eventId,
            'name' => 'Ganador del Partido',
            'type' => '1x2',
            'status' => 'open',
        ]);
        $marketId = $db->insertID();

        $homeOdds = round(1.65 + (($matchNumber * 17) % 150) / 100, 2);
        $drawOdds = round(2.85 + (($matchNumber * 11) % 115) / 100, 2);
        $awayOdds = round(1.75 + (($matchNumber * 23) % 160) / 100, 2);

        foreach ([[$homeTeam, $homeOdds], ['Empate', $drawOdds], [$awayTeam, $awayOdds]] as [$selection, $odds]) {
            $db->table('odds')->insert([
                'market_id' => $marketId,
                'selection' => $selection,
                'odds_decimal' => $odds,
                'active' => 1,
                'status' => 'pending',
            ]);
        }
    }
}
