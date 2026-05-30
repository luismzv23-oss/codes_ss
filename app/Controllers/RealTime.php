<?php

namespace App\Controllers;

class RealTime extends BaseController
{
    /**
     * Endpoint de Polling (AJAX) para transmitir cambios de cuotas en vivo.
     * Reemplaza SSE en desarrollo debido a las limitaciones single-thread de 'php spark serve'.
     */
    public function poll()
    {
        $changedOdds = cache('realtime_odds_changes');

        if ($changedOdds) {
            // Limpiamos caché para no reenviar lo mismo
            cache()->delete('realtime_odds_changes');
            return $this->response->setJSON(['status' => 'success', 'data' => $changedOdds]);
        }

        return $this->response->setJSON(['status' => 'empty', 'data' => []]);
    }
}
