<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Public Sportsbook
$routes->get('/', 'Sportsbook::index');
$routes->get('apuestas-deportivas', 'Sportsbook::seoIndex');
$routes->get('apuestas-deportivas/en-vivo', 'Sportsbook::liveSchedule');
$routes->get('apuestas-deportivas/reglas-de-apuestas', 'Sportsbook::bettingRules');
$routes->get('apuestas-deportivas/juego-responsable', 'Sportsbook::responsibleGaming');
$routes->get('apuestas-deportivas/terminos-y-condiciones', 'Sportsbook::terms');
$routes->get('apuestas-deportivas/soporte', 'Sportsbook::support');
$routes->get('apuestas-deportivas/(:segment)', 'Sportsbook::seoSport/$1');
$routes->get('apuestas-deportivas/(:segment)/(:segment)/(:segment)', 'Sportsbook::seoLeague/$1/$2/$3');
$routes->get('apuestas-deportivas/(:segment)/(:segment)/(:segment)/(:segment)', 'Sportsbook::seoEvent/$1/$2/$3/$4');
$routes->get('realtime/poll', 'RealTime::poll');
$routes->get('sportsbook/event/(:num)', 'Sportsbook::event/$1');
$routes->post('sportsbook/placeBet', 'Sportsbook::placeBet');
$routes->get('sportsbook/history', 'Sportsbook::history');
$routes->get('sportsbook/ticket/(:num)', 'Sportsbook::ticket/$1');
$routes->get('sportsbook/ticket/(:num)/pdf', 'Sportsbook::ticketPdf/$1');
$routes->get('sportsbook/responsible-limits', 'Sportsbook::responsibleLimits');
$routes->post('sportsbook/responsible-limits', 'Sportsbook::saveResponsibleLimits');
$routes->get('sportsbook/profile', 'Sportsbook::profile');
$routes->post('sportsbook/profile/update', 'Sportsbook::updateProfile');
$routes->post('sportsbook/self-exclusion', 'Sportsbook::selfExclusion');
$routes->post('sportsbook/cashout/(:num)', 'Sportsbook::cashOut/$1');
$routes->post('sportsbook/deposit', 'Sportsbook::deposit');
$routes->get('sportsbook/kyc', 'Sportsbook::kyc');
$routes->post('sportsbook/kyc/submit', 'Sportsbook::submitKyc');
$routes->post('sportsbook/withdrawal-request', 'Sportsbook::withdrawalRequest');
$routes->get('checkout', 'Checkout::index');
$routes->get('checkout/success', 'Checkout::success');
$routes->get('checkout/check-status', 'Checkout::checkStatus');
$routes->post('checkout/process-card', 'Checkout::processCard');
$routes->post('api/sportsbook/calculate-builder', 'Sportsbook::calculateBuilder');
$routes->post('api/payments/webhook', 'Api\PaymentWebhook::handle');
$routes->post('api/payments/simulate-webhook', 'Api\PaymentWebhook::simulate');


// API v1 (Requieren autenticación JWT)
$routes->group('api/v1', ['filter' => 'jwt_auth'], function ($routes) {
    // 2FA Endpoints
    $routes->post('security/2fa/enable', 'Api\SecurityController::enable2FA');
    $routes->post('security/2fa/verify', 'Api\SecurityController::verify2FA');
    $routes->post('security/2fa/disable', 'Api\SecurityController::disable2FA');
    $routes->get('security/2fa/status', 'Api\SecurityController::get2FAStatus');

    // KYC Endpoints
    $routes->post('security/kyc/submit', 'Api\SecurityController::submitKYC');
    $routes->get('security/kyc/status', 'Api\SecurityController::getKYCStatus');
});

// Auth Routes
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/loginAction', 'Auth::loginAction');
$routes->get('auth/register', 'Auth::register');
$routes->post('auth/registerAction', 'Auth::registerAction');
$routes->get('auth/verify/(:any)', 'Auth::verify/$1');
$routes->post('auth/resendVerification', 'Auth::resendVerification');
$routes->get('auth/logout', 'Auth::logout');

// Dashboard Routes (Protected)
$routes->group('dashboard', ['filter' => 'auth:1'], function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('overview', 'Dashboard::overview');
    $routes->get('analytics', 'Dashboard::analytics');
    $routes->get('users', 'Dashboard::users');
    $routes->post('users/toggle-active/(:num)', 'Dashboard::toggleUserActive/$1');
    $routes->post('users/lock/(:num)', 'Dashboard::lockUser/$1');
    $routes->post('users/unlock/(:num)', 'Dashboard::unlockUser/$1');
    $routes->get('events', 'Dashboard::events');
    $routes->get('bets', 'Dashboard::bets');
    $routes->get('bets/export', 'Dashboard::exportBets');
    $routes->get('bets/ticket/(:num)', 'Dashboard::betTicket/$1');
    $routes->get('bets/ticket/(:num)/pdf', 'Dashboard::betTicketPdf/$1');
    $routes->post('bets/void/(:num)', 'Dashboard::voidBet/$1');
    $routes->get('events/league/(:num)', 'Dashboard::leagueEvents/$1');
    $routes->post('events/league/(:num)/create', 'Dashboard::createEvent/$1');
    $routes->post('events/league/(:num)/generate-markets', 'Dashboard::generateLeagueMarkets/$1');
    $routes->post('events/update/(:num)', 'Dashboard::updateEvent/$1');
    $routes->post('events/toggle/(:num)', 'Dashboard::toggleEventStatus/$1');
    $routes->post('events/delete/(:num)', 'Dashboard::deleteEvent/$1');
    $routes->post('leagues/update/(:num)', 'Dashboard::updateLeague/$1');
    $routes->post('leagues/delete/(:num)', 'Dashboard::deleteLeague/$1');
    $routes->post('leagues/toggle/(:num)', 'Dashboard::toggleLeagueStatus/$1');
    $routes->post('leagues/update-order', 'Dashboard::updateLeagueOrder');
    $routes->post('events/finish/(:num)', 'Dashboard::finishEvent/$1');
    $routes->post('events/generate-markets/(:num)', 'Dashboard::generateEventMarkets/$1');
    $routes->post('events/markets/create/(:num)', 'Dashboard::createEventMarket/$1');
    $routes->post('events/suspend-markets/(:num)', 'Dashboard::suspendEventMarkets/$1');
    
    // New soccer import & staging routes
    $routes->get('events/soccer-sports', 'Dashboard::loadSoccerSports');
    $routes->post('events/fetch-soccer', 'Dashboard::fetchSoccerEvents');
    $routes->get('events/football-data-competitions', 'Dashboard::loadFootballDataCompetitions');
    $routes->post('events/fetch-football-data', 'Dashboard::fetchFootballDataEvents');
    $routes->post('events/fetch-serpapi', 'Dashboard::fetchSerpApiEvents');
    $routes->post('events/fetch-espn', 'Dashboard::fetchESPNEvents');
    $routes->get('events/staged', 'Dashboard::stagedEvents');
    $routes->post('events/staged/clear', 'Dashboard::clearStagedEvents');
    $routes->post('events/staged/approve/(:num)', 'Dashboard::approveStagedEvent/$1');
    $routes->post('events/staged/reject/(:num)', 'Dashboard::rejectStagedEvent/$1');
    $routes->post('events/staged/bulk-approve/(:any)', 'Dashboard::bulkApproveBatch/$1');
    
    // Football-Data.org Integration (Real fixtures & results)
    $routes->get('events/football/competitions', 'Dashboard::getFootballCompetitions');
    $routes->post('events/football/search-team', 'Dashboard::searchFootballTeam');
    $routes->post('events/football/import-fixtures', 'Dashboard::importFootballFixtures');
    $routes->post('markets/toggle/(:num)', 'Dashboard::toggleMarketStatus/$1');
    $routes->post('odds/toggle/(:num)', 'Dashboard::toggleOddStatus/$1');
    $routes->post('odds/update/(:num)', 'Dashboard::updateOdd/$1');
    $routes->post('events/worldcup-bracket/(:segment)', 'Dashboard::worldCupBracket/$1');
    $routes->get('run-migrations', 'Dashboard::runMigrations');
    $routes->post('events/fetch-scores', 'Dashboard::fetchScores');
    $routes->get('kyc', 'Dashboard::kyc');
    $routes->post('kyc/approve/(:num)', 'Dashboard::approveKyc/$1');
    $routes->post('kyc/reject/(:num)', 'Dashboard::rejectKyc/$1');
    $routes->get('withdrawals', 'Dashboard::withdrawals');
    $routes->post('withdrawals/approve/(:num)', 'Dashboard::approveWithdrawal/$1');
    $routes->post('withdrawals/reject/(:num)', 'Dashboard::rejectWithdrawal/$1');
    $routes->get('transactions', 'Dashboard::transactions');
    $routes->get('transactions/export', 'Dashboard::exportTransactions');
    $routes->post('transactions/wallet-adjustment', 'Dashboard::walletAdjustment');
    $routes->get('audit', 'Dashboard::audit');
    $routes->get('rankings', 'Dashboard::rankings');
    $routes->get('settings', 'Dashboard::settings');
    $routes->post('settings/update', 'Dashboard::updateSettings');
    $routes->post('settings/clear-cache', 'Dashboard::clearCache');
});

$routes->get('run-migrations-public', function() {
    $migrate = \Config\Services::migrations();
    try {
        if ($migrate->latest()) {
            return 'Exito. Migraciones ejecutadas al 100%.';
        } else {
            return 'No habia migraciones pendientes.';
        }
    } catch (\Throwable $e) {
        return 'Error: ' . $e->getMessage() . '<pre>' . $e->getTraceAsString() . '</pre>';
    }
});
