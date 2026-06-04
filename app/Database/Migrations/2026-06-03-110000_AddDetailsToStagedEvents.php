<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetailsToStagedEvents extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('stage', 'staged_events')) {
            $fields['stage'] = [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'league_country',
            ];
        }

        if (! $this->db->fieldExists('group_name', 'staged_events')) {
            $fields['group_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'stage',
            ];
        }

        if (! $this->db->fieldExists('venue', 'staged_events')) {
            $fields['venue'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'start_time',
            ];
        }

        if (! $this->db->fieldExists('venue_url', 'staged_events')) {
            $fields['venue_url'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'venue',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('staged_events', $fields);
        }
    }

    public function down()
    {
        $columns = [];
        foreach (['stage', 'group_name', 'venue', 'venue_url'] as $column) {
            if ($this->db->fieldExists($column, 'staged_events')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('staged_events', $columns);
        }
    }
}
