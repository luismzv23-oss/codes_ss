<?php

namespace App\Libraries\OddsProvider;

/**
 * Adaptador para The Odds API (https://the-odds-api.com)
 * Consume la API v4 y normaliza los datos al formato interno de Codex_ss.
 */
class TheOddsApiAdapter implements OddsProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.the-odds-api.com/v4';
    private string $regions;
    private int $remainingRequests = -1;

    public function __construct()
    {
        $this->apiKey = getenv('THE_ODDS_API_KEY') ?: '';
        $this->regions = getenv('ODDS_API_REGIONS') ?: 'eu';

        if (empty($this->apiKey)) {
            throw new \RuntimeException('THE_ODDS_API_KEY no está configurada en .env');
        }
    }

    public function getProviderName(): string
    {
        return 'theoddsapi';
    }

    public function getSports(): array
    {
        $data = $this->request('/sports');
        if (!is_array($data)) {
            return [];
        }

        return array_map(function ($sport) {
            return [
                'key'         => $sport['key'] ?? '',
                'group'       => $sport['group'] ?? '',
                'title'       => $sport['title'] ?? '',
                'description' => $sport['description'] ?? '',
                'active'      => $sport['active'] ?? false,
                'has_outrights' => $sport['has_outrights'] ?? false,
            ];
        }, $data);
    }

    public function getOdds(string $sportKey, array $markets = ['h2h']): array
    {
        $params = [
            'regions'     => $this->regions,
            'markets'     => implode(',', $markets),
            'oddsFormat'  => 'decimal',
        ];

        $data = $this->request("/sports/{$sportKey}/odds", $params);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function getScores(string $sportKey): array
    {
        $params = [
            'daysFrom' => 1,
        ];

        $data = $this->request("/sports/{$sportKey}/scores", $params);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Devuelve cuántos requests quedan en la cuota de la API.
     */
    public function getRemainingRequests(): int
    {
        return $this->remainingRequests;
    }

    /**
     * Realiza la petición HTTP a The Odds API.
     */
    private function request(string $endpoint, array $params = []): ?array
    {
        $params['apiKey'] = $this->apiKey;

        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Accept: application/json\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            log_message('error', "TheOddsAPI: No se pudo conectar a {$endpoint}");
            return null;
        }

        // Leer headers de cuota
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'x-requests-remaining:') === 0) {
                    $this->remainingRequests = (int) trim(substr($header, 21));
                }
            }
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "TheOddsAPI: JSON inválido de {$endpoint}: " . json_last_error_msg());
            return null;
        }

        // Chequear si es error de la API
        if (isset($decoded['message'])) {
            log_message('error', "TheOddsAPI Error: " . $decoded['message']);
            return null;
        }

        return $decoded;
    }
}
