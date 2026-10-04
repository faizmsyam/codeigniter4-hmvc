<?php

namespace App\Database\Migrations;

use App\Database\Seeds\FMSPrivilegesPermissionSeeder;
use App\Database\Seeds\FMSPrivilegesDefaultGroupSeeder;
use App\Database\Seeds\FMSProgrammerSuperAdminSeeder;
use App\Database\Seeds\FMSAdminDemoUserSeeder;
use CodeIgniter\Database\Migration;

/**
 * Migration ini menjalankan seed default setelah semua tabel tersedia.
 *
 * Efek: `migrate:refresh` langsung menghasilkan DB siap pakai tanpa
 * perlu menjalankan `db:seed` secara manual.
 *
 * Idempoten: masing-masing seeder cek data sebelum insert.
 */
final class SeedFMSDefaults extends Migration
{
    public function up(): void
    {
        $config = new \Config\Database();
        $db     = $this->db;

        (new FMSPrivilegesPermissionSeeder($config, $db))->run();
        (new FMSPrivilegesDefaultGroupSeeder($config, $db))->run();
        (new FMSProgrammerSuperAdminSeeder($config, $db))->run();
        (new FMSAdminDemoUserSeeder($config, $db))->run();
    }

    public function down(): void
    {
        /* Rollback: bersihkan data yang di-seed; tabel dihapus oleh migration lain */
        $this->db->disableForeignKeyChecks();
        $this->db->table('t_group_permissions')->truncate();
        $this->db->table('t_menu_permissions')->truncate();
        $this->db->table('c_permissions')->truncate();
        $this->db->table('t_user_sessions')->truncate();
        $this->db->table('t_user_groups')->truncate();
        $this->db->table('m_users')->truncate();
        $this->db->table('c_group_users')->truncate();
        $this->db->enableForeignKeyChecks();
    }
}
