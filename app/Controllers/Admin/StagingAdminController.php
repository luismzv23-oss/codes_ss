<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StagedEventModel;
use App\Models\EventModel;

/**
 * StagingAdminController
 * 
 * Controlador especializado para el buffer de staging, importación y aprobación multifuente de partidos.
 */
class StagingAdminController extends BaseController
{
    public function stagedEvents()
    {
        $stagedModel = new StagedEventModel();
        $stagedEvents = $stagedModel->orderBy('created_at', 'DESC')->findAll();

        return view('dashboard/staged_events', [
            'stagedEvents' => $stagedEvents
        ]);
    }

    public function clearStagedEvents()
    {
        $stagedModel = new StagedEventModel();
        $stagedModel->where('status', 'pending')->delete();

        return redirect()->to('/dashboard/events/staged')->with('success', 'Eventos en staging limpiados.');
    }

    public function approveStagedEvent(int $id)
    {
        $stagedModel = new StagedEventModel();
        $staged = $stagedModel->find($id);

        if (!$staged) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Evento en staging no encontrado.']);
        }

        $eventModel = new EventModel();
        $eventId = $eventModel->insert([
            'league_id'  => $staged['league_id'],
            'home_team'  => $staged['home_team'],
            'away_team'  => $staged['away_team'],
            'start_time' => $staged['start_time'],
            'status'     => 'pending'
        ]);

        $stagedModel->update($id, ['status' => 'approved']);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Evento aprobado e insertado a la oferta pública.',
            'event_id' => $eventId
        ]);
    }

    public function rejectStagedEvent(int $id)
    {
        $stagedModel = new StagedEventModel();
        $stagedModel->update($id, ['status' => 'rejected']);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Evento descartado de staging.'
        ]);
    }

    /**
     * Busca un equipo similar en la liga mediante el algoritmo Levenshtein (Fuzzy Matching).
     */
    public function findSimilarTeam(string $inputName, int $leagueId): ?array
    {
        $db = \Config\Database::connect();
        $existingEvents = $db->table('events')
            ->select('home_team, away_team')
            ->where('league_id', $leagueId)
            ->get()
            ->getResultArray();

        $inputClean = strtolower(trim($inputName));
        $bestMatch = null;
        $smallestDistance = 999;

        foreach ($existingEvents as $event) {
            foreach ([$event['home_team'], $event['away_team']] as $team) {
                $teamClean = strtolower(trim($team));
                $lev = levenshtein($inputClean, $teamClean);
                if ($lev < $smallestDistance && $lev <= 3) {
                    $smallestDistance = $lev;
                    $bestMatch = $team;
                }
            }
        }

        return $bestMatch ? ['similar_name' => $bestMatch, 'distance' => $smallestDistance] : null;
    }
}
