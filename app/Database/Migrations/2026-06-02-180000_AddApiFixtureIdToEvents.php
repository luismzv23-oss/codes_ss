<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiFixtureIdToEvents extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('api_fixture_id', 'events')) {
            $this->forge->addColumn('events', [
                'api_fixture_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'unique' => true,
                    'after' => 'league_id'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('api_fixture_id', 'events')) {
            $this->forge->dropColumn('events', 'api_fixture_id');
        }
    }
}
