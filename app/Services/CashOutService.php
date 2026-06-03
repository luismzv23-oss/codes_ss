<?php

namespace App\Services;

class CashOutService
{
    protected $db;
    protected $margin = 0.10; // 10% margen de la casa

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Calcula el valor de Cash Out para un ticket.
     * Retorna null si el Cash Out no está disponible (ej. mercado cerrado, ticket ya resuelto, etc).
     */
    public function calculateCashOutValue($betSlipId)
    {
        $betSlip = $this->db->table('bet_slips')->where('id', $betSlipId)->get()->getRowArray();

        if (!$betSlip) {
            return null;
        }

        // Si el ticket no está pendiente, no se puede hacer cash out
        if ($betSlip['status'] !== 'pending') {
            return null;
        }

        $selections = $this->db->table('bet_selections')->where('bet_slip_id', $betSlipId)->get()->getResultArray();
        
        $currentTotalOdds = 1.0;

        foreach ($selections as $sel) {
            if ($sel['status'] === 'lost') {
                return null; // Si una selección ya perdió, el ticket está perdido (aunque generalment el status del bet_slip sería lost)
            }

            if ($sel['status'] === 'won') {
                $currentTotalOdds *= (float)$sel['odd_at_bet_time'];
                continue;
            }

            if ($sel['status'] === 'void') {
                continue; // Multiplica por 1.0
            }

            // Para las pendientes, buscamos la cuota actual
            $odd = $this->db->table('odds o')
                ->select('o.odds_decimal, o.active, m.status as market_status, e.status as event_status')
                ->join('markets m', 'm.id = o.market_id')
                ->join('events e', 'e.id = m.event_id')
                ->where('o.id', $sel['odd_id'])
                ->get()->getRowArray();

            // Si no encontramos la cuota, o el mercado está cerrado/suspendido, o el evento terminó (pero aún no se liquida), Cash Out NO disponible
            if (!$odd || $odd['active'] == 0 || in_array($odd['market_status'], ['closed', 'suspended']) || $odd['event_status'] === 'finished') {
                return null;
            }

            $currentTotalOdds *= (float)$odd['odds_decimal'];
        }

        // Si currentTotalOdds es 0 por algún motivo extraño
        if ($currentTotalOdds <= 0) {
            return null;
        }

        // Fórmula: (Monto Apostado * Cuota Inicial) / Cuota Actual Total
        $stake = (float)$betSlip['stake'];
        $initialOdds = (float)$betSlip['total_odds'];
        
        $rawCashout = ($stake * $initialOdds) / $currentTotalOdds;
        
        // Aplicar margen de la casa
        $cashOutValue = $rawCashout * (1 - $this->margin);

        // Asegurarnos de que no sea mayor al pago potencial ni menor a 0
        $potentialPayout = (float)$betSlip['potential_payout'];
        if ($cashOutValue > $potentialPayout) {
            $cashOutValue = $potentialPayout;
        }

        if ($cashOutValue < 0) {
            $cashOutValue = 0;
        }

        return round($cashOutValue, 2);
    }

    /**
     * Ejecuta el Cash Out para un ticket.
     */
    public function executeCashOut($betSlipId, $userId)
    {
        $betSlip = $this->db->table('bet_slips')->where('id', $betSlipId)->where('user_id', $userId)->get()->getRowArray();

        if (!$betSlip || $betSlip['status'] !== 'pending') {
            throw new \Exception('El ticket no está disponible para Cash Out.');
        }

        $cashOutValue = $this->calculateCashOutValue($betSlipId);

        if ($cashOutValue === null) {
            throw new \Exception('El Cash Out no está disponible en este momento para este ticket.');
        }

        $this->db->transStart();

        // 1. Actualizar ticket
        $this->db->table('bet_slips')->where('id', $betSlipId)->update([
            'status' => 'cashed_out',
            'cashed_out_amount' => $cashOutValue,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Acreditar a la wallet principal del usuario
        $wallet = $this->db->table('wallets')->where('user_id', $userId)->get()->getRowArray();
        if ($wallet) {
            $newBalance = (float)$wallet['balance'] + $cashOutValue;
            $this->db->table('wallets')->where('id', $wallet['id'])->update([
                'balance' => $newBalance,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 3. Registrar transacción
            $this->db->table('transactions')->insert([
                'user_id' => $userId,
                'type' => 'cash_out',
                'amount' => $cashOutValue,
                'balance_after' => $newBalance,
                'description' => "Cash Out del Ticket #{$betSlipId}",
                'reference_id' => $betSlipId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \Exception('Error al procesar el Cash Out.');
        }

        return $cashOutValue;
    }
}
