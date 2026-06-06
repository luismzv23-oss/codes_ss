<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddScoresToStagedEvents extends Migration
{
    public function up()
    {
        $fields = [];
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->fieldExists('score_home', 'staged_events')) {
            $fields['score_home'] = [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'away_team',
            ];
        }

        if (! $db->fieldExists('score_away', 'staged_events')) {
            $fields['score_away'] = [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'score_home',
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

        foreach (['score_home', 'score_away'] as $column) {
            if ($db->fieldExists($column, 'staged_events')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('staged_events', $columns);
        }
    }
}
