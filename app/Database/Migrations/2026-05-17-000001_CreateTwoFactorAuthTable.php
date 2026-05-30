<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTwoFactorAuthTable extends Migration
{
    public function up()
    {
        // Two Factor Auth Table
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'method'       => ['type' => 'ENUM', 'constraint' => ['totp', 'sms'], 'default' => 'totp'],
            'secret'       => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'backup_codes' => ['type' => 'JSON', 'null' => true],
            'is_verified'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'verified_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('two_factor_auths', true);
    }

    public function down()
    {
        $this->forge->dropTable('two_factor_auths', true);
    }
}
