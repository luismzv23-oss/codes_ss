<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandUsersTableSecurityFields extends Migration
{
    public function up()
    {
        // Agregar campos de seguridad y KYC a tabla users
        $fields = [
            'phone'           => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
                'after'      => 'email'
            ],
            'country'         => [
                'type'       => 'VARCHAR',
                'constraint' => '2',
                'null'       => true,
                'after'      => 'phone'
            ],
            'birthdate'       => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'country'
            ],
            'document_type'   => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'birthdate'
            ],
            'document_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'document_type'
            ],
            'kyc_status'      => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
                'after'      => 'document_number'
            ],
            'is_2fa_enabled'  => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'kyc_status'
            ],
            'last_login_at'   => [
                'type'       => 'DATETIME',
                'null'       => true,
                'after'      => 'is_2fa_enabled'
            ],
            'last_login_ip'   => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
                'after'      => 'last_login_at'
            ],
            'failed_login_attempts' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'last_login_ip'
            ],
            'locked_until'    => [
                'type'       => 'DATETIME',
                'null'       => true,
                'after'      => 'failed_login_attempts'
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $columnsToRemove = [
            'phone',
            'country',
            'birthdate',
            'document_type',
            'document_number',
            'kyc_status',
            'is_2fa_enabled',
            'last_login_at',
            'last_login_ip',
            'failed_login_attempts',
            'locked_until'
        ];

        $this->forge->dropColumn('users', $columnsToRemove);
    }
}
