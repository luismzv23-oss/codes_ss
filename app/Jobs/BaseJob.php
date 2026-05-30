<?php

namespace App\Jobs;

/**
 * Base contract for all queue jobs.
 */
abstract class BaseJob
{
    protected array $payload;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job logic. Must be implemented by subclasses.
     * @throws \Exception on failure (will be retried)
     */
    abstract public function handle(): void;

    /**
     * Called when the job permanently fails after all retries.
     */
    public function failed(\Throwable $e): void
    {
        log_message('error', static::class . ' permanently failed: ' . $e->getMessage());
    }
}
