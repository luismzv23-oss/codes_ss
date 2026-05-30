<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSettlementFieldsToTables extends Migration
{
    public function up()
    {
        // Add `settled` to events
        $fields = [
            'settled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'status'
            ],
        ];
        $this->forge->addColumn('events', $fields);

        // Add `status` to odds (pending, won, lost, void)
        $oddFields = [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'won', 'lost', 'void'],
                'default'    => 'pending',
                'after'      => 'active'
            ],
        ];
        $this->forge->addColumn('odds', $oddFields);
    }

    public function down()
    {
        $this->forge->dropColumn('events', 'settled');
        $this->forge->dropColumn('odds', 'status');
    }
}
