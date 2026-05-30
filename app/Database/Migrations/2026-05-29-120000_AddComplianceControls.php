<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComplianceControls extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'daily_deposit_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'monthly_deposit_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'daily_loss_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'monthly_loss_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'session_limit_minutes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'self_excluded_until' => ['type' => 'DATETIME', 'null' => true],
            'self_exclusion_reason' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('responsible_gaming_limits', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => '45'],
            'country_code' => ['type' => 'VARCHAR', 'constraint' => '2', 'null' => true],
            'allowed' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reason' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('geo_access_logs', true);

        $fields = [
            'cashout_value' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true, 'after' => 'correlation_discount'],
            'cashed_out_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'cashout_value'],
        ];
        $this->forge->addColumn('bet_slips', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('bet_slips', ['cashout_value', 'cashed_out_at']);
        $this->forge->dropTable('geo_access_logs', true);
        $this->forge->dropTable('responsible_gaming_limits', true);
    }
}
