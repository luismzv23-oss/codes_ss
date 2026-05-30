<?php

namespace App\Jobs;

use App\Libraries\CacheManager;

/**
 * Process and cache aggregated data/reports.
 */
class DataProcessingJob extends BaseJob
{
    public function handle(): void
    {
        $report = $this->payload['report'] ?? 'daily_summary';
        $cache  = CacheManager::getInstance();

        log_message('info', "[DataJob] Processing report: {$report}");
        usleep(500000); // 500ms — simulate heavy computation

        // Generate mock report data
        $reportData = [
            'report_type' => $report,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => [
                'total_bets'       => rand(3000, 5000),
                'total_volume'     => rand(40000, 60000),
                'avg_bet_size'     => round(rand(800, 1500) / 100, 2),
                'unique_users'     => rand(200, 500),
                'most_popular_event' => 'Champions League Final',
                'peak_hour'        => rand(18, 22) . ':00',
            ],
        ];

        // Cache the result for 1 hour
        $cache->set("report_{$report}", $reportData, CacheManager::TTL_LONG);

        log_message('info', "[DataJob] Report '{$report}' cached successfully.");
    }
}
