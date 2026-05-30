<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOwnAccountFieldsToWithdrawalRequests extends Migration
{
    public function up()
    {
        $this->forge->addColumn('withdrawal_requests', [
            'account_holder' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'after' => 'target_account',
            ],
            'account_document' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'account_holder',
            ],
            'own_account_confirmed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'account_document',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('withdrawal_requests', ['account_holder', 'account_document', 'own_account_confirmed']);
    }
}
