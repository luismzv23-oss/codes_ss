<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Rescue extends Controller
{
    public function index()
    {
        $output = shell_exec('cd ' . escapeshellarg(ROOTPATH) . ' && git checkout app/Views/dashboard/partials/events.php 2>&1');
        return $this->response->setBody("Git output: " . $output);
    }
}
