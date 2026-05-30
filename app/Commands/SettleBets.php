<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SettlementService;

class SettleBets extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:settle';
    protected $description = 'Resuelve los partidos finalizados y paga los boletos ganadores a los usuarios.';

    public function run(array $params)
    {
        CLI::write('Iniciando Motor de Liquidación (Settlement Engine)...', 'yellow');

        $service = new SettlementService();
        
        try {
            $service->settleEvents();
            CLI::write('✓ Boletos liquidados y pagos emitidos correctamente.', 'green');
        } catch (\Exception $e) {
            CLI::error('Error en el Settlement Engine: ' . $e->getMessage());
            CLI::error($e->getTraceAsString());
        }
    }
}
