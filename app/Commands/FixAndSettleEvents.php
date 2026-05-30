<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixAndSettleEvents extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:fix-settle';
    protected $description = 'Asigna marcador 0-0 a eventos finalizados sin marcador y ejecuta la liquidación de apuestas.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        // Asignar marcador 0-0 a todos los eventos finished sin marcador y sin liquidar
        $db->query(
            "UPDATE events SET score_home = 0, score_away = 0 WHERE status = 'finished' AND settled = 0 AND score_home IS NULL"
        );
        $fixed = $db->affectedRows();

        if ($fixed > 0) {
            CLI::write("✅ {$fixed} evento(s) sin marcador corregido(s) con 0-0.", 'green');
        } else {
            CLI::write('No hay eventos finalizados sin marcador pendientes de corrección.', 'yellow');
        }

        // Ejecutar liquidación
        CLI::write('Ejecutando liquidación de apuestas...', 'cyan');
        try {
            $settlementService = new \App\Services\SettlementService();
            $settlementService->settleEvents();
            CLI::write('✅ Liquidación completada.', 'green');
        } catch (\Exception $e) {
            CLI::error('Error en liquidación: ' . $e->getMessage());
        }
    }
}
