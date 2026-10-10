<?php

namespace App\Modules\LogMonitor\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddFMSLogMonitorMenu extends Migration
{
    public function up(): void
    {
        $ts = date('Y-m-d H:i:s');

        // Seed menu item if not exists
        $menuExists = $this->db->table('c_menus')
            ->where('url', 'log-monitor')
            ->where('id_parent', 2)
            ->get()
            ->getRowArray();

        if (! $menuExists) {
            $this->db->table('c_menus')->insert([
                'id'           => 11,
                'id_parent'    => 2,
                'name'         => 'Log Monitor',
                'url'          => 'log-monitor',
                'icon'         => 'ph-duotone ph-terminal-window',
                'position'     => 7,
                'is_active'    => 1,
                'target_blank' => 0,
                'created_by'   => 1,
                'created_at'   => $ts,
            ]);
        }

        // Upsert permission
        $permExists = $this->db->table('c_permissions')
            ->where('permission_key', 'log_monitor.read')
            ->get()
            ->getRowArray();

        if (! $permExists) {
            $this->db->table('c_permissions')->insert([
                'uuid'           => service('uuid')->uuid4(),
                'permission_key' => 'log_monitor.read',
                'module_name'    => 'log_monitor',
                'action_name'    => 'read',
                'description'    => 'Hak read pada modul Log Monitor.',
                'is_active'      => 1,
                'is_system'      => 1,
                'created_by'     => 1,
                'created_at'     => $ts,
            ]);

            $newPermId = $this->db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0;
            if ($newPermId) {
                $this->db->table('t_menu_permissions')->insert([
                    'menu_id'       => 11,
                    'permission_id' => (int) $newPermId,
                    'created_by'    => 1,
                    'created_at'    => $ts,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->db->table('c_menus')->where('url', 'log-monitor')->where('id_parent', 2)->delete();
        $this->db->table('c_permissions')->where('permission_key', 'log_monitor.read')->delete();
        $this->db->table('t_menu_permissions')
            ->whereIn('permission_id', function ($b) { $b->select('id')->from('c_permissions')->where('permission_key', 'log_monitor.read'); })
            ->delete();
    }
}
