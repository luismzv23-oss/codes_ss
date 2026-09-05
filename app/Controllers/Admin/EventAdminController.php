<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\LeagueModel;
use App\Models\MarketModel;
use App\Models\OddModel;

/**
 * EventAdminController
 * 
 * Controlador especializado para la gestión y administración de deportes, ligas, partidos y mercados.
 */
class EventAdminController extends BaseController
{
    public function events()
    {
        $db = \Config\Database::connect();
        $events = $db->table('events e')
            ->select('e.*, l.name as league_name, s.name as sport_name')
            ->join('leagues l', 'l.id = e.league_id')
            ->join('sports s', 's.id = l.sport_id')
            ->orderBy('e.start_time', 'DESC')
            ->get()
            ->getResultArray();

        return view('dashboard/events', ['events' => $events]);
    }

    public function leagueEvents(int $leagueId)
    {
        $eventModel = new EventModel();
        $leagueModel = new LeagueModel();

        $league = $leagueModel->find($leagueId);
        if (!$league) {
            return redirect()->to('/dashboard/events')->with('error', 'Liga no encontrada.');
        }

        $events = $eventModel->where('league_id', $leagueId)->orderBy('start_time', 'ASC')->findAll();
        return view('dashboard/league_events', [
            'league' => $league,
            'events' => $events
        ]);
    }

    public function toggleEventStatus(int $id)
    {
        $eventModel = new EventModel();
        $event = $eventModel->find($id);

        if (!$event) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento no encontrado.']);
        }

        $newStatus = ($event['status'] === 'suspended') ? 'pending' : 'suspended';
        $eventModel->update($id, ['status' => $newStatus]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Estado del partido actualizado.',
            'new_status' => $newStatus
        ]);
    }
}
