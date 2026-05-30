<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMpCardSettings extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('system_settings');
        
        $keys = [
            ['key' => 'mp_public_key', 'value' => ''],
            ['key' => 'mp_card_enabled', 'value' => '0'],
        ];

        foreach ($keys as $row) {
            $exists = $builder->where('key', $row['key'])->countAllResults();
            if ($exists === 0) {
                $builder->insert($row);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('system_settings');
        $builder->whereIn('key', ['mp_public_key', 'mp_card_enabled'])->delete();
    }
}
