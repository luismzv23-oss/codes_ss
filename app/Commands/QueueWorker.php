<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\QueueManager;

/**
 * Queue Worker — Processes jobs from named queues.
 * Usage:
 *   php spark queue:work              # Process all queues once
 *   php spark queue:work --queue=emails  # Process only 'emails' queue
 *   php spark queue:work --daemon     # Run continuously (like Redis worker)
 *   php spark queue:work --seed       # Push demo jobs for testing
 */
class QueueWorker extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:work';
    protected $description = 'Process jobs from the queue system.';
    protected $usage       = 'queue:work [--queue=<name>] [--daemon] [--seed]';
    protected $arguments   = [];
    protected $options     = [
        '--queue'  => 'Queue name to process (default: all)',
        '--daemon' => 'Run continuously in daemon mode',
        '--seed'   => 'Push demo jobs for testing',
    ];

    private const QUEUES = ['default', 'emails', 'rankings', 'data-processing'];

    public function run(array $params)
    {
        $queue   = $params['queue'] ?? CLI::getOption('queue');
        $daemon  = CLI::getOption('daemon');
        $seed    = CLI::getOption('seed');

        $manager = new QueueManager();

        // ── Seed demo jobs ──
        if ($seed) {
            $this->seedDemoJobs($manager);
            return;
        }

        $queues = $queue ? [$queue] : self::QUEUES;

        CLI::write('╔══════════════════════════════════════╗', 'cyan');
        CLI::write('║   Codex SS — Queue Worker Started    ║', 'cyan');
        CLI::write('╚══════════════════════════════════════╝', 'cyan');
        CLI::write('Queues: ' . implode(', ', $queues), 'yellow');
        CLI::write('Mode: ' . ($daemon ? 'Daemon (continuous)' : 'Single pass'), 'yellow');
        CLI::newLine();

        do {
            $processed = 0;
            foreach ($queues as $qName) {
                $job = $manager->pop($qName);
                if (!$job) continue;

                $processed++;
                CLI::write("[{$job['queue']}] Processing: {$job['class']} ({$job['id']})", 'light_blue');

                try {
                    $class = $job['class'];
                    if (!class_exists($class)) {
                        throw new \Exception("Job class not found: {$class}");
                    }

                    /** @var \App\Jobs\BaseJob $instance */
                    $instance = new $class($job['payload']);
                    $instance->handle();

                    $manager->complete($qName, $job['id']);
                    CLI::write("  ✓ Completed in " . $this->elapsed($job['started_at']), 'green');

                } catch (\Throwable $e) {
                    $manager->fail($qName, $job['id'], $e->getMessage());
                    CLI::write("  ✗ Failed: {$e->getMessage()}", 'red');
                }
            }

            if ($daemon) {
                if ($processed === 0) {
                    // No jobs — sleep before polling again
                    CLI::write('.', 'dark_gray');
                    sleep(2);
                }
            }
        } while ($daemon);

        // ── Print summary ──
        CLI::newLine();
        $stats = $manager->getStats();
        CLI::write('── Queue Stats ──', 'yellow');
        CLI::write("Total pushed:    {$stats['totals']['pushed']}", 'white');
        CLI::write("Total processed: {$stats['totals']['processed']}", 'green');
        CLI::write("Total failed:    {$stats['totals']['failed']}", 'red');
        foreach ($stats['queues'] as $name => $sizes) {
            CLI::write("  [{$name}] pending: {$sizes['pending']} | completed: {$sizes['completed']}", 'light_gray');
        }
    }

    private function seedDemoJobs(QueueManager $manager): void
    {
        CLI::write('Seeding demo jobs...', 'yellow');

        // Email jobs
        $emails = [
            ['to' => 'carlos@email.com', 'subject' => 'Bienvenido a Codex SS', 'body' => 'Tu cuenta ha sido creada.'],
            ['to' => 'maria@email.com', 'subject' => 'Depósito confirmado', 'body' => 'Tu depósito de $1,500 fue acreditado.'],
            ['to' => 'lucia@email.com', 'subject' => 'Apuesta ganada', 'body' => '¡Felicidades! Ganaste $2,400 en Champions League Final.'],
        ];
        foreach ($emails as $e) {
            $manager->push('emails', \App\Jobs\SendEmailJob::class, $e);
        }
        CLI::write("  ✓ 3 email jobs pushed", 'green');

        // Rankings recalculation
        $manager->push('rankings', \App\Jobs\ProcessRankingsJob::class, ['type' => 'all']);
        CLI::write("  ✓ 1 rankings job pushed", 'green');

        // Data processing
        $reports = ['daily_summary', 'weekly_report', 'top_events'];
        foreach ($reports as $r) {
            $manager->push('data-processing', \App\Jobs\DataProcessingJob::class, ['report' => $r]);
        }
        CLI::write("  ✓ 3 data processing jobs pushed", 'green');

        CLI::newLine();
        CLI::write('Done! Run `php spark queue:work` to process them.', 'cyan');
    }

    private function elapsed(string $start): string
    {
        $ms = (microtime(true) - strtotime($start)) * 1000;
        return round($ms) . 'ms';
    }
}
