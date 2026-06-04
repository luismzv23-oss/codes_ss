<?php

namespace App\Libraries\OddsProvider;

/**
 * Interface para proveedores de odds.
 * Hoy usa The Odds API, mañana Kambi Odds Feed+ sin cambiar lógica de negocio.
 */
interface OddsProviderInterface
{
    /**
     * Devuelve la lista de deportes disponibles.
     * @return array [['key' => 'soccer_argentina', 'title' => 'Argentina - Primera', 'active' => true], ...]
     */
    public function getSports(): array;

    /**
     * Devuelve eventos + odds para un deporte.
     * @param string $sportKey ej: "soccer_argentina_primera_division"
     * @param array $markets ej: ['h2h', 'totals', 'spreads']
     * @return array Eventos normalizados con cuotas
     */
    public function getOdds(string $sportKey, array $markets = ['h2h']): array;

    /**
     * Devuelve scores en vivo para un deporte.
     * @param string $sportKey
     * @return array
     */
    public function getScores(string $sportKey): array;

    /**
     * Nombre del proveedor para guardar en api_provider.
     */
    public function getProviderName(): string;
}
