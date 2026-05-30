<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuspensionsTable extends Migration
{
    public function up()
    {
        // User Suspensions Table
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'reason'           => ['type' => 'VARCHAR', 'constraint' => '255'],
            'suspension_type'  => ['type' => 'ENUM', 'constraint' => ['temporary', 'permanent'], 'default' => 'temporary'],
            'suspended_at'     => ['type' => 'DATETIME'],
            'expires_at'       => ['type' => 'DATETIME', 'null' => true],
            'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'       => ['type' => 'INT', 'unsigned' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addKey('user_id');
        $this->forge->addKey('is_active');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('suspensions', true);
    }

    public function down()
    {
        $this->forge->dropTable('suspensions', true);
    }
}
