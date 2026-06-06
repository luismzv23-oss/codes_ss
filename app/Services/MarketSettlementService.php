<?php

namespace App\Services;

class MarketSettlementService
{
    public function settleMarket(array $event, array $market, array $odds): array
    {
        if ($event['score_home'] === null || $event['score_away'] === null) {
            return [
                'settled' => false,
                'reason' => 'El evento no tiene marcador cargado.',
                'odds' => [],
            ];
        }

        $homeScore = (int) $event['score_home'];
        $awayScore = (int) $event['score_away'];
        $type = strtolower(trim((string) ($market['type'] ?? '')));
        $statuses = [];

        foreach ($odds as $odd) {
            $selection = (string) ($odd['selection'] ?? '');
            $status = match ($type) {
                '1x2', 'h2h', 'moneyline' => $this->settle1x2($selection, $event, $homeScore, $awayScore),
                'totals', 'over_under', 'total_goals', 'total_points' => $this->settleTotals($selection, $homeScore + $awayScore),
                'team_totals', 'team_totals_home' => $this->settleTotals($selection, $homeScore),
                'team_totals_away' => $this->settleTotals($selection, $awayScore),
                'btts', 'both_teams_to_score' => $this->settleBtts($selection, $homeScore, $awayScore),
                'double_chance' => $this->settleDoubleChance($selection, $homeScore, $awayScore),
                'handicap', 'spread' => $this->settleHandicap($selection, $event, $homeScore, $awayScore),
                'correct_score' => $this->settleCorrectScore($selection, $homeScore, $awayScore),
                'qualifies', 'outright', 'outright_champion' => $this->settleWinnerOnly($selection, $event, $homeScore, $awayScore),
                default => 'void',
            };

            $statuses[(int) $odd['id']] = $status;
        }

        return [
            'settled' => true,
            'reason' => 'Mercado liquidado.',
            'odds' => $statuses,
        ];
    }

    private function settle1x2(string $selection, array $event, int $homeScore, int $awayScore): string
    {
        $result = $homeScore <=> $awayScore;
        $normalized = $this->normalize($selection);

        if ($result > 0) {
            return $this->isHomeSelection($normalized, $event) ? 'won' : 'lost';
        }

        if ($result < 0) {
            return $this->isAwaySelection($normalized, $event) ? 'won' : 'lost';
        }

        return $this->isDrawSelection($normalized) ? 'won' : 'lost';
    }

    private function settleTotals(string $selection, int $total): string
    {
        $normalized = $this->normalize($selection);
        $line = $this->extractLine($selection);

        if ($line === null) {
            return 'void';
        }

        if (abs($total - $line) < 0.00001) {
            return 'void';
        }

        $isOver = str_contains($normalized, 'over') || str_contains($normalized, 'mas') || str_contains($normalized, 'mas de');
        $isUnder = str_contains($normalized, 'under') || str_contains($normalized, 'menos') || str_contains($normalized, 'menos de');

        if ($isOver) {
            return $total > $line ? 'won' : 'lost';
        }

        if ($isUnder) {
            return $total < $line ? 'won' : 'lost';
        }

        return 'void';
    }

    private function settleBtts(string $selection, int $homeScore, int $awayScore): string
    {
        $normalized = $this->normalize($selection);
        $bothScored = $homeScore > 0 && $awayScore > 0;

        if ($this->isYesSelection($normalized)) {
            return $bothScored ? 'won' : 'lost';
        }

        if ($this->isNoSelection($normalized)) {
            return $bothScored ? 'lost' : 'won';
        }

        return 'void';
    }

    private function settleDoubleChance(string $selection, int $homeScore, int $awayScore): string
    {
        $normalized = strtoupper(str_replace([' ', '-'], '', $selection));
        $result = $homeScore <=> $awayScore;

        $won = ($result > 0 && str_contains($normalized, '1'))
            || ($result === 0 && str_contains($normalized, 'X'))
            || ($result < 0 && str_contains($normalized, '2'));

        return $won ? 'won' : 'lost';
    }

    private function settleHandicap(string $selection, array $event, int $homeScore, int $awayScore): string
    {
        if (! preg_match('/^(.*?)\s*([+-]?\d+(?:[.,]\d+)?)$/', $selection, $matches)) {
            return 'void';
        }

        $teamPart = trim($matches[1]);
        $line = (float) str_replace(',', '.', $matches[2]);
        $normalizedTeam = $this->normalize($teamPart);

        $isHome = in_array($normalizedTeam, ['1', 'local', 'home'], true)
            || $normalizedTeam === $this->normalize((string) ($event['home_team'] ?? ''));

        $isAway = in_array($normalizedTeam, ['2', 'visitante', 'away'], true)
            || $normalizedTeam === $this->normalize((string) ($event['away_team'] ?? ''));

        if (! $isHome && ! $isAway) {
            return 'void';
        }

        $adjustedHome = $homeScore + ($isHome ? $line : 0);
        $adjustedAway = $awayScore + ($isAway ? $line : 0);

        if (abs($adjustedHome - $adjustedAway) < 0.00001) {
            return 'void';
        }

        return ($isHome && $adjustedHome > $adjustedAway) || ($isAway && $adjustedAway > $adjustedHome)
            ? 'won'
            : 'lost';
    }

    private function settleCorrectScore(string $selection, int $homeScore, int $awayScore): string
    {
        if (! preg_match('/(\d+)\s*[-:]\s*(\d+)/', $selection, $matches)) {
            return 'void';
        }

        return ((int) $matches[1] === $homeScore && (int) $matches[2] === $awayScore) ? 'won' : 'lost';
    }

    private function settleWinnerOnly(string $selection, array $event, int $homeScore, int $awayScore): string
    {
        if ($homeScore === $awayScore) {
            return 'void';
        }

        $normalized = $this->normalize($selection);
        $winnerKey = $homeScore > $awayScore ? 'home_team' : 'away_team';
        $winnerName = $this->normalize((string) ($event[$winnerKey] ?? ''));

        return $normalized === $winnerName ? 'won' : 'lost';
    }

    private function isHomeSelection(string $normalized, array $event): bool
    {
        return in_array($normalized, ['1', 'local', 'home'], true)
            || $normalized === $this->normalize((string) ($event['home_team'] ?? ''));
    }

    private function isAwaySelection(string $normalized, array $event): bool
    {
        return in_array($normalized, ['2', 'visitante', 'away'], true)
            || $normalized === $this->normalize((string) ($event['away_team'] ?? ''));
    }

    private function isDrawSelection(string $normalized): bool
    {
        return in_array($normalized, ['x', 'draw', 'empate'], true);
    }

    private function isYesSelection(string $normalized): bool
    {
        return in_array($normalized, ['si', 'yes', 's'], true) || str_starts_with($normalized, 's');
    }

    private function isNoSelection(string $normalized): bool
    {
        return in_array($normalized, ['no', 'n'], true);
    }

    private function extractLine(string $selection): ?float
    {
        return preg_match('/(-?\d+(?:[.,]\d+)?)/', $selection, $matches)
            ? (float) str_replace(',', '.', $matches[1])
            : null;
    }

    private function extractSignedLine(string $selection): ?float
    {
        return preg_match('/([+-]\d+(?:[.,]\d+)?|-?\d+(?:[.,]\d+)?)/', $selection, $matches)
            ? (float) str_replace(',', '.', $matches[1])
            : null;
    }

    private function normalize(string $value): string
    {
        $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $transliterated !== false ? trim($transliterated) : $value;
    }
}
