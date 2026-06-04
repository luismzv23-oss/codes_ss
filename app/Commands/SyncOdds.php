<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\OddsSyncService;

/**
 * Comando CLI para sincronizar odds desde The Odds API (o Kambi en el futuro).
 *
 * Uso:
 *   php spark odds:sync                                 → Sincroniza fútbol argentino (default)
 *   php spark odds:sync soccer_argentina_primera_division
 *   php spark odds:sync basketball_nba
 *   php spark odds:sync --list                          → Lista deportes disponibles
 */
class SyncOdds extends BaseCommand
{
    protected $group       = 'B2B Integration';
    protected $name        = 'odds:sync';
    protected $description = 'Sincroniza eventos y cuotas desde The Odds API / Kambi hacia la BD local.';
    protected $usage       = 'odds:sync [sport_key] [--list] [--markets=h2h,totals]';
    protected $arguments   = [
        'sport_key' => 'Key del deporte a sincronizar (ej: soccer_argentina_primera_division)',
    ];
    protected $options = [
        '--list'    => 'Lista todos los deportes disponibles del provider',
        '--markets' => 'Mercados a sincronizar separados por coma (default: h2h,totals)',
    ];

    public function run(array $params)
    {
        try {
            $service = new OddsSyncService();
        } catch (\RuntimeException $e) {
            CLI::error($e->getMessage());
            return;
        }

        // Modo lista
        if (CLI::getOption('list') !== null) {
            $this->listSports($service);
            return;
        }

        // Deporte a sincronizar
        $sportKey = $params[0] ?? 'soccer_argentina_primera_division';
        $marketsOpt = CLI::getOption('markets') ?? 'h2h,totals';
        $markets = explode(',', $marketsOpt);

        CLI::write("═══════════════════════════════════════════", 'cyan');
        CLI::write("  Codex_ss — Odds Sync Engine", 'cyan');
        CLI::write("═══════════════════════════════════════════", 'cyan');
        CLI::write("  Deporte: {$sportKey}", 'yellow');
        CLI::write("  Mercados: " . implode(', ', $markets), 'yellow');
        CLI::write("  Provider: " . (getenv('ODDS_PROVIDER') ?: 'theoddsapi'), 'yellow');
        CLI::write("───────────────────────────────────────────", 'dark_gray');

        $result = $service->syncSport($sportKey, $markets);

        CLI::newLine();
        CLI::write("───────────────────────────────────────────", 'dark_gray');
        CLI::write("  Resumen:", 'green');
        CLI::write("    Eventos creados:  {$result['events_created']}", $result['events_created'] > 0 ? 'green' : 'white');
        CLI::write("    Eventos actualizados: {$result['events_updated']}", 'white');
        CLI::write("    Mercados creados: {$result['markets_created']}", $result['markets_created'] > 0 ? 'green' : 'white');
        CLI::write("    Odds actualizados: {$result['odds_updated']}", $result['odds_updated'] > 0 ? 'green' : 'white');
        CLI::write("═══════════════════════════════════════════", 'cyan');

        if (!empty($result['log'])) {
            CLI::newLine();
            foreach ($result['log'] as $line) {
                CLI::write("  " . $line, 'dark_gray');
            }
        }
    }

    private function listSports(OddsSyncService $service): void
    {
        CLI::write("═══════════════════════════════════════════", 'cyan');
        CLI::write("  Deportes Disponibles", 'cyan');
        CLI::write("═══════════════════════════════════════════", 'cyan');

        $sports = $service->listSports();

        if (empty($sports)) {
            CLI::error("No se pudieron obtener los deportes. Verificá tu API key.");
            return;
        }

        // Agrupar por categoría
        $groups = [];
        foreach ($sports as $sport) {
            if (!($sport['active'] ?? false)) continue;
            $group = $sport['group'] ?? 'Other';
            $groups[$group][] = $sport;
        }

        ksort($groups);

        foreach ($groups as $group => $groupSports) {
            CLI::newLine();
            CLI::write("  ▸ {$group}", 'yellow');
            foreach ($groupSports as $s) {
                $key = str_pad($s['key'], 45);
                CLI::write("    {$key} {$s['title']}", 'white');
            }
        }

        CLI::newLine();
        CLI::write("  Uso: php spark odds:sync [sport_key]", 'dark_gray');
    }
}
