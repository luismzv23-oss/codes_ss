<?php

namespace App\Services;

use App\Models\OddModel;

class BetBuilderService
{
    /**
     * Calcula la cuota combinada aplicando correlaciones probabilísticas para selecciones del mismo evento.
     *
     * @param array $oddIds Lista de IDs de cuotas (odds).
     * @return array Array con ['valid' => bool, 'odds' => float, 'message' => string, 'details' => array]
     */
    public function calculateCombinedOdds(array $oddIds): array
    {
        if (empty($oddIds)) {
            return [
                'valid' => false,
                'odds' => 0.00,
                'message' => 'No hay selecciones en el boleto.',
                'details' => []
            ];
        }

        $db = \Config\Database::connect();
        
        // Obtener detalles de las cuotas con sus mercados y eventos
        $selections = $db->table('odds o')
            ->select('o.id as odd_id, o.selection, o.odds_decimal, o.status as odd_status, m.id as market_id, m.name as market_name, m.type as market_type, m.status as market_status, e.id as event_id, e.home_team, e.away_team, e.status as event_status, e.start_time as event_start_time')
            ->join('markets m', 'm.id = o.market_id')
            ->join('events e', 'e.id = m.event_id')
            ->whereIn('o.id', $oddIds)
            ->where('o.active', 1)
            ->where('o.status', 'pending')
            ->where('m.status', 'open')
            ->get()
            ->getResultArray();

        if (count($selections) !== count($oddIds)) {
            return [
                'valid' => false,
                'odds' => 0.00,
                'message' => 'Una o más cuotas seleccionadas no están disponibles o han cambiado.',
                'details' => []
            ];
        }

        // Validar que ningún partido esté en vivo o falten menos de 30 minutos para empezar
        foreach ($selections as $sel) {
            if ($sel['market_status'] !== 'open' || $sel['odd_status'] !== 'pending') {
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => 'Una o mas cuotas seleccionadas estan suspendidas o cerradas.',
                    'details' => []
                ];
            }

            $status = $sel['event_status'];
            $startTime = strtotime($sel['event_start_time']);
            $isLive = ($status === 'live');
            $isTooClose = ($startTime !== false && ($startTime - time()) <= 1800);

            if ($isLive || $isTooClose) {
                $reason = $isLive ? "está en vivo" : "comienza en menos de 30 minutos";
                $eventTitle = $sel['home_team'] . ' vs ' . $sel['away_team'];
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => "No se permiten apuestas en " . $eventTitle . " porque " . $reason . ".",
                    'details' => []
                ];
            }
        }

        // Agrupar selecciones por evento
        $groupedByEvent = [];
        foreach ($selections as $sel) {
            $groupedByEvent[$sel['event_id']][] = $sel;
        }

        $totalOdds = 1.0;
        $eventDetails = [];

        foreach ($groupedByEvent as $eventId => $eventSelections) {
            $eventTitle = $eventSelections[0]['home_team'] . ' vs ' . $eventSelections[0]['away_team'];
            
            // 1. Validar incompatibilidades
            // Regla A: No se puede seleccionar más de una cuota del mismo mercado
            $marketIds = array_column($eventSelections, 'market_id');
            if (count($marketIds) !== count(array_unique($marketIds))) {
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => "Incompatible: No puedes combinar múltiples resultados del mercado '" . $eventSelections[0]['market_name'] . "' en el partido " . $eventTitle . ".",
                    'details' => []
                ];
            }

            // Regla B: Validar selecciones mutuamente excluyentes entre diferentes mercados
            $hasLocalWin = false;
            $hasAwayWin = false;
            $hasDraw = false;
            $hasUnder05 = false;
            $hasUnder15 = false;
            $hasUnder25 = false;
            $hasOver25 = false;
            $hasBTTSNo = false;
            $hasBTTSSi = false;
            
            foreach ($eventSelections as $sel) {
                $selName = strtolower(trim($sel['selection']));
                $mType = strtolower(trim($sel['market_type']));
                
                if ($mType === '1x2') {
                    if ($selName === '1' || strpos($selName, 'local') !== false || strpos($selName, 'home') !== false) {
                        $hasLocalWin = true;
                    } elseif ($selName === '2' || strpos($selName, 'visitante') !== false || strpos($selName, 'away') !== false) {
                        $hasAwayWin = true;
                    } elseif ($selName === 'x' || strpos($selName, 'empate') !== false || strpos($selName, 'draw') !== false) {
                        $hasDraw = true;
                    }
                }
                
                // Totales de goles
                if ($mType === 'totals' || strpos(strtolower($sel['market_name']), 'total') !== false || strpos(strtolower($sel['market_name']), 'goles') !== false) {
                    if (strpos($selName, 'under 0.5') !== false || strpos($selName, 'menos de 0.5') !== false || strpos($selName, 'u 0.5') !== false) {
                        $hasUnder05 = true;
                    }
                    if (strpos($selName, 'under 1.5') !== false || strpos($selName, 'menos de 1.5') !== false || strpos($selName, 'u 1.5') !== false) {
                        $hasUnder15 = true;
                    }
                    if (strpos($selName, 'under 2.5') !== false || strpos($selName, 'menos de 2.5') !== false || strpos($selName, 'u 2.5') !== false) {
                        $hasUnder25 = true;
                    }
                    if (strpos($selName, 'over 2.5') !== false || strpos($selName, 'mas de 2.5') !== false || strpos($selName, 'más de 2.5') !== false || strpos($selName, 'o 2.5') !== false) {
                        $hasOver25 = true;
                    }
                }

                // BTTS
                if ($mType === 'btts' || strpos(strtolower($sel['market_name']), 'ambos equipos') !== false) {
                    if ($selName === 'no' || strpos($selName, 'no') !== false) {
                        $hasBTTSNo = true;
                    } elseif ($selName === 'yes' || $selName === 'si' || $selName === 'sí' || strpos($selName, 'si') !== false || strpos($selName, 'sí') !== false) {
                        $hasBTTSSi = true;
                    }
                }
            }

            // Exclusión: Victoria de un equipo y Menos de 0.5 goles
            if (($hasLocalWin || $hasAwayWin) && $hasUnder05) {
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => "Incompatible: Victoria de un equipo y Menos de 0.5 goles en " . $eventTitle . ".",
                    'details' => []
                ];
            }

            // Exclusión: Ambos anotan Sí y Menos de 1.5 goles (mínimo 1-1 = 2 goles)
            if ($hasBTTSSi && ($hasUnder05 || $hasUnder15)) {
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => "Incompatible: Ambos equipos anotan y Menos de 1.5 goles en " . $eventTitle . ".",
                    'details' => []
                ];
            }

            // Exclusión: Ambos anotan Sí y Menos de 0.5 goles
            if ($hasBTTSSi && $hasUnder05) {
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => "Incompatible: Ambos equipos anotan y Menos de 0.5 goles en " . $eventTitle . ".",
                    'details' => []
                ];
            }

            // Exclusión: Over 2.5 y Menos de 1.5 / 0.5 goles
            if ($hasOver25 && ($hasUnder05 || $hasUnder15 || $hasUnder25)) {
                return [
                    'valid' => false,
                    'odds' => 0.00,
                    'message' => "Incompatible: Secciones mutuamente excluyentes en la línea de goles para " . $eventTitle . ".",
                    'details' => []
                ];
            }

            // 2. Calcular cuota combinada del evento aplicando correlaciones
            $countSelections = count($eventSelections);
            $eventOdds = 1.0;
            foreach ($eventSelections as $sel) {
                $eventOdds *= (float) $sel['odds_decimal'];
            }

            $correlationFactor = 0.0;
            if ($countSelections > 1) {
                // Hay múltiples selecciones para el mismo partido -> Bet Builder
                $types = array_map(function($s) {
                    return strtolower($s['market_type']);
                }, $eventSelections);

                sort($types);
                
                // Si combinan 1x2 + totales (Over/Under)
                if (in_array('1x2', $types) && in_array('totals', $types)) {
                    $correlationFactor = 0.18; // Descuento del 18%
                }
                // Si combinan 1x2 + btts
                elseif (in_array('1x2', $types) && in_array('btts', $types)) {
                    $correlationFactor = 0.15; // Descuento del 15%
                }
                // Si combinan totals + btts
                elseif (in_array('totals', $types) && in_array('btts', $types)) {
                    $correlationFactor = 0.22; // Descuento del 22%
                }
                // Genérico para cualquier combinación de 2 mercados distintos
                elseif ($countSelections === 2) {
                    $correlationFactor = 0.12; // Descuento base del 12%
                }
                // Si combinan 3 mercados
                elseif ($countSelections === 3) {
                    $correlationFactor = 0.25; // Descuento del 25%
                }
                // Si combinan 4 o más mercados
                else {
                    $correlationFactor = 0.32; // Descuento máximo del 32%
                }
            }

            $finalEventOdds = $eventOdds * (1 - $correlationFactor);
            $finalEventOdds = round(max(1.01, $finalEventOdds), 2);

            $totalOdds *= $finalEventOdds;
            
            $eventDetails[] = [
                'event_id' => $eventId,
                'event_title' => $eventTitle,
                'raw_odds' => $eventOdds,
                'correlation_factor' => $correlationFactor,
                'final_odds' => $finalEventOdds,
                'selections' => $eventSelections
            ];
        }

        $totalOdds = round($totalOdds, 2);

        return [
            'valid' => true,
            'odds' => $totalOdds,
            'message' => 'Cálculo exitoso.',
            'details' => $eventDetails
        ];
    }
}
