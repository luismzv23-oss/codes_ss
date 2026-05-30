<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthSeeder extends Seeder
{
    public function run()
    {
        // Insert Roles
        $roles = [
            [
                'id' => 1,
                'name' => 'Admin',
                'description' => 'System Administrator'
            ],
            [
                'id' => 2,
                'name' => 'User',
                'description' => 'Standard User'
            ]
        ];

        // Only insert if not exists
        if ($this->db->table('roles')->countAll() === 0) {
            $this->db->table('roles')->insertBatch($roles);
        }

        // Insert Admin User
        if ($this->db->table('users')->where('username', 'admin')->countAllResults() === 0) {
            $data = [
                'role_id'       => 1,
                'username'      => 'admin',
                'email'         => 'admin@codexss.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];

            $this->db->table('users')->insert($data);
        }
    }
}
