<?php

namespace App\Libraries;

use App\Libraries\CacheManager;

/**
 * RankingService — Sorted rankings using cache layer.
 * Uses Redis ZSET-like logic via serialized arrays.
 * In production with Redis, can swap to native ZADD/ZRANGEBYSCORE.
 */
class RankingService
{
    private CacheManager $cache;
    private const PREFIX = 'rank_';

    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }

    /**
     * Add or update a member's score in a ranking board
     */
    public function addScore(string $board, string $member, float $score): void
    {
        $data = $this->getBoard($board);
        $data[$member] = ($data[$member] ?? 0) + $score;
        arsort($data); // Keep sorted descending
        $this->cache->set(self::PREFIX . $board, $data, CacheManager::TTL_DAY);
    }

    /**
     * Set an absolute score (overwrite)
     */
    public function setScore(string $board, string $member, float $score): void
    {
        $data = $this->getBoard($board);
        $data[$member] = $score;
        arsort($data);
        $this->cache->set(self::PREFIX . $board, $data, CacheManager::TTL_DAY);
    }

    /**
     * Get top N members from a board
     */
    public function getTop(string $board, int $limit = 10): array
    {
        $data = $this->getBoard($board);
        $result = [];
        $rank = 1;
        foreach (array_slice($data, 0, $limit, true) as $member => $score) {
            $result[] = [
                'rank'   => $rank++,
                'member' => $member,
                'score'  => $score,
            ];
        }
        return $result;
    }

    /**
     * Get a member's rank (1-indexed)
     */
    public function getRank(string $board, string $member): ?int
    {
        $data = $this->getBoard($board);
        $keys = array_keys($data);
        $pos = array_search($member, $keys);
        return $pos !== false ? $pos + 1 : null;
    }

    /**
     * Get a member's score
     */
    public function getScore(string $board, string $member): ?float
    {
        $data = $this->getBoard($board);
        return $data[$member] ?? null;
    }

    /**
     * Get total member count
     */
    public function getCount(string $board): int
    {
        return count($this->getBoard($board));
    }

    /**
     * Remove a member from a board
     */
    public function removeMember(string $board, string $member): void
    {
        $data = $this->getBoard($board);
        unset($data[$member]);
        $this->cache->set(self::PREFIX . $board, $data, CacheManager::TTL_DAY);
    }

    /**
     * Clear an entire board
     */
    public function clearBoard(string $board): void
    {
        $this->cache->forget(self::PREFIX . $board);
    }

    /**
     * Seed demo rankings for testing
     */
    public function seedDemoData(): void
    {
        // Top Apostadores — by total wagered
        $bettors = [
            'carlos_bet'   => 45200.00,
            'lucia_pro'    => 38750.50,
            'maria_22'     => 27400.00,
            'diego_m'      => 21800.75,
            'admin'        => 15600.00,
            'pablo_99'     => 12300.00,
            'ana_garcia'   => 9800.50,
            'roberto_vip'  => 8400.00,
            'sofia_lopez'  => 6200.25,
            'juan_martin'  => 4100.00,
        ];
        foreach ($bettors as $member => $score) {
            $this->setScore('top_bettors', $member, $score);
        }

        // Top Ganadores — by net profit
        $winners = [
            'lucia_pro'   => 12400.00,
            'carlos_bet'  => 8950.50,
            'pablo_99'    => 5200.00,
            'maria_22'    => 3800.00,
            'ana_garcia'  => 2100.75,
            'juan_martin' => 1500.00,
            'roberto_vip' => -200.00,
            'sofia_lopez' => -800.50,
            'diego_m'     => -1200.00,
            'admin'       => -2500.00,
        ];
        foreach ($winners as $member => $score) {
            $this->setScore('top_winners', $member, $score);
        }

        // Eventos más apostados
        $events = [
            'Champions League Final'      => 18400,
            'Copa América ARG vs BRA'     => 12800,
            'NBA Finals Game 7'           => 9200,
            'Premier League J38'          => 5830,
            'Roland Garros Final'         => 4100,
            'NFL Super Bowl'              => 3200,
            'F1 GP Monaco'                => 2800,
            'UFC 320'                     => 2100,
            'Wimbledon Final'             => 1800,
            'Tour de France Stage 21'     => 950,
        ];
        foreach ($events as $event => $volume) {
            $this->setScore('hot_events', $event, $volume);
        }
    }

    /**
     * Raw board data
     */
    private function getBoard(string $board): array
    {
        return $this->cache->get(self::PREFIX . $board, []);
    }
}
