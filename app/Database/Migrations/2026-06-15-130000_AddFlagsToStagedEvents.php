<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFlagsToStagedEvents extends Migration
{
    public function up()
    {
        $fields = [];
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->fieldExists('home_flag', 'staged_events')) {
            $fields['home_flag'] = [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => true,
                'after'      => 'home_team',
            ];
        }

        if (! $db->fieldExists('away_flag', 'staged_events')) {
            $fields['away_flag'] = [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => true,
                'after'      => 'away_team',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('staged_events', $fields);
        }
    }

    public function down()
    {
        $columns = [];
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        foreach (['home_flag', 'away_flag'] as $column) {
            if ($db->fieldExists($column, 'staged_events')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('staged_events', $columns);
        }
    }
}
