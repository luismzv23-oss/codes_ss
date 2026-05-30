<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        
        // Ensure api_sport_key exists
        if (!$db->fieldExists('api_sport_key', 'leagues')) {
            $forge->addColumn('leagues', [
                'api_sport_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'country',
                ]
            ]);
            
            // Map common leagues
            $db->table('leagues')->where('id', 1)->update(['api_sport_key' => 'soccer_uefa_champs_league']);
            $db->table('leagues')->where('name', 'Copa Mundial')->update(['api_sport_key' => 'soccer_fifa_world_cup']);
            $db->table('leagues')->where('name', 'Copa Libertadores')->update(['api_sport_key' => 'soccer_conmebol_copa_libertadores']);
            $db->table('leagues')->where('name', 'Liga Profesional Argentina')->update(['api_sport_key' => 'soccer_argentina_primera_division']);
        }
        
        // Ensure odds_api_key setting exists
        $hasKey = $db->table('system_settings')->where('key', 'odds_api_key')->countAllResults();
        if ($hasKey === 0) {
            $db->table('system_settings')->insert([
                'key' => 'odds_api_key',
                'value' => '357002f026ea63c327e2af81e6d95dc4'
            ]);
        }
    }

    /**
     * Actualiza los estados de los partidos (pending→live→finished) y ejecuta la liquidación.
     * NO llama a ninguna API externa. Es seguro ejecutar en cada request.
     */
    protected function updateEventStatuses()
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $twoHoursAgo = date('Y-m-d H:i:s', strtotime('-2 hours'));

        // 1. Transicionar de pending -> live
        $db->table('events')
            ->where('status', 'pending')
            ->where('start_time <=', $now)
            ->where('start_time >', $twoHoursAgo)
            ->update(['status' => 'live']);

        // 2. Transicionar de pending o live -> finished
        $db->table('events')
            ->whereIn('status', ['pending', 'live'])
            ->where('start_time <=', $twoHoursAgo)
            ->update(['status' => 'finished', 'settled' => 0]);

        // 3. Ejecutar la liquidación para eventos que YA tienen marcador asignado
        try {
            $settlementService = new \App\Services\SettlementService();
            $settlementService->settleEvents();
        } catch (\Exception $e) {
            log_message('error', 'Error en liquidación automática: ' . $e->getMessage());
        }
    }
}

