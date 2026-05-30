<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorldCupEventDetails extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('stage', 'events')) {
            $fields['stage'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'league_id',
            ];
        }

        if (! $this->db->fieldExists('group_name', 'events')) {
            $fields['group_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'stage',
            ];
        }

        if (! $this->db->fieldExists('match_number', 'events')) {
            $fields['match_number'] = [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'group_name',
            ];
        }

        if (! $this->db->fieldExists('home_flag', 'events')) {
            $fields['home_flag'] = [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => true,
                'after'      => 'home_team',
            ];
        }

        if (! $this->db->fieldExists('away_flag', 'events')) {
            $fields['away_flag'] = [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => true,
                'after'      => 'away_team',
            ];
        }

        if (! $this->db->fieldExists('venue', 'events')) {
            $fields['venue'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'start_time',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('events', $fields);
        }
    }

    public function down()
    {
        foreach (['stage', 'group_name', 'match_number', 'home_flag', 'away_flag', 'venue'] as $field) {
            if ($this->db->fieldExists($field, 'events')) {
                $this->forge->dropColumn('events', $field);
            }
        }
    }
}
