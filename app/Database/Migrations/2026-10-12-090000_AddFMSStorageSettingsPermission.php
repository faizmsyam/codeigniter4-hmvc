<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan permission storage.settings (menu Pengaturan) ke instalasi
 * Storage yang sudah ada, lalu menyinkronkan role super_admin agar
 * mencakup seluruh permission yang dikenali modul Storage.
 *
 * Idempoten: aman dijalankan berulang kali.
 */
final class AddFMSStorageSettingsPermission extends Migration
{
    public function up(): void
    {
        $db = $this->db;
        $now = date('Y-m-d H:i:s');

        $exists = $db->table('storage_permissions')->where('permission_key', 'storage.settings')->countAllResults();
        if ($exists === 0) {
            $db->table('storage_permissions')->insert([
                'permission_key' => 'storage.settings',
                'description' => 'FMS Storage: storage.settings',
                'created_at' => $now,
            ]);
        }

        /* Sinkronkan super_admin: beri seluruh permission yang dikenali. */
        $all = ['storage.read', 'storage.upload', 'storage.edit', 'storage.delete', 'storage.share', 'storage.users', 'storage.settings'];
        $role = $db->table('storage_roles')->where('name', 'super_admin')->get(1)->getRowArray();
        if (is_array($role)) {
            $db->table('storage_roles')->where('id', (int) $role['id'])->update([
                'permissions_json' => json_encode($all, JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->db->table('storage_permissions')->where('permission_key', 'storage.settings')->delete();
    }
}
