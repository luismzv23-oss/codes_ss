<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ]
        ]);
        $this->forge->addKey('key', true);
        $this->forge->createTable('system_settings', true);

        // Seed default settings
        $db = \Config\Database::connect();
        
        // Only insert if empty
        $count = $db->table('system_settings')->countAllResults();
        if ($count === 0) {
            $db->table('system_settings')->insertBatch([
                ['key' => 'platform_name', 'value' => 'Codex SS'],
                ['key' => 'base_url', 'value' => 'http://localhost:8080'],
                ['key' => 'timezone', 'value' => 'America/Argentina/Buenos_Aires'],
                ['key' => 'security_2fa', 'value' => '1'],
                ['key' => 'security_lockout', 'value' => '1'],
                ['key' => 'security_sessions', 'value' => '0'],
                ['key' => 'notify_email', 'value' => '1'],
                ['key' => 'notify_security', 'value' => '1'],
                ['key' => 'notify_marketing', 'value' => '0'],
                ['key' => 'bank_name', 'value' => 'Banco Galicia'],
                ['key' => 'bank_holder', 'value' => 'Codex SS S.A.'],
                ['key' => 'bank_cbu_cvu', 'value' => '0070001230004567891234'],
                ['key' => 'bank_alias', 'value' => 'codex.ss.transfer'],
                ['key' => 'qr_code_path', 'value' => '']
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('system_settings', true);
    }
}
