<?php

namespace App\Services;

class LiveScoreProviderService
{
    private string $baseUrl;
    private string $apiKey;
    private string $provider;
    private string $timezone;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (env('API_FOOTBALL_BASE_URL') ?: getenv('API_FOOTBALL_BASE_URL') ?: 'https://v3.football.api-sports.io'), '/');
        $this->apiKey = (string) (env('API_FOOTBALL_KEY') ?: getenv('API_FOOTBALL_KEY') ?: '');
        $this->provider = (string) (env('LIVE_SCORE_PROVIDER') ?: getenv('LIVE_SCORE_PROVIDER') ?: 'espn_free');
        $this->timezone = (string) (env('LIVE_SCORE_TIMEZONE') ?: getenv('LIVE_SCORE_TIMEZONE') ?: 'America/Argentina/Buenos_Aires');
    }

    public function syncEvent(array $event): array
    {
        if ($this->provider === 'espn_free') {
            return $this->syncEventFromEspn($event);
        }

        if ($this->apiKey === '') {
            throw new \RuntimeException('API_FOOTBALL_KEY no esta configurada.');
        }

        $fixture = $this->fixtureForEvent($event);
        if (! $fixture) {
            return [
                'matched' => false,
                'updated' => false,
                'message' => 'No se encontro fixture coincidente en API-Football.',
            ];
        }

        $fixtureId = (string) ($fixture['fixture']['id'] ?? '');
        $apiStatus = (string) ($fixture['fixture']['status']['short'] ?? '');
        $elapsed = $fixture['fixture']['status']['elapsed'] ?? null;
        $goalsHome = $fixture['goals']['home'] ?? null;
        $goalsAway = $fixture['goals']['away'] ?? null;
        $internalStatus = $this->mapStatus($apiStatus);

        $payload = [
            'api_fixture_id' => $fixtureId,
            'api_provider' => 'api_football',
            'api_status' => $apiStatus,
            'api_elapsed' => is_numeric($elapsed) ? (int) $elapsed : null,
            'api_last_score_sync_at' => date('Y-m-d H:i:s'),
            'score_source' => 'api_football',
            'status' => $internalStatus,
        ];

        if ($goalsHome !== null && $goalsAway !== null) {
            $payload['score_home'] = (int) $goalsHome;
            $payload['score_away'] = (int) $goalsAway;
        }

        if ($internalStatus === 'finished') {
            $payload['settled'] = 0;
        }

        \Config\Database::connect()->table('events')->where('id', (int) $event['id'])->update($payload);

        return [
            'matched' => true,
            'updated' => true,
            'fixture_id' => $fixtureId,
            'api_status' => $apiStatus,
            'elapsed' => $elapsed,
            'status' => $internalStatus,
            'score_home' => $goalsHome,
            'score_away' => $goalsAway,
            'message' => $this->formatResultMessage($event, $payload),
        ];
    }

    public function linkEvent(int $eventId, string $fixtureId): array
    {
        if ($this->provider === 'espn_free') {
            return $this->linkEspnEvent($eventId, $fixtureId);
        }

        if ($this->apiKey === '') {
            throw new \RuntimeException('API_FOOTBALL_KEY no esta configurada.');
        }

        $event = \Config\Database::connect()->table('events')->where('id', $eventId)->get()->getRowArray();
        if (! $event) {
            throw new \RuntimeException('Evento no encontrado.');
        }

        $fixtureId = trim($fixtureId);
        if ($fixtureId === '' || ! ctype_digit($fixtureId)) {
            throw new \RuntimeException('Fixture ID invalido.');
        }

        \Config\Database::connect()->table('events')->where('id', $eventId)->update([
            'api_fixture_id' => $fixtureId,
            'api_provider' => 'api_football',
        ]);

        $event['api_fixture_id'] = $fixtureId;

        return $this->syncEvent($event);
    }

    private function syncEventFromEspn(array $event): array
    {
        $fixture = $this->espnFixtureForEvent($event);
        if (! $fixture) {
            return [
                'matched' => false,
                'updated' => false,
                'message' => 'No se encontro fixture coincidente en ESPN gratis.',
            ];
        }

        $competition = $fixture['competitions'][0] ?? [];
        $competitors = $competition['competitors'] ?? [];
        if (count($competitors) < 2) {
            return [
                'matched' => false,
                'updated' => false,
                'message' => 'ESPN encontro el evento, pero sin competidores suficientes.',
            ];
        }

        $scores = $this->scoresFromEspnCompetition($event, $competitors);
        $status = $competition['status'] ?? $fixture['status'] ?? [];
        $apiStatus = (string) ($status['type']['shortDetail'] ?? $status['type']['description'] ?? $status['type']['name'] ?? '');
        $state = (string) ($status['type']['state'] ?? '');
        $completed = (bool) ($status['type']['completed'] ?? false);
        $elapsed = $this->elapsedFromEspnStatus($status);
        $internalStatus = $this->mapEspnStatus($state, $completed, $apiStatus);

        $payload = [
            'api_fixture_id' => (string) ($fixture['id'] ?? ''),
            'api_provider' => 'espn_free',
            'api_status' => substr($apiStatus, 0, 30),
            'api_elapsed' => $elapsed,
            'api_last_score_sync_at' => date('Y-m-d H:i:s'),
            'score_source' => 'espn_free',
            'status' => $internalStatus,
        ];

        if ($scores !== null) {
            $payload['score_home'] = $scores[0];
            $payload['score_away'] = $scores[1];
        }

        if ($internalStatus === 'finished') {
            $payload['settled'] = 0;
        }

        \Config\Database::connect()->table('events')->where('id', (int) $event['id'])->update($payload);

        return [
            'matched' => true,
            'updated' => true,
            'fixture_id' => $payload['api_fixture_id'],
            'api_status' => $payload['api_status'],
            'elapsed' => $elapsed,
            'status' => $internalStatus,
            'score_home' => $payload['score_home'] ?? null,
            'score_away' => $payload['score_away'] ?? null,
            'message' => $this->formatResultMessage($event, $payload),
        ];
    }

    private function linkEspnEvent(int $eventId, string $fixtureId): array
    {
        $event = \Config\Database::connect()->table('events')->where('id', $eventId)->get()->getRowArray();
        if (! $event) {
            throw new \RuntimeException('Evento no encontrado.');
        }

        $fixtureId = trim($fixtureId);
        if ($fixtureId === '') {
            throw new \RuntimeException('Fixture ID invalido.');
        }

        \Config\Database::connect()->table('events')->where('id', $eventId)->update([
            'api_fixture_id' => $fixtureId,
            'api_provider' => 'espn_free',
        ]);

        $event['api_fixture_id'] = $fixtureId;
        $event['api_provider'] = 'espn_free';

        return $this->syncEventFromEspn($event);
    }

    public function syncLeague(int $leagueId): array
    {
        $db = \Config\Database::connect();
        $events = $db->table('events')
            ->where('league_id', $leagueId)
            ->whereIn('status', ['pending', 'live', 'finished'])
            ->where('start_time >=', date('Y-m-d H:i:s', strtotime('-12 hours')))
            ->where('start_time <=', date('Y-m-d H:i:s', strtotime('+2 days')))
            ->orderBy('start_time', 'ASC')
            ->get()
            ->getResultArray();

        $updated = 0;
        $matched = 0;
        $unmatched = 0;
        $results = [];

        foreach ($events as $event) {
            try {
                $result = $this->syncEvent($event);
                if ($result['matched']) {
                    $matched++;
                } else {
                    $unmatched++;
                }
                if ($result['updated']) {
                    $updated++;
                }
                $results[] = $result['message'];
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'API-Football token')) {
                    throw $e;
                }
                $unmatched++;
                $results[] = ($event['home_team'] ?? 'Evento') . ' vs ' . ($event['away_team'] ?? '') . ': ' . $e->getMessage();
            }
        }

        return [
            'updated' => $updated,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'results' => $results,
        ];
    }

    private function fixtureForEvent(array $event): ?array
    {
        $fixtureId = trim((string) ($event['api_fixture_id'] ?? ''));
        if ($fixtureId !== '') {
            $fixtures = $this->request('/fixtures', ['id' => $fixtureId]);
            return $fixtures[0] ?? null;
        }

        $date = date('Y-m-d', strtotime((string) $event['start_time']));
        $fixtures = $this->request('/fixtures', [
            'date' => $date,
            'timezone' => $this->timezone,
        ]);

        $best = null;
        $bestScore = 0;
        foreach ($fixtures as $fixture) {
            $score = $this->matchScore($event, $fixture);
            if ($score > $bestScore) {
                $best = $fixture;
                $bestScore = $score;
            }
        }

        return $bestScore >= 160 ? $best : null;
    }

    private function espnFixtureForEvent(array $event): ?array
    {
        $fixtureId = trim((string) ($event['api_fixture_id'] ?? ''));
        $fixtures = $this->espnScoreboardFixtures($event);

        if ($fixtureId !== '') {
            foreach ($fixtures as $fixture) {
                if ((string) ($fixture['id'] ?? '') === $fixtureId) {
                    return $fixture;
                }
            }
        }

        $best = null;
        $bestScore = 0;
        foreach ($fixtures as $fixture) {
            $score = $this->matchScoreEspn($event, $fixture);
            if ($score > $bestScore) {
                $best = $fixture;
                $bestScore = $score;
            }
        }

        return $bestScore >= 145 ? $best : null;
    }

    private function espnScoreboardFixtures(array $event): array
    {
        $date = date('Ymd', strtotime((string) $event['start_time']));
        $urls = [
            'https://site.api.espn.com/apis/site/v2/sports/soccer/all/scoreboard?dates=' . $date,
            'https://site.api.espn.com/apis/site/v2/sports/soccer/all/scoreboard',
        ];

        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_NOPROXY => '*',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode < 200 || $httpCode >= 300) {
                log_message('error', 'ESPN free scoreboard HTTP ' . $httpCode . ($error ? ': ' . $error : ''));
                continue;
            }

            $data = json_decode($response, true);
            if (is_array($data) && is_array($data['events'] ?? null)) {
                return $data['events'];
            }
        }

        return [];
    }

    private function scoresFromEspnCompetition(array $event, array $competitors): ?array
    {
        $home = $this->normalizeTeam((string) ($event['home_team'] ?? ''));
        $away = $this->normalizeTeam((string) ($event['away_team'] ?? ''));
        $homeScore = null;
        $awayScore = null;

        foreach ($competitors as $competitor) {
            $name = $this->normalizeTeam((string) ($competitor['team']['displayName'] ?? $competitor['team']['name'] ?? ''));
            $score = $competitor['score'] ?? null;
            if ($score === null || $score === '') {
                continue;
            }

            similar_text($home, $name, $homePct);
            similar_text($away, $name, $awayPct);

            if ($homePct >= $awayPct) {
                $homeScore = (int) $score;
            } else {
                $awayScore = (int) $score;
            }
        }

        return $homeScore !== null && $awayScore !== null ? [$homeScore, $awayScore] : null;
    }

    private function mapEspnStatus(string $state, bool $completed, string $description): string
    {
        $description = strtolower($description);
        if (str_contains($description, 'cancel') || str_contains($description, 'postpon') || str_contains($description, 'aband')) {
            return 'cancelled';
        }

        if ($completed || $state === 'post') {
            return 'finished';
        }

        if ($state === 'in') {
            return 'live';
        }

        return 'pending';
    }

    private function elapsedFromEspnStatus(array $status): ?int
    {
        $candidates = [
            $status['displayClock'] ?? null,
            $status['type']['shortDetail'] ?? null,
            $status['type']['detail'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && preg_match('/\b(\d{1,3})\b/', $candidate, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function request(string $path, array $query): array
    {
        $url = $this->baseUrl . $path . '?' . http_build_query($query);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_NOPROXY => '*',
            CURLOPT_HTTPHEADER => ['x-apisports-key: ' . $this->apiKey],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('API-Football HTTP ' . $httpCode . ($error ? ': ' . $error : ''));
        }

        $data = json_decode($response, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Respuesta invalida de API-Football.');
        }

        $errors = $data['errors'] ?? [];
        if (is_array($errors) && $errors !== []) {
            $messages = [];
            foreach ($errors as $key => $message) {
                $messages[] = is_string($message) ? $message : json_encode($message);
            }
            $message = implode(' ', array_filter($messages));
            if (isset($errors['token'])) {
                throw new \RuntimeException('API-Football token invalido o faltante. Configura una API_FOOTBALL_KEY real de API-Sports/API-Football en .env. ' . $message);
            }
            throw new \RuntimeException('API-Football devolvio error: ' . $message);
        }

        return is_array($data['response'] ?? null) ? $data['response'] : [];
    }

    private function mapStatus(string $status): string
    {
        switch ($status) {
            case '1H':
            case 'HT':
            case '2H':
            case 'ET':
            case 'BT':
            case 'P':
            case 'SUSP':
            case 'INT':
                return 'live';
            case 'FT':
            case 'AET':
            case 'PEN':
                return 'finished';
            case 'CANC':
            case 'ABD':
            case 'PST':
                return 'cancelled';
            default:
                return 'pending';
        }
    }

    private function matchScore(array $event, array $fixture): int
    {
        $home = $this->normalizeTeam((string) ($event['home_team'] ?? ''));
        $away = $this->normalizeTeam((string) ($event['away_team'] ?? ''));
        $apiHome = $this->normalizeTeam((string) ($fixture['teams']['home']['name'] ?? ''));
        $apiAway = $this->normalizeTeam((string) ($fixture['teams']['away']['name'] ?? ''));

        similar_text($home, $apiHome, $homePct);
        similar_text($away, $apiAway, $awayPct);

        return (int) round($homePct + $awayPct);
    }

    private function matchScoreEspn(array $event, array $fixture): int
    {
        $competition = $fixture['competitions'][0] ?? [];
        $competitors = $competition['competitors'] ?? [];
        if (count($competitors) < 2) {
            return 0;
        }

        $home = $this->normalizeTeam((string) ($event['home_team'] ?? ''));
        $away = $this->normalizeTeam((string) ($event['away_team'] ?? ''));

        $names = [];
        foreach ($competitors as $competitor) {
            $names[] = $this->normalizeTeam((string) ($competitor['team']['displayName'] ?? $competitor['team']['name'] ?? ''));
        }

        similar_text($home, $names[0] ?? '', $homeA);
        similar_text($away, $names[1] ?? '', $awayA);
        similar_text($home, $names[1] ?? '', $homeB);
        similar_text($away, $names[0] ?? '', $awayB);

        return (int) round(max($homeA + $awayA, $homeB + $awayB));
    }

    private function normalizeTeam(string $team): string
    {
        $team = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $team) ?: $team;
        $team = strtolower($team);
        $aliases = [
            'polonia' => 'poland',
            'dinamarca' => 'denmark',
            'paises bajos' => 'netherlands',
            'luxemburgo' => 'luxembourg',
            'italia' => 'italy',
            'argelia' => 'algeria',
            'islas virgenes britanicas' => 'british virgin islands',
            'rd congo' => 'dr congo',
            'republica democratica del congo' => 'dr congo',
            'kirguistan' => 'kyrgyz republic',
            'kenia' => 'kenya',
            'filipinas' => 'philippines',
        ];
        foreach ($aliases as $from => $to) {
            $team = str_replace($from, $to, $team);
        }
        $team = preg_replace('/[^a-z0-9]+/', ' ', $team) ?: $team;
        $team = preg_replace('/\b(fc|cf|club|seleccion|futbol|football|de|la|el)\b/', ' ', $team) ?: $team;
        return trim(preg_replace('/\s+/', ' ', $team) ?: $team);
    }

    private function formatResultMessage(array $event, array $payload): string
    {
        $score = '';
        if (array_key_exists('score_home', $payload) && array_key_exists('score_away', $payload)) {
            $score = ' ' . $payload['score_home'] . '-' . $payload['score_away'];
        }

        $elapsed = $payload['api_elapsed'] ? " {$payload['api_elapsed']}'" : '';

        return "{$event['home_team']} vs {$event['away_team']}: {$payload['status']} {$payload['api_status']}{$elapsed}{$score}";
    }
}
