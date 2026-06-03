<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashedOutAmountToBetSlips extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bet_slips', [
            'cashed_out_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'potential_payout'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bet_slips', 'cashed_out_amount');
    }
}
