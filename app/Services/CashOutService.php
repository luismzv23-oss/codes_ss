<?php

namespace App\Services;

use App\Models\BetSlipModel;
use App\Models\BetSelectionModel;
use App\Models\OddModel;
use App\Models\WalletModel;
use App\Models\TransactionModel;

/**
 * CashOutService
 * 
 * Servicio avanzado para el cálculo y procesamiento de Cashout Total y Parcial.
 */
class CashOutService
{
    /**
     * Calcula el valor actual de Cashout disponible para un boleto de apuestas.
     *
     * @param int $betSlipId ID del boleto de apuestas
     * @return array Resumen del cálculo {available: bool, current_value: float, initial_stake: float, message: string}
     */
    public function calculateCashOutValue(int $betSlipId): array
    {
        $betSlipModel = new BetSlipModel();
        $betSlip = $betSlipModel->find($betSlipId);

        if (!$betSlip || $betSlip['status'] !== 'pending') {
            return [
                'available' => false,
                'current_value' => 0.00,
                'initial_stake' => 0.00,
                'message' => 'El boleto no está disponible para Cashout.'
            ];
        }

        $db = \Config\Database::connect();
        $selections = $db->table('bet_selections bs')
            ->select('bs.*, o.odds_decimal as current_odds, o.active, o.status as odd_status, m.status as market_status')
            ->join('odds o', 'o.id = bs.odd_id')
            ->join('markets m', 'm.id = o.market_id')
            ->where('bs.bet_slip_id', $betSlipId)
            ->get()
            ->getResultArray();

        if (empty($selections)) {
            return [
                'available' => false,
                'current_value' => 0.00,
                'initial_stake' => (float) $betSlip['stake'],
                'message' => 'Sin selecciones válidas.'
            ];
        }

        $currentCombinedOdds = 1.0;
        $initialCombinedOdds = (float) $betSlip['total_odds'];

        foreach ($selections as $sel) {
            // Si alguna selección fue perdida, el boleto no tiene Cashout
            if ($sel['status'] === 'lost') {
                return [
                    'available' => false,
                    'current_value' => 0.00,
                    'initial_stake' => (float) $betSlip['stake'],
                    'message' => 'Una selección resultó perdida.'
                ];
            }

            // Si el mercado o la cuota está suspendido, congelar Cashout
            if ((int) $sel['active'] !== 1 || $sel['market_status'] !== 'open') {
                return [
                    'available' => false,
                    'current_value' => 0.00,
                    'initial_stake' => (float) $betSlip['stake'],
                    'message' => 'Mercado suspendido temporalmente.'
                ];
            }

            if ($sel['status'] === 'pending') {
                $currentCombinedOdds *= (float) $sel['current_odds'];
            }
        }

        // Fórmula estándar de Cashout: Stake * (Cuota Inicial / Cuota Actual) * Margen de la casa (0.90)
        $fairValue = ((float) $betSlip['stake']) * ($initialCombinedOdds / max($currentCombinedOdds, 1.01));
        $cashOutValue = round($fairValue * 0.90, 2);

        return [
            'available' => $cashOutValue > 0,
            'current_value' => $cashOutValue,
            'initial_stake' => (float) $betSlip['stake'],
            'total_odds' => (float) $betSlip['total_odds'],
            'message' => 'Cashout disponible.'
        ];
    }

    /**
     * Procesa un Cashout parcial o total sobre un boleto activo.
     *
     * @param int $betSlipId ID del boleto
     * @param int $userId ID del usuario propietario
     * @param float $percentage Porcentaje de cobro (10.0 a 100.0)
     * @return array Resultado de la transacción
     */
    public function processPartialCashOut(int $betSlipId, int $userId, float $percentage): array
    {
        if ($percentage < 10.0 || $percentage > 100.0) {
            return ['status' => 'error', 'message' => 'Porcentaje de Cashout inválido (10% - 100%).'];
        }

        $calculation = $this->calculateCashOutValue($betSlipId);
        if (!$calculation['available'] || $calculation['current_value'] <= 0) {
            return ['status' => 'error', 'message' => $calculation['message']];
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Obtener boleto con bloqueo pesimista
        $betSlip = $db->query("SELECT * FROM bet_slips WHERE id = ? AND user_id = ? AND status = 'pending' FOR UPDATE", [$betSlipId, $userId])->getRowArray();
        if (!$betSlip) {
            $db->transRollback();
            return ['status' => 'error', 'message' => 'El boleto ya no está disponible o cambió su estado.'];
        }

        $fullCashOutValue = $calculation['current_value'];
        $payoutAmount = round($fullCashOutValue * ($percentage / 100.0), 2);
        $stakeReduction = round(((float) $betSlip['stake']) * ($percentage / 100.0), 2);

        // Bloqueo pesimista sobre billetera
        $wallet = $db->query("SELECT * FROM wallets WHERE user_id = ? FOR UPDATE", [$userId])->getRowArray();
        if (!$wallet) {
            $db->transRollback();
            return ['status' => 'error', 'message' => 'Billetera no encontrada.'];
        }

        $newBalance = (float) $wallet['balance'] + $payoutAmount;

        // 1. Acreditar saldo en billetera
        $db->query("UPDATE wallets SET balance = ? WHERE id = ?", [$newBalance, $wallet['id']]);

        // 2. Actualizar ticket
        $newStake = (float) $betSlip['stake'] - $stakeReduction;
        $newPotentialPayout = $newStake * ((float) $betSlip['total_odds']);
        
        $newStatus = ($percentage >= 99.0 || $newStake <= 0.01) ? 'cashed_out' : 'pending';

        $db->query(
            "UPDATE bet_slips SET stake = ?, potential_payout = ?, status = ?, cashed_out_amount = COALESCE(cashed_out_amount, 0) + ? WHERE id = ?",
            [$newStake, $newPotentialPayout, $newStatus, $payoutAmount, $betSlipId]
        );

        // 3. Registrar transacción contable
        $txModel = new TransactionModel();
        $txModel->insert([
            'wallet_id' => $wallet['id'],
            'type' => 'cashout',
            'amount' => $payoutAmount,
            'balance_after' => $newBalance,
            'reference_id' => $betSlipId,
            'description' => sprintf('Cashout %s%% Ticket #%d', ($percentage >= 99.0 ? 'Total' : $percentage), $betSlipId)
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['status' => 'error', 'message' => 'Error al procesar el Cashout.'];
        }

        return [
            'status' => 'success',
            'message' => sprintf('¡Cashout del %s%% procesado con éxito! Se acreditó $%s ARS en tu billetera.', $percentage, number_format($payoutAmount, 2)),
            'cashed_out_amount' => $payoutAmount,
            'remaining_stake' => $newStake,
            'new_balance' => $newBalance,
            'ticket_status' => $newStatus
        ];
    }
}
