<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCommissionAndTargetToTransactions extends Migration
{
    public function up()
    {
        $fields = [
            'commission' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
                'null'       => false,
            ],
            'target_account' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
        ];
        $this->forge->addColumn('transactions', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', 'commission');
        $this->forge->dropColumn('transactions', 'target_account');
    }
}
