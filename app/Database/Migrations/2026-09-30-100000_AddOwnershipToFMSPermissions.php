<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddOwnershipToFMSPermissions extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('c_permissions', [
            'is_system' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'is_active',
            ],
            'source_menu_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'is_system',
            ],
        ]);

        $this->db->table('c_permissions')
            ->where('created_by', 1)
            ->where('permission_key !=', '*')
            ->update(['is_system' => 1]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('c_permissions', ['source_menu_id', 'is_system']);
    }
}
