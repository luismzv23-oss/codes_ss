<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSortOrderToLeagues extends Migration
{
    public function up()
    {
        $this->forge->addColumn('leagues', [
            'sort_order' => [
                'type' => 'INT',
                'unsigned' => true,
                'default' => 0,
                'after' => 'active'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('leagues', 'sort_order');
    }
}
