<?php

namespace App\Services;

class ApiFootballService
{
    protected $baseUrl = 'https://v3.football.api-sports.io';
    protected $apiKey;

    public function __construct()
    {
        // El API key puede guardarse en .env como API_FOOTBALL_KEY
        $this->apiKey = getenv('API_FOOTBALL_KEY');
    }

    /**
     * Realiza una petición genérica a la API
     */
    protected function request($endpoint, $params = [])
    {
        if (empty($this->apiKey)) {
            log_message('error', 'API_FOOTBALL_KEY no está configurada.');
            return ['errors' => ['key' => 'No API Key configured']];
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-apisports-key: ' . $this->apiKey,
            'Accept: application/json'
        ]);
        // Ignorar SSL en entornos de desarrollo local si es necesario, 
        // pero mejor dejarlo seguro para prod.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', "cURL Error en ApiFootballService: " . $err);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Obtiene los partidos (fixtures) por fecha
     * 
     * @param string $date Formato YYYY-MM-DD
     * @param int $leagueId (Opcional) Filtrar por liga
     * @return array
     */
    public function getFixturesByDate($date, $leagueId = null)
    {
        $params = ['date' => $date, 'timezone' => 'America/Argentina/Buenos_Aires'];
        if ($leagueId) {
            $params['league'] = $leagueId;
            // Normalmente requiere también el parámetro season
            $params['season'] = date('Y');
        }

        return $this->request('fixtures', $params);
    }

    /**
     * Obtiene los partidos (fixtures) futuros para una liga específica
     */
    public function getUpcomingFixtures($leagueId, $season = null)
    {
        $params = [
            'league' => $leagueId,
            'season' => $season ?: date('Y'),
            'next' => 20, // Traer los próximos 20
            'timezone' => 'America/Argentina/Buenos_Aires'
        ];
        return $this->request('fixtures', $params);
    }

    /**
     * Obtiene las cuotas (odds) pre-partido para un fixture
     * Usa Bet365 por defecto (bookmaker_id = 8)
     */
    public function getOdds($fixtureId, $bookmaker = 8)
    {
        $params = [
            'fixture' => $fixtureId,
            'bookmaker' => $bookmaker
        ];

        return $this->request('odds', $params);
    }

    /**
     * Obtiene el estado y marcador actual/final de un fixture (Live o Final)
     */
    public function getFixtureById($fixtureId)
    {
        return $this->request('fixtures', ['id' => $fixtureId, 'timezone' => 'America/Argentina/Buenos_Aires']);
    }
}
