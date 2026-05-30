<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWalletTables extends Migration
{
    public function up()
    {
        // 1. Wallets
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'unique' => true],
            'balance'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'currency'   => ['type' => 'VARCHAR', 'constraint' => '3', 'default' => 'USD'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wallets');

        // 2. Transactions
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'wallet_id'        => ['type' => 'INT', 'unsigned' => true],
            'type'             => ['type' => 'ENUM', 'constraint' => ['deposit', 'withdrawal', 'bet_placed', 'bet_won', 'bet_refunded']],
            'amount'           => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'balance_after'    => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'reference_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true], // e.g. bet_slip_id
            'description'      => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('wallet_id', 'wallets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transactions');
    }

    public function down()
    {
        $this->forge->dropTable('transactions');
        $this->forge->dropTable('wallets');
    }
}
