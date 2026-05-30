<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiSportKeyToLeagues extends Migration
{
    public function up()
    {
        $this->forge->addColumn('leagues', [
            'api_sport_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'icon',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('leagues', 'api_sport_key');
    }
}
