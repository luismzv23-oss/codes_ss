<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyReferenceIdInTransactions extends Migration
{
    public function up()
    {
        $fields = [
            'reference_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
        ];
        $this->forge->modifyColumn('transactions', $fields);
    }

    public function down()
    {
        $fields = [
            'reference_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ];
        $this->forge->modifyColumn('transactions', $fields);
    }
}
