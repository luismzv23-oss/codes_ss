<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWithdrawalRequestsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'wallet_id' => ['type' => 'INT', 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'target_account' => ['type' => 'VARCHAR', 'constraint' => 160],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'user_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'admin_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'processed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('wallet_id', 'wallets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('withdrawal_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('withdrawal_requests', true);
    }
}
