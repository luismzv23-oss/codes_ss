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

    /**
     * Congela instantáneamente todos los mercados y cuotas de un evento deportivo por emergencia.
     */
    public function triggerEmergencySuspend(int $eventId, string $reason = 'Suspensión por riesgo o evento crítico'): bool
    {
        $db = \Config\Database::connect();
        
        // 1. Marcar evento como suspendido
        $db->table('events')->where('id', $eventId)->update(['status' => 'suspended']);

        // 2. Suspender todos los mercados del evento
        $db->table('markets')->where('event_id', $eventId)->update(['status' => 'suspended']);

        // 3. Desactivar cuotas activas
        $db->query("UPDATE odds SET active = 0, status = 'suspended' WHERE market_id IN (SELECT id FROM markets WHERE event_id = ?)", [$eventId]);

        // 4. Disparar broadcast WebSocket instantáneo (< 50ms)
        try {
            $payload = json_encode(['event_id' => $eventId, 'reason' => $reason]);
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
                    'content' => $payload,
                    'timeout' => 1,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents('http://localhost:3000/broadcast-suspend', false, $context);
        } catch (\Throwable $e) {}

        log_message('notice', "[EMERGENCY SUSPEND] Evento #{$eventId} suspendido. Razón: {$reason}");
        return true;
    }

    /**
     * Valida el retardo de seguridad (Bet Delay / 3-5 segundos) para apuestas realizadas sobre eventos en vivo.
     */
    public function validateInPlayBetDelay(array $oddIds, bool $isLive = false): array
    {
        if (!$isLive || empty($oddIds)) {
            return ['allowed' => true, 'delay_seconds' => 0];
        }

        $db = \Config\Database::connect();
        $suspendedOdds = $db->table('odds o')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->whereIn('o.id', $oddIds)
            ->groupStart()
                ->where('o.active', 0)
                ->orWhere('o.status', 'suspended')
                ->orWhere('m.status', 'suspended')
                ->orWhere('e.status', 'suspended')
            ->groupEnd()
            ->countAllResults();

        if ($suspendedOdds > 0) {
            return ['allowed' => false, 'message' => 'Un mercado en vivo cambió de estado o fue suspendido. Apuesta rechazada.'];
        }

        return ['allowed' => true, 'delay_seconds' => 3];
    }

    /**
     * Rebalanza automáticamente las cuotas de un mercado según la concentración del dinero apostado (Liability Re-balancing).
     */
    public function rebalanceOddsByHandle(int $marketId): array
    {
        $db = \Config\Database::connect();

        $odds = $db->table('odds')->where('market_id', $marketId)->get()->getResultArray();
        if (count($odds) < 2) {
            return ['rebalanced' => false, 'message' => 'Insuficientes opciones en el mercado.'];
        }

        $totalMarketHandle = 0.0;
        $handles = [];

        foreach ($odds as $odd) {
            $oddHandle = (float) ($db->table('bet_selections sel')
                ->select('COALESCE(SUM(bs.stake), 0) as total', false)
                ->join('bet_slips bs', 'bs.id = sel.bet_slip_id')
                ->where('sel.odd_id', $odd['id'])
                ->where('bs.status', 'pending')
                ->get()
                ->getRowArray()['total'] ?? 0);

            $handles[$odd['id']] = $oddHandle;
            $totalMarketHandle += $oddHandle;
        }

        if ($totalMarketHandle <= 0) {
            return ['rebalanced' => false, 'message' => 'Sin dinero acumulado en el mercado.'];
        }

        $adjustments = [];
        foreach ($odds as $odd) {
            $share = $handles[$odd['id']] / $totalMarketHandle;
            $currentOdds = (float) $odd['odds_decimal'];

            // Si una selección concentra más del 70% del dinero, bajar su cuota 5%
            if ($share >= 0.70) {
                $newOdds = max(1.05, round($currentOdds * 0.95, 2));
                $db->table('odds')->where('id', $odd['id'])->update(['odds_decimal' => $newOdds]);
                $adjustments[] = ['odd_id' => $odd['id'], 'old' => $currentOdds, 'new' => $newOdds, 'reason' => 'heavy_handle'];
            } elseif ($share <= 0.15) {
                // Si concentra menos del 15%, subir su cuota 5% para incentivar flujo
                $newOdds = round($currentOdds * 1.05, 2);
                $db->table('odds')->where('id', $odd['id'])->update(['odds_decimal' => $newOdds]);
                $adjustments[] = ['odd_id' => $odd['id'], 'old' => $currentOdds, 'new' => $newOdds, 'reason' => 'light_handle'];
            }
        }

        return [
            'rebalanced' => !empty($adjustments),
            'total_handle' => $totalMarketHandle,
            'adjustments' => $adjustments
        ];
    }

    private function fail(string $message): array
    {
        return ['valid' => false, 'message' => $message];
    }
}
