<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddSettingsMenuAndPermissions extends Migration
{
    public function up(): void
    {
        $db = $this->db;
        $ts = date('Y-m-d H:i:s');

        $db->transStart();

        /* ── 1. Daftarkan permission modul settings (gate: settings.read) ─ */
        $permTable = $db->table('c_permissions');

        $readPerm = $permTable->where('permission_key', 'settings.read')->get()->getRowArray();
        if ($readPerm === null) {
            $permTable->insert([
                'uuid'           => $this->uuid4(),
                'permission_key' => 'settings.read',
                'module_name'    => 'settings',
                'action_name'    => 'read',
                'description'    => 'Hak melihat menu dan modul konfigurasi sistem (khusus Super Administrator).',
                'is_active'      => 1,
                'is_system'      => 1,
                'created_by'     => 1,
                'created_at'     => $ts,
            ]);
            $readPermId = (int) $db->insertID();
        } else {
            $readPermId = (int) $readPerm['id'];
        }

        $updatePerm = $permTable->where('permission_key', 'settings.update')->get()->getRowArray();
        if ($updatePerm === null) {
            $permTable->insert([
                'uuid'           => $this->uuid4(),
                'permission_key' => 'settings.update',
                'module_name'    => 'settings',
                'action_name'    => 'update',
                'description'    => 'Hak mengubah konfigurasi sistem, API key, dan Basic Auth (khusus Super Administrator).',
                'is_active'      => 1,
                'is_system'      => 0,
                'created_by'     => 1,
                'created_at'     => $ts,
            ]);
        }

        /* ── 2. Daftarkan menu Konfigurasi di bawah Control Panel ─────── */
        $menuTable = $db->table('c_menus');
        $existingMenu = $menuTable->where('url', 'settings')->get()->getRowArray();

        if ($existingMenu === null) {
            $menuTable->insert([
                'id_parent'    => 2, // Control Panel
                'name'         => 'Konfigurasi',
                'url'          => 'settings',
                'position'     => 8,
                'icon'         => 'ph-duotone ph-sliders',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
                'created_at'   => $ts,
            ]);
            $menuId = (int) $db->insertID();
        } else {
            $menuId = (int) $existingMenu['id'];
        }

        /* ── 3. Hubungkan menu Konfigurasi dengan permission gate ──────── */
        $mpTable = $db->table('t_menu_permissions');
        $existsMapping = $mpTable->where('menu_id', $menuId)->where('permission_id', $readPermId)->countAllResults() > 0;
        if (! $existsMapping) {
            $mpTable->insert([
                'menu_id'       => $menuId,
                'permission_id' => $readPermId,
                'created_by'    => 1,
                'created_at'    => $ts,
            ]);
        }

        $db->transComplete();
    }

    public function down(): void
    {
        $db = $this->db;
        $db->transStart();

        $menu = $db->table('c_menus')->where('url', 'settings')->get()->getRowArray();
        if ($menu !== null) {
            $menuId = (int) $menu['id'];
            $db->table('t_menu_permissions')->where('menu_id', $menuId)->delete();
            $db->table('c_menus')->where('id', $menuId)->delete();
        }

        $db->table('c_permissions')->whereIn('permission_key', ['settings.read', 'settings.update'])->delete();

        $db->transComplete();
    }

    private function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
