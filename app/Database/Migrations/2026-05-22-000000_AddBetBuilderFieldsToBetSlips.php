<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBetBuilderFieldsToBetSlips extends Migration
{
    public function up()
    {
        $fields = [
            'is_builder' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'status'
            ],
            'correlation_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'after'      => 'is_builder'
            ]
        ];
        $this->forge->addColumn('bet_slips', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('bet_slips', 'is_builder');
        $this->forge->dropColumn('bet_slips', 'correlation_discount');
    }
}
