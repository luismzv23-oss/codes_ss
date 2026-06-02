<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\I18n\Time;

class AddEmailVerificationFields extends Migration
{
    public function up()
    {
        $fields = [
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'email_verification_token' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'email_verification_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];
        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'email_verified_at');
        $this->forge->dropColumn('users', 'email_verification_token');
        $this->forge->dropColumn('users', 'email_verification_sent_at');
    }
}
?>
