<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use GuzzleHttp\Client;

class PaymentWebhook extends BaseController
{
    /**
     * Handle incoming real webhooks from Mercado Pago
     */
    public function handle()
    {
        // 1. Get query parameters or JSON body from Mercado Pago
        // MP sends either GET/POST with 'type' and 'data.id', or query parameters
        $type = $this->request->getGet('type') ?? $this->request->getGet('topic');
        $id = $this->request->getGet('id') ?? $this->request->getGet('data.id');

        if (empty($type) || empty($id)) {
            // Also check JSON body if not in GET
            $json = $this->request->getJSON(true);
            if ($json) {
                $type = $json['type'] ?? ($json['topic'] ?? '');
                $id = $json['data']['id'] ?? ($json['id'] ?? '');
            }
        }

        // We only care about payments
        if ($type !== 'payment' || empty($id)) {
            // Return 200/201 to acknowledge receipt
            return $this->response->setStatusCode(200)->setJSON(['status' => 'ignored', 'reason' => 'Not a payment event']);
        }

        // 2. Fetch MP Access Token from settings
        $settingModel = new \App\Models\SystemSettingModel();
        $settings = $settingModel->getAllSettings();
        $mpAccessToken = $settings['mp_access_token'] ?? '';

        if (empty($mpAccessToken)) {
            log_message('error', 'Webhook received but mp_access_token is empty.');
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'reason' => 'Access token not configured']);
        }

        // 3. Fetch payment details from Mercado Pago API
        try {
            $client = new Client();
            $response = $client->get("https://api.mercadopago.com/v1/payments/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . trim($mpAccessToken),
                ]
            ]);

            $paymentData = json_decode($response->getBody(), true);
            $status = $paymentData['status'] ?? '';
            $amount = (float)($paymentData['transaction_amount'] ?? 0);
            $userId = $paymentData['external_reference'] ?? '';
            $preferenceId = $paymentData['preference_id'] ?? '';

            log_message('info', "Webhook Payment Info: ID: {$id}, Status: {$status}, Amount: {$amount}, User: {$userId}, Pref: {$preferenceId}");

            // 4. If approved, credit wallet and record transaction
            if ($status === 'approved' && !empty($userId) && $amount > 0) {
                $walletModel = new \App\Models\WalletModel();
                $txModel = new \App\Models\TransactionModel();

                $db = \Config\Database::connect();
                $db->transStart();

                // Define the reference ID to check for duplicates
                $referenceId = !empty($preferenceId) ? $preferenceId : 'MP-' . $id;

                // Check if transaction was already processed
                $existingTx = $txModel->where('reference_id', $referenceId)->first();
                if ($existingTx) {
                    $db->transComplete();
                    return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'reason' => 'Transaction already processed']);
                }

                // Calculate commission
                $commission = $amount * 0.10;
                $netAmount = $amount - $commission;
                $mpAccount = $settings['mp_qr_account'] ?? 'Mercado Pago Principal';

                // Get or create wallet
                $wallet = $walletModel->where('user_id', $userId)->first();
                if (!$wallet) {
                    $walletModel->insert(['user_id' => $userId, 'balance' => $netAmount, 'currency' => 'ARS']);
                    $wallet = $walletModel->where('user_id', $userId)->first();
                    $newBalance = $netAmount;
                } else {
                    $newBalance = (float)$wallet['balance'] + $netAmount;
                    $walletModel->update($wallet['id'], ['balance' => $newBalance]);
                }

                // Insert completed transaction
                $txModel->insert([
                    'wallet_id'      => $wallet['id'],
                    'type'           => 'deposit',
                    'amount'         => $amount,
                    'balance_after'  => $newBalance,
                    'reference_id'   => $referenceId,
                    'description'    => 'Depósito Mercado Pago',
                    'commission'     => $commission,
                    'target_account' => $mpAccount
                ]);

                $db->transComplete();

                if ($db->transStatus() === false) {
                    return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'reason' => 'Database transaction failed']);
                }

                return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Wallet credited']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in Mercado Pago webhook handler: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'reason' => $e->getMessage()]);
        }

        return $this->response->setStatusCode(200)->setJSON(['status' => 'pending', 'reason' => 'Payment status is not approved yet']);
    }

    /**
     * Local simulation endpoint for testing Sandbox/Local payments without public SSL webhook
     */
    public function simulate()
    {
        $json = $this->request->getJSON(true);
        if (!$json) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid payload']);
        }

        $preferenceId = $json['preference_id'] ?? '';
        $amount = (float)($json['amount'] ?? 0);

        if (empty($preferenceId) || $amount <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing preference_id or amount']);
        }

        $userId = session()->get('user_id');
        if (empty($userId)) {
            // Default to 1 if session is lost for API simulation tests
            $userId = 1;
        }

        $walletModel = new \App\Models\WalletModel();
        $txModel = new \App\Models\TransactionModel();

        $db = \Config\Database::connect();
        $db->transStart();

        // Check if transaction was already processed
        $existingTx = $txModel->where('reference_id', $preferenceId)->first();
        if ($existingTx) {
            $db->transComplete();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Already processed']);
        }

        // Calculate commission
        $commission = $amount * 0.10;
        $netAmount = $amount - $commission;

        // Retrieve settings for mp_qr_account
        $settingModel = new \App\Models\SystemSettingModel();
        $settings = $settingModel->getAllSettings();
        $mpAccount = $settings['mp_qr_account'] ?? 'Mercado Pago Principal';

        // Get or create wallet
        $wallet = $walletModel->where('user_id', $userId)->first();
        if (!$wallet) {
            $walletModel->insert(['user_id' => $userId, 'balance' => $netAmount, 'currency' => 'ARS']);
            $wallet = $walletModel->where('user_id', $userId)->first();
            $newBalance = $netAmount;
        } else {
            $newBalance = (float)$wallet['balance'] + $netAmount;
            $walletModel->update($wallet['id'], ['balance' => $newBalance]);
        }

        // Insert completed transaction
        $txModel->insert([
            'wallet_id'      => $wallet['id'],
            'type'           => 'deposit',
            'amount'         => $amount,
            'balance_after'  => $newBalance,
            'reference_id'   => $preferenceId,
            'description'    => 'Depósito Mercado Pago (Simulación Local)',
            'commission'     => $commission,
            'target_account' => $mpAccount
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Database transaction failed']);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Simulación local acreditada correctamente.',
            'new_balance' => $newBalance
        ]);
    }
}
