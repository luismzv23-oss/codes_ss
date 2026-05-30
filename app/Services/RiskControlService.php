<?php

namespace App\Services;

use App\Models\SystemSettingModel;

class RiskControlService
{
    public function validateBet(int $userId, float $stake, float $potentialPayout, array $oddIds): array
    {
        $settings = (new SystemSettingModel())->getAllSettings();

        $minStake = $this->money($settings['risk_min_stake'] ?? 100);
        $maxStake = $this->money($settings['risk_max_stake'] ?? 100000);
        $maxPayout = $this->money($settings['risk_max_payout'] ?? 1000000);
        $maxUserDailyStake = $this->money($settings['risk_max_user_daily_stake'] ?? 250000);
        $maxEventExposure = $this->money($settings['risk_max_event_exposure'] ?? 500000);
        $maxMarketExposure = $this->money($settings['risk_max_market_exposure'] ?? 300000);

        if ($stake < $minStake) {
            return $this->fail('El importe minimo por apuesta es ' . number_format($minStake, 2) . ' K.');
        }

        if ($stake > $maxStake) {
            return $this->fail('El importe maximo por apuesta es ' . number_format($maxStake, 2) . ' K.');
        }

        if ($potentialPayout > $maxPayout) {
            return $this->fail('La ganancia potencial supera el maximo permitido de ' . number_format($maxPayout, 2) . ' K.');
        }

        $db = \Config\Database::connect();

        $todayStake = (float) ($db->table('bet_slips')
            ->select('COALESCE(SUM(stake), 0) as total', false)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'won', 'lost'])
            ->where('DATE(created_at)', date('Y-m-d'))
            ->get()
            ->getRowArray()['total'] ?? 0);

        if ($todayStake + $stake > $maxUserDailyStake) {
            return $this->fail('El usuario supera el limite diario de apuestas.');
        }

        $targets = $this->selectionTargets($oddIds);
        foreach ($targets['events'] as $eventId => $eventName) {
            $exposure = $this->eventExposure((int) $eventId);
            if ($exposure + $potentialPayout > $maxEventExposure) {
                return $this->fail('La exposicion maxima del evento fue alcanzada: ' . $eventName . '.');
            }
        }

        foreach ($targets['markets'] as $marketId => $marketName) {
            $exposure = $this->marketExposure((int) $marketId);
            if ($exposure + $potentialPayout > $maxMarketExposure) {
                return $this->fail('La exposicion maxima del mercado fue alcanzada: ' . $marketName . '.');
            }
        }

        return ['valid' => true, 'message' => 'Riesgo aprobado.'];
    }

    private function selectionTargets(array $oddIds): array
    {
        if (empty($oddIds)) {
            return ['events' => [], 'markets' => []];
        }

        $rows = \Config\Database::connect()->table('odds o')
            ->select('m.id as market_id, m.name as market_name, e.id as event_id, e.home_team, e.away_team')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->whereIn('o.id', $oddIds)
            ->get()
            ->getResultArray();

        $events = [];
        $markets = [];
        foreach ($rows as $row) {
            $events[(int) $row['event_id']] = $row['home_team'] . ' vs ' . $row['away_team'];
            $markets[(int) $row['market_id']] = $row['market_name'];
        }

        return ['events' => $events, 'markets' => $markets];
    }

    private function eventExposure(int $eventId): float
    {
        return (float) (\Config\Database::connect()->table('bet_slips bs')
            ->select('COALESCE(SUM(bs.potential_payout), 0) as total', false)
            ->join('bet_selections sel', 'sel.bet_slip_id = bs.id')
            ->join('odds o', 'o.id = sel.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->where('m.event_id', $eventId)
            ->where('bs.status', 'pending')
            ->get()
            ->getRowArray()['total'] ?? 0);
    }

    private function marketExposure(int $marketId): float
    {
        return (float) (\Config\Database::connect()->table('bet_slips bs')
            ->select('COALESCE(SUM(bs.potential_payout), 0) as total', false)
            ->join('bet_selections sel', 'sel.bet_slip_id = bs.id')
            ->join('odds o', 'o.id = sel.odd_id')
            ->where('o.market_id', $marketId)
            ->where('bs.status', 'pending')
            ->get()
            ->getRowArray()['total'] ?? 0);
    }

    private function money($value): float
    {
        return max(0.0, (float) $value);
    }

    private function fail(string $message): array
    {
        return ['valid' => false, 'message' => $message];
    }
}
