<?php

namespace App\Jobs;

use App\Libraries\RankingService;

/**
 * Recalculate rankings from database data.
 * In production, this would query the real bets/transactions tables.
 */
class ProcessRankingsJob extends BaseJob
{
    public function handle(): void
    {
        $rankings = new RankingService();

        $type = $this->payload['type'] ?? 'all';

        log_message('info', "[RankingsJob] Processing rankings type: {$type}");

        // Simulate DB query time
        usleep(300000); // 300ms

        if ($type === 'all' || $type === 'bettors') {
            // In production: query real transactions table
            // $db = \Config\Database::connect();
            // $rows = $db->query("SELECT username, SUM(amount) as total FROM bets GROUP BY username ORDER BY total DESC LIMIT 50")->getResultArray();
            // foreach ($rows as $r) $rankings->setScore('top_bettors', $r['username'], $r['total']);

            // For now, seed with demo data
            $rankings->seedDemoData();
        }

        log_message('info', "[RankingsJob] Rankings recalculated successfully.");
    }
}
