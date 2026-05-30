<?php

namespace App\Services;

class CashOutService
{
    public function quote(array $slip): float
    {
        if (($slip['status'] ?? '') !== 'pending') {
            return 0.0;
        }

        $stake = (float) ($slip['stake'] ?? 0);
        $potential = (float) ($slip['potential_payout'] ?? 0);
        if ($stake <= 0 || $potential <= 0) {
            return 0.0;
        }

        $ageMinutes = max(0, (time() - strtotime($slip['created_at'] ?? 'now')) / 60);
        $timeFactor = max(0.62, 0.92 - min(0.20, $ageMinutes / 720));
        $base = min($potential * 0.68, max($stake * 0.45, $stake * $timeFactor));

        return round(max(0, $base), 2);
    }

    public function canCashOut(array $slip): array
    {
        if (($slip['status'] ?? '') !== 'pending') {
            return ['allowed' => false, 'message' => 'Solo se puede hacer cash-out de tickets pendientes.'];
        }

        $db = \Config\Database::connect();
        $restricted = $db->table('bet_selections bs')
            ->select('e.status, e.start_time')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->where('bs.bet_slip_id', (int) $slip['id'])
            ->groupStart()
                ->where('e.status', 'finished')
                ->orWhere('e.status', 'cancelled')
            ->groupEnd()
            ->countAllResults();

        if ($restricted > 0) {
            return ['allowed' => false, 'message' => 'El ticket ya contiene eventos cerrados o cancelados.'];
        }

        return ['allowed' => true, 'message' => 'OK'];
    }
}
