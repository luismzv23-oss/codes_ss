<?php

namespace App\Libraries;

use App\Libraries\CacheManager;

/**
 * QueueManager — File-based job queue system (Redis-ready).
 * Jobs are serialized to cache. A spark worker command processes them.
 * When Redis is available, swap to native LPUSH/BRPOP for real-time processing.
 */
class QueueManager
{
    private CacheManager $cache;
    private const QUEUE_PREFIX = 'queue_';
    private const FAILED_KEY   = 'queue__failed';
    private const STATS_KEY    = 'queue__stats';

    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }

    /**
     * Push a job to a named queue
     */
    public function push(string $queue, string $jobClass, array $payload = [], int $delay = 0): string
    {
        $jobId = uniqid('job_', true);
        $job = [
            'id'         => $jobId,
            'class'      => $jobClass,
            'payload'    => $payload,
            'queue'      => $queue,
            'status'     => 'pending',
            'attempts'   => 0,
            'max_tries'  => 3,
            'created_at' => date('Y-m-d H:i:s'),
            'run_after'  => $delay > 0 ? date('Y-m-d H:i:s', time() + $delay) : null,
        ];

        $jobs = $this->getQueueJobs($queue);
        $jobs[] = $job;
        $this->cache->set(self::QUEUE_PREFIX . $queue, $jobs, CacheManager::TTL_DAY);
        $this->incrementStat('pushed');

        log_message('info', "[Queue] Job {$jobId} pushed to '{$queue}': {$jobClass}");
        return $jobId;
    }

    /**
     * Pop the next pending job from a queue
     */
    public function pop(string $queue): ?array
    {
        $jobs = $this->getQueueJobs($queue);
        $now = date('Y-m-d H:i:s');

        foreach ($jobs as $i => $job) {
            if ($job['status'] !== 'pending') continue;
            if ($job['run_after'] && $job['run_after'] > $now) continue;

            $jobs[$i]['status'] = 'processing';
            $jobs[$i]['started_at'] = $now;
            $this->cache->set(self::QUEUE_PREFIX . $queue, $jobs, CacheManager::TTL_DAY);
            return $jobs[$i];
        }
        return null;
    }

    /**
     * Mark a job as completed
     */
    public function complete(string $queue, string $jobId): void
    {
        $jobs = $this->getQueueJobs($queue);
        foreach ($jobs as $i => $job) {
            if ($job['id'] === $jobId) {
                $jobs[$i]['status'] = 'completed';
                $jobs[$i]['completed_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        $this->cache->set(self::QUEUE_PREFIX . $queue, $jobs, CacheManager::TTL_DAY);
        $this->incrementStat('processed');
    }

    /**
     * Mark a job as failed
     */
    public function fail(string $queue, string $jobId, string $error): void
    {
        $jobs = $this->getQueueJobs($queue);
        foreach ($jobs as $i => $job) {
            if ($job['id'] !== $jobId) continue;

            $jobs[$i]['attempts']++;
            if ($jobs[$i]['attempts'] < $jobs[$i]['max_tries']) {
                // Retry with exponential backoff
                $delay = pow(2, $jobs[$i]['attempts']) * 30;
                $jobs[$i]['status'] = 'pending';
                $jobs[$i]['run_after'] = date('Y-m-d H:i:s', time() + $delay);
                $jobs[$i]['last_error'] = $error;
                log_message('warning', "[Queue] Job {$jobId} failed, retry #{$jobs[$i]['attempts']} in {$delay}s");
            } else {
                // Move to failed
                $jobs[$i]['status'] = 'failed';
                $jobs[$i]['last_error'] = $error;
                $failed = $this->cache->get(self::FAILED_KEY, []);
                $failed[] = $jobs[$i];
                $this->cache->set(self::FAILED_KEY, $failed, CacheManager::TTL_DAY);
                $this->incrementStat('failed');
                log_message('error', "[Queue] Job {$jobId} permanently failed: {$error}");
            }
            break;
        }
        $this->cache->set(self::QUEUE_PREFIX . $queue, $jobs, CacheManager::TTL_DAY);
    }

    /**
     * Get queue size (pending jobs)
     */
    public function size(string $queue): int
    {
        return count(array_filter(
            $this->getQueueJobs($queue),
            fn($j) => $j['status'] === 'pending'
        ));
    }

    /**
     * Get all jobs in a queue with optional status filter
     */
    public function getJobs(string $queue, ?string $status = null): array
    {
        $jobs = $this->getQueueJobs($queue);
        if ($status) {
            $jobs = array_filter($jobs, fn($j) => $j['status'] === $status);
        }
        return array_values($jobs);
    }

    /**
     * Get failed jobs
     */
    public function getFailedJobs(): array
    {
        return $this->cache->get(self::FAILED_KEY, []);
    }

    /**
     * Retry a failed job
     */
    public function retryFailed(string $jobId): bool
    {
        $failed = $this->getFailedJobs();
        foreach ($failed as $i => $job) {
            if ($job['id'] !== $jobId) continue;

            $job['status'] = 'pending';
            $job['attempts'] = 0;
            $job['run_after'] = null;
            unset($job['last_error']);

            // Push back to original queue
            $jobs = $this->getQueueJobs($job['queue']);
            $jobs[] = $job;
            $this->cache->set(self::QUEUE_PREFIX . $job['queue'], $jobs, CacheManager::TTL_DAY);

            // Remove from failed
            unset($failed[$i]);
            $this->cache->set(self::FAILED_KEY, array_values($failed), CacheManager::TTL_DAY);
            return true;
        }
        return false;
    }

    /**
     * Purge completed jobs from a queue
     */
    public function purgeCompleted(string $queue): int
    {
        $jobs = $this->getQueueJobs($queue);
        $before = count($jobs);
        $jobs = array_filter($jobs, fn($j) => $j['status'] !== 'completed');
        $this->cache->set(self::QUEUE_PREFIX . $queue, array_values($jobs), CacheManager::TTL_DAY);
        return $before - count($jobs);
    }

    /**
     * Get queue system statistics
     */
    public function getStats(): array
    {
        $stats = $this->cache->get(self::STATS_KEY, ['pushed' => 0, 'processed' => 0, 'failed' => 0]);
        $queues = ['default', 'emails', 'rankings', 'data-processing'];
        $queueSizes = [];
        foreach ($queues as $q) {
            $queueSizes[$q] = [
                'pending'    => count(array_filter($this->getQueueJobs($q), fn($j) => $j['status'] === 'pending')),
                'processing' => count(array_filter($this->getQueueJobs($q), fn($j) => $j['status'] === 'processing')),
                'completed'  => count(array_filter($this->getQueueJobs($q), fn($j) => $j['status'] === 'completed')),
            ];
        }
        return [
            'totals'  => $stats,
            'queues'  => $queueSizes,
            'failed'  => count($this->getFailedJobs()),
        ];
    }

    private function getQueueJobs(string $queue): array
    {
        return $this->cache->get(self::QUEUE_PREFIX . $queue, []);
    }

    private function incrementStat(string $type): void
    {
        $stats = $this->cache->get(self::STATS_KEY, ['pushed' => 0, 'processed' => 0, 'failed' => 0]);
        $stats[$type] = ($stats[$type] ?? 0) + 1;
        $this->cache->set(self::STATS_KEY, $stats, CacheManager::TTL_DAY);
    }
}
