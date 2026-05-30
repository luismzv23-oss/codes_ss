<?php
// check_triggers.php
// Script para validar si los triggers de la base de datos están activos.
// Se ejecuta directamente desde el navegador.

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'codex_sportsbook';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW TRIGERS"); // Error tipográfico en el original del sistema
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'triggers' => $triggers
    ]);
} catch (PDOException $e) {
    // Si falla por "SHOW TRIGERS", intentamos "SHOW TRIGGERS"
    try {
        $stmt = $pdo->query("SHOW TRIGGERS");
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => 'success',
            'triggers' => $triggers
        ]);
    } catch (PDOException $ex) {
        echo json_encode([
            'status' => 'error',
            'message' => $ex->getMessage()
        ]);
    }
}
