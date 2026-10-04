<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder menu default starter FMS.
 *
 * Urutan Control Panel (id_parent = 2):
 *   1. Brand           (ph-duotone ph-palette)
 *   2. Admin Menus     (ph-duotone ph-list)
 *   3. User Groups     (ph-duotone ph-users-three)
 *   4. Privileges      (ph-duotone ph-shield-check)
 *   5. Users           (ph-duotone ph-user)
 *   6. Profil Saya     (ph-duotone ph-user-circle)
 *   7. Activity Logs   (ph-duotone ph-clock-counter-clockwise)
 *
 * Menu Uploads telah dihapus.
 */
final class FMSCMenusSeeder extends Seeder
{
    public function run(): void
    {
        $menuTable = $this->db->table('c_menus');

        $this->db->disableForeignKeyChecks();
        $menuTable->truncate();
        $this->db->enableForeignKeyChecks();

        $data = [
            [
                'id'           => 1,
                'id_parent'    => null,
                'name'         => 'Dashboard',
                'url'          => 'dashboard',
                'position'     => 1,
                'icon'         => 'ph-duotone ph-house-line',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 2,
                'id_parent'    => null,
                'name'         => 'Control Panel',
                'url'          => '#',
                'position'     => 50,
                'icon'         => 'ph-duotone ph-gear',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 3,
                'id_parent'    => 2,
                'name'         => 'Brand',
                'url'          => 'brand',
                'position'     => 1,
                'icon'         => 'ph-duotone ph-palette',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 4,
                'id_parent'    => 2,
                'name'         => 'Admin Menus',
                'url'          => 'admin-menus',
                'position'     => 2,
                'icon'         => 'ph-duotone ph-list',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 5,
                'id_parent'    => 2,
                'name'         => 'User Groups',
                'url'          => 'user-groups',
                'position'     => 3,
                'icon'         => 'ph-duotone ph-users-three',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 6,
                'id_parent'    => 2,
                'name'         => 'Privileges',
                'url'          => 'privileges',
                'position'     => 4,
                'icon'         => 'ph-duotone ph-shield-check',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 7,
                'id_parent'    => 2,
                'name'         => 'Users',
                'url'          => 'users',
                'position'     => 5,
                'icon'         => 'ph-duotone ph-user',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 8,
                'id_parent'    => 2,
                'name'         => 'Profil Saya',
                'url'          => 'profile',
                'position'     => 6,
                'icon'         => 'ph-duotone ph-user-circle',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
            [
                'id'           => 9,
                'id_parent'    => 2,
                'name'         => 'Activity Logs',
                'url'          => 'activity-logs',
                'position'     => 7,
                'icon'         => 'ph-duotone ph-clock-counter-clockwise',
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
            ],
        ];

        $menuTable->insertBatch($data);
    }
}
