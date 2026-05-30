<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKYCVerificationsTable extends Migration
{
    public function up()
    {
        // KYC Verification Table
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'document_type'    => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true], // passport, dni, license
            'document_number'  => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'document_image'   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'verified_at'      => ['type' => 'DATETIME', 'null' => true],
            'verified_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'rejection_reason' => ['type' => 'TEXT', 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('verified_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('kyc_verifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('kyc_verifications', true);
    }
}
