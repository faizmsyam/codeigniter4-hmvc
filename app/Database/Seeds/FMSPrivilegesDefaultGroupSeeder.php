<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Menyiapkan grup default dan grant permission dasar untuk Administrator.
 *
 * Grup 1: Super Administrator — dapat permission wildcard (*).
 *          Dikelola oleh FMSProgrammerSuperAdminSeeder.
 *
 * Grup 2: Administrator — dapat permission operasional default (semua modul,
 *          kecuali delete permanen dan revoke session).
 *
 * Idempoten: aman dijalankan berulang kali.
 */
final class FMSPrivilegesDefaultGroupSeeder extends Seeder
{
    /**
     * Permission yang diberikan ke grup Administrator (ID 2).
     * Sesuai permission_key yang ada di FMSPrivilegesPermissionSeeder.
     *
     * @var list<string>
     */
    private const ADMINISTRATOR_PERMISSIONS = [
        'dashboard.read',

        'brand.read',
        'brand.update',

        'menus.view',
        'menus.create',
        'menus.update',
        'menus.delete',
        'menus.reorder',

        'user_groups.read',
        'user_groups.create',
        'user_groups.update',
        'user_groups.delete',
        'user_groups.restore',

        'privileges.manage',
        'privileges.create',
        'privileges.update',

        'users.read',
        'users.create',
        'users.update',
        'users.change_status',
        'users.reset_password',
        'users.verify_email',
        'users.unverify_email',
        'users.unlock',
        'users.restore',
        'users.read_sessions',
        'users.revoke_sessions',
        'users.read_groups',
        'users.assign_groups',

        'profile.read',
        'profile.update',
        'profile.update_avatar',
        'profile.change_password',

        'activity_logs.read',
        'activity_logs.export',

        'log_monitor.read',
    ];

    public function run(): void
    {
        $db  = $this->db;
        $ts  = date('Y-m-d H:i:s');
        $grp = $db->table('c_group_users');
        $gp  = $db->table('t_group_permissions');

        $db->transStart();

        /* ── Pastikan grup 1 (Super Administrator) ada ─────── */
        if ($grp->where('id', 1)->countAllResults() === 0) {
            $grp->insert(['id' => 1, 'name' => 'Super Administrator', 'is_active' => 1, 'created_by' => 1, 'created_at' => $ts]);
        } else {
            $grp->where('id', 1)->update(['name' => 'Super Administrator', 'is_active' => 1, 'deleted_by' => null, 'deleted_at' => null]);
        }

        /* ── Pastikan grup 2 (Administrator) ada ────────────── */
        if ($grp->where('id', 2)->countAllResults() === 0) {
            $grp->insert(['id' => 2, 'name' => 'Administrator', 'is_active' => 1, 'created_by' => 1, 'created_at' => $ts]);
        } else {
            $grp->where('id', 2)->update(['name' => 'Administrator', 'is_active' => 1, 'deleted_by' => null, 'deleted_at' => null]);
        }

        /* ── Grant permission ke Administrator (grup 2) ─────── */
        foreach (self::ADMINISTRATOR_PERMISSIONS as $key) {
            $perm = $db->table('c_permissions')->where('permission_key', $key)->get()->getRowArray();
            if ($perm === null) {
                /* Permission belum di-seed, lewati — jalankan FMSPrivilegesPermissionSeeder dulu */
                continue;
            }

            $permId = (int) $perm['id'];
            $exists = $gp->where('group_id', 2)->where('permission_id', $permId)->countAllResults() > 0;

            if (! $exists) {
                $gp->insert([
                    'group_id'     => 2,
                    'permission_id' => $permId,
                    'effect'       => 'allow',
                    'created_by'   => 1,
                    'created_at'   => $ts,
                ]);
            }
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new RuntimeException('FMSPrivilegesDefaultGroupSeeder gagal di-commit.');
        }
    }
}
