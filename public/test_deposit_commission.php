<?php
// test_deposit_commission.php
// Script para probar el depósito con comisión del 10% y el registro de la transacción.

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'codex_sportsbook';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Seleccionar un usuario para la prueba
    $stmt = $pdo->query("SELECT id, username, balance FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("No users found in database to perform test.");
    }

    $userId = $user['id'];
    $initialBalance = floatval($user['balance']);

    // Monto bruto a depositar
    $amount = 100.00;
    // Comisión del 10%
    $commission = $amount * 0.10;
    // Monto neto a acreditar
    $netAmount = $amount - $commission;
    // Canal o cuenta destino simulada
    $targetAccount = "codex.sportsbook.mp";

    // Iniciar transacción
    $pdo->beginTransaction();

    // 1. Acreditar balance neto a la billetera (tabla wallets)
    $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, currency, created_at) VALUES (:user_id, :balance, 'ARS', NOW())");
        $stmt->execute([':user_id' => $userId, ':balance' => $netAmount]);
        $walletId = $pdo->lastInsertId();
        $newBalance = $netAmount;
    } else {
        $walletId = $wallet['id'];
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + :net_amount WHERE id = :wallet_id");
        $stmt->execute([
            ':net_amount' => $netAmount,
            ':wallet_id' => $walletId
        ]);
        $newBalance = floatval($wallet['balance']) + $netAmount;
    }

    // 2. Mantener sincronizada la tabla users para compatibilidad con código legado
    $stmt = $pdo->prepare("UPDATE users SET balance = balance + :net_amount WHERE id = :user_id");
    $stmt->execute([
        ':net_amount' => $netAmount,
        ':user_id' => $userId
    ]);

    // 3. Registrar la transacción con el monto bruto (100.00 K) y la comisión en su columna dedicada
    $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, type, amount, balance_after, commission, target_account, created_at) VALUES (:wallet_id, 'deposit', :amount, :balance_after, :commission, :target_account, NOW())");
    $stmt->execute([
        ':wallet_id' => $walletId,
        ':amount' => $amount,
        ':balance_after' => $newBalance,
        ':commission' => $commission,
        ':target_account' => $targetAccount
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Test deposit transaction committed successfully.',
        'details' => [
            'username' => $user['username'],
            'initial_balance' => $initialBalance,
            'amount_charged' => $amount,
            'commission_deducted' => $commission,
            'net_credited' => $netAmount,
            'final_balance' => $newBalance,
            'target_account' => $targetAccount
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
