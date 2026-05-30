<?php

namespace App\Controllers;

use GuzzleHttp\Client;

class Checkout extends BaseController
{
    public function index()
    {
        // Run migrations programmatically to keep database up-to-date
        try {
            \Config\Services::migrations()->latest();
        } catch (\Throwable $migErr) {
            log_message('error', 'Checkout Programmatic Migration error: ' . $migErr->getMessage());
        }

        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login')->with('error', 'Debes iniciar sesión para depositar.');
        }

        $amount = (float) $this->request->getGet('amount');
        if ($amount <= 0) {
            return redirect()->to('/')->with('error', 'El monto a depositar debe ser mayor a 0.');
        }

        $userId = session()->get('user_id');
        $compliance = (new \App\Services\ComplianceService())->validateDeposit((int) $userId, $amount);
        if (! $compliance['allowed']) {
            return redirect()->to('/sportsbook/responsible-limits')->with('error', $compliance['message']);
        }

        $walletModel = new \App\Models\WalletModel();
        $wallet = $walletModel->where('user_id', $userId)->first();
        $balance = $wallet ? (float)$wallet['balance'] : 0.00;

        $userModel = new \App\Models\UserModel();
        $userObj = $userModel->find($userId);
        $email = $userObj ? $userObj['email'] : 'test@example.com';

        $settings = [];
        try {
            $settingModel = new \App\Models\SystemSettingModel();
            $settings = $settingModel->getAllSettings();
        } catch (\Throwable $t) {
            // Backup migration runner (already covered above, but kept for safety)
            try {
                \Config\Services::migrations()->latest();
                $settingModel = new \App\Models\SystemSettingModel();
                $settings = $settingModel->getAllSettings();
            } catch (\Throwable $migErr) {
                log_message('error', 'Checkout Backup Migration error: ' . $migErr->getMessage());
            }
        }

        $mpAccount = $settings['mp_qr_account'] ?? '';
        $mpAccessToken = $settings['mp_access_token'] ?? '';
        
        $qrBase64 = '';
        $isRealMp = false;
        $mpPreferenceId = '';
        $mpPreferenceUrl = '';

        if (!empty($mpAccessToken) && !empty($mpAccount)) {
            // Determine base URL for redirection
            $baseUrl = $settings['base_url'] ?? base_url('/');
            $baseUrl = rtrim($baseUrl, '/') . '/';

            $isLocal = false;
            $parsed = parse_url($baseUrl);
            if (isset($parsed['host'])) {
                $host = $parsed['host'];
                if ($host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]' || strpos($host, '.') === false) {
                    $isLocal = true;
                }
            }

            // Real Mercado Pago Preference Flow
            try {
                $client = new Client();
                $payload = [
                    'items' => [
                        [
                            'title'       => 'Carga de Saldo - Codex SS',
                            'quantity'    => 1,
                            'unit_price'  => $amount,
                            'currency_id' => 'ARS',
                        ]
                    ],
                    'external_reference' => (string) $userId,
                    'back_urls' => [
                        'success' => $baseUrl . 'checkout/success',
                        'pending' => $baseUrl . 'checkout/success',
                        'failure' => $baseUrl . 'checkout/success',
                    ],
                ];

                if (!$isLocal) {
                    $payload['auto_return'] = 'approved';
                    // Mercado Pago requires a public URL for webhooks
                    $payload['notification_url'] = $baseUrl . 'api/payments/webhook';
                }

                $response = $client->post('https://api.mercadopago.com/checkout/preferences', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . trim($mpAccessToken),
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload
                ]);

                $preference = json_decode($response->getBody(), true);
                if (isset($preference['init_point']) && isset($preference['id'])) {
                    $mpPreferenceId = $preference['id'];
                    $mpPreferenceUrl = $preference['init_point'];
                    $isRealMp = true;
                    
                    // Render QR targeting the init_point
                    $qrBase64 = (new \chillerlan\QRCode\QRCode())->render($mpPreferenceUrl);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Failed to create Mercado Pago preference: ' . $e->getMessage());
                // Fallback to manual QR if API call fails
            }
        }

        // Fallback or Manual Flow if not dynamic
        if (!$isRealMp && !empty($mpAccount)) {
            $qrContent = $mpAccount;
            if (!preg_match('/^https?:\/\//i', $qrContent)) {
                $cleanAccount = trim($qrContent);
                // If it doesn't contain '@' and is not purely numeric/CVU, append alias to mercadopago link
                if (!filter_var($cleanAccount, FILTER_VALIDATE_EMAIL) && !ctype_digit(str_replace([' ', '-', '.'], '', $cleanAccount))) {
                    $qrContent = 'https://link.mercadopago.com.ar/' . $cleanAccount;
                }
            }
            try {
                $qrBase64 = (new \chillerlan\QRCode\QRCode())->render($qrContent);
            } catch (\Throwable $e) {
                log_message('error', 'Error generating fallback QR code: ' . $e->getMessage());
            }
        }

        return view('checkout/index', [
            'amount' => $amount,
            'balance' => $balance,
            'username' => session()->get('username'),
            'settings' => $settings,
            'mpAccount' => $mpAccount,
            'qrBase64' => $qrBase64,
            'isRealMp' => $isRealMp,
            'mpPreferenceId' => $mpPreferenceId,
            'mpPreferenceUrl' => $mpPreferenceUrl,
            'mpPublicKey' => $settings['mp_public_key'] ?? '',
            'mpCardEnabled' => $settings['mp_card_enabled'] ?? '0',
            'email' => $email
        ]);
    }

    /**
     * Polling endpoint to check if payment is completed
     */
    public function checkStatus()
    {
        $reference = $this->request->getGet('reference');
        if (empty($reference)) {
            return $this->response->setJSON(['status' => 'pending']);
        }

        $txModel = new \App\Models\TransactionModel();
        // Look for completed deposit transaction with reference_id
        $tx = $txModel->where('reference_id', $reference)
                      ->where('type', 'deposit')
                      ->first();

        if ($tx) {
            return $this->response->setJSON(['status' => 'completed']);
        }

        return $this->response->setJSON(['status' => 'pending']);
    }

    /**
     * Callback for successful payments on redirect
     */
    public function success()
    {
        $status = $this->request->getGet('status') ?? $this->request->getGet('collection_status');
        $paymentId = $this->request->getGet('payment_id') ?? $this->request->getGet('collection_id');
        $preferenceId = $this->request->getGet('preference_id');

        $isApproved = ($status === 'approved');
        $amount = 0.00;

        if ($isApproved && !empty($paymentId)) {
            // Get credentials
            $settingModel = new \App\Models\SystemSettingModel();
            $settings = $settingModel->getAllSettings();
            $mpAccessToken = $settings['mp_access_token'] ?? '';

            if (!empty($mpAccessToken)) {
                try {
                    $client = new Client();
                    $response = $client->get("https://api.mercadopago.com/v1/payments/{$paymentId}", [
                        'headers' => [
                            'Authorization' => 'Bearer ' . trim($mpAccessToken),
                        ]
                    ]);

                    $paymentData = json_decode($response->getBody(), true);
                    $apiStatus = $paymentData['status'] ?? '';
                    $amount = (float)($paymentData['transaction_amount'] ?? 0);
                    $userId = $paymentData['external_reference'] ?? '';

                    if ($apiStatus === 'approved' && $amount > 0) {
                        $compliance = (new \App\Services\ComplianceService())->validateDeposit((int) $userId, $amount);
                        if (! $compliance['allowed']) {
                            throw new \RuntimeException($compliance['message']);
                        }

                        $walletModel = new \App\Models\WalletModel();
                        $txModel = new \App\Models\TransactionModel();

                        $db = \Config\Database::connect();
                        $db->transStart();

                        // Use preferenceId or MP-paymentId as reference_id
                        $referenceId = !empty($preferenceId) ? $preferenceId : 'MP-' . $paymentId;

                        // Check if already processed
                        $existingTx = $txModel->where('reference_id', $referenceId)->first();
                        if (!$existingTx) {
                            if (empty($userId)) {
                                $userId = session()->get('user_id');
                            }

                            if (!empty($userId)) {
                                $commission = $amount * 0.10;
                                $netAmount = $amount - $commission;
                                $mpAccount = $settings['mp_qr_account'] ?? 'Mercado Pago Principal';

                                $wallet = $walletModel->where('user_id', $userId)->first();
                                if (!$wallet) {
                                    $walletModel->insert(['user_id' => $userId, 'balance' => $netAmount, 'currency' => 'ARS']);
                                    $wallet = $walletModel->where('user_id', $userId)->first();
                                    $newBalance = $netAmount;
                                } else {
                                    $newBalance = (float)$wallet['balance'] + $netAmount;
                                    $walletModel->update($wallet['id'], ['balance' => $newBalance]);
                                }

                                $txModel->insert([
                                    'wallet_id'      => $wallet['id'],
                                    'type'           => 'deposit',
                                    'amount'         => $amount,
                                    'balance_after'  => $newBalance,
                                    'reference_id'   => $referenceId,
                                    'description'    => 'Depósito Mercado Pago (Redirect)',
                                    'commission'     => $commission,
                                    'target_account' => $mpAccount
                                ]);
                            }
                        }

                        $db->transComplete();
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Error verifying payment in Checkout::success: ' . $e->getMessage());
                }
            }
        }

        // Show success view
        return view('checkout/success', [
            'payment_id' => $paymentId,
            'status' => $status,
            'amount' => $amount > 0 ? $amount : (float)$this->request->getGet('amount')
        ]);
    }

    /**
     * Process Card Payment from Mercado Pago Card Brick
     */
    public function processCard()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Debes iniciar sesión para procesar un pago.'
            ])->setStatusCode(401);
        }

        $userId = session()->get('user_id');
        $settingModel = new \App\Models\SystemSettingModel();
        $settings = $settingModel->getAllSettings();
        
        $mpAccessToken = $settings['mp_access_token'] ?? '';
        $mpCardEnabled = $settings['mp_card_enabled'] ?? '0';

        if (empty($mpAccessToken) || $mpCardEnabled !== '1') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'El pago por tarjeta no está habilitado o configurado.'
            ])->setStatusCode(400);
        }

        // Get request payload
        $data = $this->request->getJSON(true);
        if (empty($data)) {
            $data = $this->request->getPost();
        }

        $token = $data['token'] ?? '';
        $paymentMethodId = $data['payment_method_id'] ?? '';
        $installments = (int)($data['installments'] ?? 1);
        $transactionAmount = (float)($data['transaction_amount'] ?? 0);
        $payer = $data['payer'] ?? [];
        $email = $payer['email'] ?? '';

        if (empty($token) || empty($paymentMethodId) || $transactionAmount <= 0 || empty($email)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Datos de pago insuficientes o incorrectos.'
            ])->setStatusCode(400);
        }

        $compliance = (new \App\Services\ComplianceService())->validateDeposit((int) $userId, $transactionAmount);
        if (! $compliance['allowed']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $compliance['message']
            ])->setStatusCode(403);
        }

        try {
            $client = new Client();
            $payload = [
                'token'              => $token,
                'issuer_id'          => isset($data['issuer_id']) ? (string)$data['issuer_id'] : null,
                'payment_method_id'  => $paymentMethodId,
                'transaction_amount' => $transactionAmount,
                'installments'       => $installments,
                'description'        => 'Depósito Tarjeta Mercado Pago - Codex SS',
                'external_reference' => (string)$userId,
                'payer'              => [
                    'email' => $email,
                ]
            ];

            if (empty($payload['issuer_id'])) {
                unset($payload['issuer_id']);
            }

            $response = $client->post('https://api.mercadopago.com/v1/payments', [
                'headers' => [
                    'Authorization' => 'Bearer ' . trim($mpAccessToken),
                    'Content-Type'  => 'application/json',
                    'X-Idempotency-Key' => uniqid('card_', true)
                ],
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300 && isset($result['status'])) {
                $status = $result['status'];
                $paymentId = $result['id'] ?? '';

                if ($status === 'approved') {
                    $walletModel = new \App\Models\WalletModel();
                    $txModel = new \App\Models\TransactionModel();

                    $db = \Config\Database::connect();
                    $db->transStart();

                    $referenceId = 'MP-CARD-' . $paymentId;
                    $existingTx = $txModel->where('reference_id', $referenceId)->first();
                    $newBalance = 0.00;
                    if (!$existingTx) {
                        $commission = $transactionAmount * 0.10;
                        $netAmount = $transactionAmount - $commission;
                        $mpAccount = $settings['mp_qr_account'] ?? 'Mercado Pago Principal';

                        $wallet = $walletModel->where('user_id', $userId)->first();
                        if (!$wallet) {
                            $walletModel->insert(['user_id' => $userId, 'balance' => $netAmount, 'currency' => 'ARS']);
                            $wallet = $walletModel->where('user_id', $userId)->first();
                            $newBalance = $netAmount;
                        } else {
                            $newBalance = (float)$wallet['balance'] + $netAmount;
                            $walletModel->update($wallet['id'], ['balance' => $newBalance]);
                        }

                        $txModel->insert([
                            'wallet_id'      => $wallet['id'],
                            'type'           => 'deposit',
                            'amount'         => $transactionAmount,
                            'balance_after'  => $newBalance,
                            'reference_id'   => $referenceId,
                            'description'    => 'Depósito Tarjeta Mercado Pago (Real)',
                            'commission'     => $commission,
                            'target_account' => $mpAccount
                        ]);
                    } else {
                        $wallet = $walletModel->where('user_id', $userId)->first();
                        $newBalance = $wallet ? (float)$wallet['balance'] : 0.00;
                    }

                    $db->transComplete();

                    return $this->response->setJSON([
                        'status' => 'approved',
                        'message' => '¡Pago aprobado con éxito!',
                        'payment_id' => $paymentId,
                        'new_balance' => $newBalance
                    ]);
                } else if ($status === 'in_process' || $status === 'pending') {
                    return $this->response->setJSON([
                        'status' => $status,
                        'message' => 'El pago está siendo procesado por Mercado Pago.',
                        'payment_id' => $paymentId
                    ]);
                } else {
                    $detail = $result['status_detail'] ?? 'cc_rejected_other_reason';
                    return $this->response->setJSON([
                        'status' => 'rejected',
                        'message' => 'El pago fue rechazado. Motivo: ' . $detail,
                        'payment_id' => $paymentId
                    ]);
                }
            } else {
                $errorMsg = $result['message'] ?? 'Error desconocido de la API de Mercado Pago';
                log_message('error', 'Mercado Pago Card API Error: ' . json_encode($result));
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Error al procesar el pago con la pasarela: ' . $errorMsg
                ])->setStatusCode(400);
            }

        } catch (\Throwable $e) {
            log_message('error', 'Error in Checkout::processCard: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error interno al procesar el pago: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
