<?php

namespace App\Libraries\OddsProvider;

/**
 * Stub para Kambi Odds Feed+.
 * Se activará cuando se firme el contrato enterprise.
 */
class KambiAdapter implements OddsProviderInterface
{
    public function __construct()
    {
        // Kambi requiere contrato B2B. Este adapter se completará
        // cuando se tenga acceso al sandbox/producción de Kambi.
    }

    public function getProviderName(): string
    {
        return 'kambi';
    }

    public function getSports(): array
    {
        throw new \RuntimeException(
            'Kambi Odds Feed+ no está habilitado. '
            . 'Se requiere contrato enterprise con Kambi. '
            . 'Usá THE_ODDS_API_KEY en .env para consumir datos vía The Odds API.'
        );
    }

    public function getOdds(string $sportKey, array $markets = ['h2h']): array
    {
        throw new \RuntimeException('Kambi Odds Feed+ no está habilitado.');
    }

    public function getScores(string $sportKey): array
    {
        throw new \RuntimeException('Kambi Odds Feed+ no está habilitado.');
    }
}
