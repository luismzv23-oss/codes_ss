<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginAttemptsTable extends Migration
{
    public function up()
    {
        // Login Attempts Table (para rate limiting y detección de fraude)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'email'        => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => '45'],
            'user_agent'   => ['type' => 'TEXT', 'null' => true],
            'success'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'failed_reason' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'attempted_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addKey('ip_address');
        $this->forge->addKey('email');
        $this->forge->addKey('attempted_at');
        $this->forge->createTable('login_attempts', true);
    }

    public function down()
    {
        $this->forge->dropTable('login_attempts', true);
    }
}
