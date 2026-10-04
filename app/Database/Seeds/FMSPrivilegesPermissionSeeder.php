<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 *
 * Katalog permission operasional FMS.
 *
 * KONVENSI:
 *   - 'read' / 'view' = is_system=1 (Izin dasar membuka halaman / menu)
 *   - Semua action lain = is_system=0 (Masuk ke "Hal yang boleh dilakukan" di Privileges Matrix)
 *   - Seluruh permission dipetakan ke menu_id masing-masing via t_menu_permissions
 */
final class FMSPrivilegesPermissionSeeder extends Seeder
{
    /**
     * Module => [actions...]
     * Urutan modul mengikuti urutan menu di sidebar:
     * Dashboard, Control Panel (Brand, Admin Menus, User Groups, Privileges, Users, Profil Saya, Activity Logs).
     * Urutan action: read/view dulu, create, update, delete, restore, lalu aksi khusus alfabet.
     *
     * @var array<string, list<string>>
     */
    private const MODULE_ACTIONS = [
        'dashboard' => ['read'],
        'brand' => ['read', 'update'],
        'menus' => ['view', 'create', 'update', 'delete', 'reorder'],
        'user_groups' => ['read', 'create', 'update', 'delete', 'restore'],
        'privileges' => ['manage', 'create', 'update', 'delete'],
        'users' => [
            'read',
            'create',
            'update',
            'delete',
            'restore',
            'assign_groups',
            'change_status',
            'read_groups',
            'read_sessions',
            'reset_password',
            'revoke_sessions',
            'unlock',
            'unverify_email',
            'verify_email',
        ],
        'profile' => ['read', 'update', 'update_avatar', 'change_password'],
        'activity_logs' => ['read', 'export'],
    ];

    /**
     * Menu ID => module_name
     * Mengikuti urutan menu aktual (c_menus):
     *   1 = Dashboard
     *   3 = Brand
     *   4 = Admin Menus
     *   5 = User Groups
     *   6 = Privileges
     *   7 = Users
     *   8 = Profil Saya
     *   9 = Activity Logs
     *
     * @var array<int, string>
     */
    private const MENU_MODULE_MAP = [
        1 => 'dashboard',
        3 => 'brand',
        4 => 'menus',
        5 => 'user_groups',
        6 => 'privileges',
        7 => 'users',
        8 => 'profile',
        9 => 'activity_logs',
    ];

    /**
     * Actions yang berfungsi sebagai izin dasar membuka menu (is_system = 1).
     * Semua action lain otomatis is_system = 0.
     *
     * @var list<string>
     */
    private const SYSTEM_GATE_ACTIONS = ['read', 'view', 'manage'];

    public function run(): void
    {
        $db        = $this->db;
        $ts        = date('Y-m-d H:i:s');
        $permTable = $db->table('c_permissions');
        $mpTable   = $db->table('t_menu_permissions');

        $db->transStart();

        /* ── 1. Upsert seluruh permission katalog ────────────────── */
        $permissionIds = [];

        foreach (self::MODULE_ACTIONS as $module => $actions) {
            foreach ($actions as $action) {
                $key      = $module . '.' . $action;
                $isSystem = in_array($action, self::SYSTEM_GATE_ACTIONS, true) ? 1 : 0;

                $existing = $permTable->where('permission_key', $key)->get()->getRowArray();

                if ($existing === null) {
                    $permTable->insert([
                        'uuid'           => $this->uuid4(),
                        'permission_key' => $key,
                        'module_name'    => $module,
                        'action_name'    => $action,
                        'description'    => 'Hak ' . str_replace('_', ' ', $action)
                                          . ' pada modul ' . str_replace('_', ' ', $module) . '.',
                        'is_active'      => 1,
                        'is_system'      => $isSystem,
                        'created_by'     => 1,
                        'created_at'     => $ts,
                    ]);
                    $permissionIds[$key] = (int) $db->insertID();
                } else {
                    $permissionIds[$key] = (int) $existing['id'];
                    $permTable->where('id', (int) $existing['id'])->update([
                        'module_name' => $module,
                        'action_name' => $action,
                        'is_active'   => 1,
                        'is_system'   => $isSystem,
                        'deleted_by'  => null,
                        'deleted_at'  => null,
                        'updated_by'  => 1,
                        'updated_at'  => $ts,
                    ]);
                }
            }
        }

        /* ── 2. Petakan seluruh permission ke menu-nya ────────────── */
        /* Mapping lama milik katalog permission bawaan dibersihkan dulu,
         * lalu dibangun ulang persis mengikuti MENU_MODULE_MAP. Permission
         * custom (di luar MODULE_ACTIONS) tidak disentuh. */
        $catalogPermissionIds = array_values($permissionIds);
        if ($catalogPermissionIds !== []) {
            $mpTable->whereIn('permission_id', $catalogPermissionIds)->delete();
        }

        foreach (self::MENU_MODULE_MAP as $menuId => $moduleName) {
            $moduleActions = self::MODULE_ACTIONS[$moduleName] ?? [];
            foreach ($moduleActions as $action) {
                $permKey = $moduleName . '.' . $action;
                if (! isset($permissionIds[$permKey])) {
                    continue;
                }

                $permId = $permissionIds[$permKey];
                $exists = $mpTable
                    ->where('menu_id', $menuId)
                    ->where('permission_id', $permId)
                    ->countAllResults() > 0;

                if (! $exists) {
                    $mpTable->insert([
                        'menu_id'       => $menuId,
                        'permission_id' => $permId,
                        'created_by'    => 1,
                        'created_at'    => $ts,
                    ]);
                }
            }
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new RuntimeException('FMSPrivilegesPermissionSeeder gagal di-commit.');
        }
    }

    private function uuid4(): string
    {
        $b    = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h    = bin2hex($b);
        return sprintf('%s-%s-%s-%s-%s',
            substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4),
            substr($h, 16, 4), substr($h, 20, 12));
    }
}
