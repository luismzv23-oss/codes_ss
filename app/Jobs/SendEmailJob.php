<?php

namespace App\Jobs;

/**
 * Simulated email sending job.
 * In production, integrate with SMTP/SendGrid/AWS SES.
 */
class SendEmailJob extends BaseJob
{
    public function handle(): void
    {
        $to      = $this->payload['to'] ?? 'unknown';
        $subject = $this->payload['subject'] ?? 'No Subject';
        $body    = $this->payload['body'] ?? '';

        // Simulate processing time
        usleep(200000); // 200ms

        log_message('info', "[EmailJob] Sent email to {$to}: {$subject}");

        // In production:
        // $email = \Config\Services::email();
        // $email->setTo($to);
        // $email->setSubject($subject);
        // $email->setMessage($body);
        // if (!$email->send()) throw new \Exception($email->printDebugger());
    }
}
