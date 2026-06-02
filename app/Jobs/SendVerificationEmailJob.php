<?php
namespace App\Jobs;

use CodeIgniter\Queue\JobInterface;
use Config\Services;

class SendVerificationEmailJob implements JobInterface
{
    public function handle(array $payload)
    {
        $email = Services::email();
        $email->setTo($payload['to']);
        $email->setSubject('Verifica tu cuenta en Codex SS');
        $email->setMailtype('html');
        $link = base_url('auth/verify/' . $payload['token']);
        $message = view('emails/verify_account', [
            'name' => $payload['name'],
            'link' => $link,
        ]);
        $email->setMessage($message);
        $email->send();
    }
}
?>
