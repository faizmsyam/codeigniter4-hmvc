<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menyimpan asal aktivitas dalam bentuk plain agar bisa ditampilkan
 * di list dan CSV export. Hash lama tetap dipertahankan untuk
 * kebutuhan integritas rantai audit.
 */
final class AddFMSActivityLogNetworkColumns extends Migration
{
    public function up(): void
    {
        $columns = [];

        if (! $this->db->fieldExists('ip_address', 't_activity_logs')) {
            $columns['ip_address'] = [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ];
        }

        if (! $this->db->fieldExists('user_agent', 't_activity_logs')) {
            $columns['user_agent'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('device_label', 't_activity_logs')) {
            $columns['device_label'] = [
                'type'       => 'VARCHAR',
                'constraint' => 160,
                'null'       => true,
            ];
        }

        if ($columns !== []) {
            $this->forge->addColumn('t_activity_logs', $columns);
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        foreach (['device_label', 'user_agent', 'ip_address'] as $columnName) {
            if ($this->db->fieldExists($columnName, 't_activity_logs')) {
                $this->forge->dropColumn('t_activity_logs', $columnName);
            }
        }
    }
}
