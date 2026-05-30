<?php

namespace App\Services;

use App\Models\BetSelectionModel;
use App\Models\BetSlipModel;
use App\Models\EventModel;
use App\Models\MarketModel;
use App\Models\OddModel;
use App\Models\TransactionModel;
use App\Models\WalletModel;

class SettlementService
{
    public function settleEvents(): void
    {
        $eventModel = new EventModel();
        $events = $eventModel->where('status', 'finished')
            ->where('settled', 0)
            ->findAll();

        foreach ($events as $event) {
            $this->settleEvent($event);
        }

        $bracketResult = (new WorldCupBracketService())->advanceKnockoutRoundsIfReady();
        log_message(
            'info',
            $bracketResult['completed']
                ? 'Mundial 2026: bracket actualizado automaticamente.'
                : 'Mundial 2026: bracket pendiente - ' . $bracketResult['reason']
        );
    }

    public function settleEvent(array $event): bool
    {
        if ($event['score_home'] === null || $event['score_away'] === null) {
            log_message('info', 'Evento ID ' . $event['id'] . ' finalizado sin marcador; liquidacion omitida.');
            return false;
        }

        $db = \Config\Database::connect();
        $marketModel = new MarketModel();
        $oddModel = new OddModel();
        $betSelectionModel = new BetSelectionModel();
        $eventModel = new EventModel();
        $resolver = new MarketSettlementService();

        $db->transStart();

        $markets = $marketModel->where('event_id', $event['id'])->findAll();
        $oddStatuses = [];

        foreach ($markets as $market) {
            $odds = $oddModel->where('market_id', $market['id'])->findAll();
            if (empty($odds)) {
                $marketModel->update($market['id'], ['status' => 'closed']);
                continue;
            }

            $result = $resolver->settleMarket($event, $market, $odds);
            if (! $result['settled']) {
                continue;
            }

            foreach ($result['odds'] as $oddId => $status) {
                $oddStatuses[(int) $oddId] = $status;
                $oddModel->update((int) $oddId, [
                    'status' => $status,
                    'active' => 0,
                ]);
            }

            $marketModel->update($market['id'], ['status' => 'closed']);
        }

        if (! empty($oddStatuses)) {
            $selections = $betSelectionModel
                ->whereIn('odd_id', array_keys($oddStatuses))
                ->where('status', 'pending')
                ->findAll();

            $affectedSlipIds = [];
            foreach ($selections as $selection) {
                $status = $oddStatuses[(int) $selection['odd_id']] ?? 'void';
                $betSelectionModel->update($selection['id'], ['status' => $status]);
                $affectedSlipIds[(int) $selection['bet_slip_id']] = true;
            }

            foreach (array_keys($affectedSlipIds) as $slipId) {
                $this->settleSlip((int) $slipId);
            }
        }

        $eventModel->update($event['id'], ['settled' => 1]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Error liquidando el evento ID ' . $event['id']);
            return false;
        }

        log_message('info', 'Evento ID ' . $event['id'] . ' liquidado con marcador real.');
        return true;
    }

    private function settleSlip(int $slipId): void
    {
        $betSlipModel = new BetSlipModel();
        $betSelectionModel = new BetSelectionModel();
        $walletModel = new WalletModel();
        $txModel = new TransactionModel();

        $slip = $betSlipModel->find($slipId);
        if (! $slip || $slip['status'] !== 'pending') {
            return;
        }

        $selections = $betSelectionModel->where('bet_slip_id', $slipId)->findAll();
        if (empty($selections)) {
            return;
        }

        $hasPending = false;
        $hasLost = false;
        $hasWon = false;
        $adjustedOdds = 1.0;

        foreach ($selections as $selection) {
            if ($selection['status'] === 'pending') {
                $hasPending = true;
                break;
            }

            if ($selection['status'] === 'lost') {
                $hasLost = true;
                break;
            }

            if ($selection['status'] === 'won') {
                $hasWon = true;
                $adjustedOdds *= (float) $selection['odd_at_bet_time'];
            }
        }

        if ($hasPending) {
            return;
        }

        if ($hasLost) {
            $betSlipModel->update($slipId, ['status' => 'lost']);
            return;
        }

        $wallet = $walletModel->where('user_id', $slip['user_id'])->first();
        if (! $wallet) {
            return;
        }

        if (! $hasWon) {
            $refund = (float) $slip['stake'];
            $newBalance = (float) $wallet['balance'] + $refund;
            $walletModel->update($wallet['id'], ['balance' => $newBalance]);
            $betSlipModel->update($slipId, [
                'status' => 'void',
                'total_odds' => 1.000,
                'potential_payout' => $refund,
            ]);
            $txModel->insert([
                'wallet_id' => $wallet['id'],
                'type' => 'bet_refunded',
                'amount' => $refund,
                'balance_after' => $newBalance,
                'reference_id' => $slipId,
                'description' => 'Reintegro de Apuesta Anulada Ticket #' . $slipId,
            ]);
            return;
        }

        $payout = round((float) $slip['stake'] * $adjustedOdds, 2);
        $newBalance = (float) $wallet['balance'] + $payout;

        $walletModel->update($wallet['id'], ['balance' => $newBalance]);
        $betSlipModel->update($slipId, [
            'status' => 'won',
            'total_odds' => round($adjustedOdds, 3),
            'potential_payout' => $payout,
        ]);
        $txModel->insert([
            'wallet_id' => $wallet['id'],
            'type' => 'bet_won',
            'amount' => $payout,
            'balance_after' => $newBalance,
            'reference_id' => $slipId,
            'description' => 'Pago de Apuesta Ganada Ticket #' . $slipId,
        ]);
    }
}
