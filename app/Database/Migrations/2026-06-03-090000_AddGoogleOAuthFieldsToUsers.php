<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGoogleOAuthFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'google_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
                'after'      => 'email',
            ],
            'oauth_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'google_id',
            ],
        ];

        $this->forge->addColumn('users', $fields);
        $this->db->query('CREATE UNIQUE INDEX users_google_id_unique ON users (google_id)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX users_google_id_unique ON users');
        $this->forge->dropColumn('users', ['google_id', 'oauth_provider']);
    }
}
