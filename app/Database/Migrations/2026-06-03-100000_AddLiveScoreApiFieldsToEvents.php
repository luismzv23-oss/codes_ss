<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLiveScoreApiFieldsToEvents extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('api_provider', 'events')) {
            $fields['api_provider'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'api_fixture_id',
            ];
        }

        if (! $this->db->fieldExists('api_status', 'events')) {
            $fields['api_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'api_provider',
            ];
        }

        if (! $this->db->fieldExists('api_elapsed', 'events')) {
            $fields['api_elapsed'] = [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => true,
                'after'      => 'api_status',
            ];
        }

        if (! $this->db->fieldExists('api_last_score_sync_at', 'events')) {
            $fields['api_last_score_sync_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'api_elapsed',
            ];
        }

        if (! $this->db->fieldExists('score_source', 'events')) {
            $fields['score_source'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'api_last_score_sync_at',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('events', $fields);
        }
    }

    public function down()
    {
        $columns = [];
        foreach (['api_provider', 'api_status', 'api_elapsed', 'api_last_score_sync_at', 'score_source'] as $column) {
            if ($this->db->fieldExists($column, 'events')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('events', $columns);
        }
    }
}
