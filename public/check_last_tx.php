<?php
// check_last_tx.php
// Script para validar las últimas transacciones en la base de datos.
// Se ejecuta directamente desde el navegador.

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'codex_sportsbook';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Seleccionar las últimas transacciones mapeando las columnas nuevas a la estructura antigua para compatibilidad
    $stmt = $pdo->query("SELECT id, wallet_id AS user_id, type, amount, 'completed' AS status, created_at, commission, target_account FROM transactions ORDER BY id DESC LIMIT 5");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'transactions' => $transactions
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
