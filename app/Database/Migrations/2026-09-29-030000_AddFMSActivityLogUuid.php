<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Adds a public UUID to existing activity-log rows. */
final class AddFMSActivityLogUuid extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('uuid', 't_activity_logs')) {
            return;
        }

        $this->forge->addColumn('t_activity_logs', [
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
                'after' => 'id',
            ],
        ]);

        $physicalTableName = $this->db->DBPrefix . 't_activity_logs';
        $escapedTableName = $this->db->escapeIdentifiers($physicalTableName);
        $activityLogRows = $this->db->query(
            'SELECT id FROM ' . $escapedTableName . ' WHERE uuid IS NULL',
        )->getResultArray();

        foreach ($activityLogRows as $activityLogRow) {
            $this->db->table('t_activity_logs')
                ->where('id', (int) $activityLogRow['id'])
                ->update(['uuid' => $this->generateUuidV4()]);
        }

        $this->forge->modifyColumn('t_activity_logs', [
            'uuid' => [
                'name' => 'uuid',
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('uuid', false, true, 't_activity_logs_uuid_unique');
    }

    public function down(): void
    {
        if (! $this->db->fieldExists('uuid', 't_activity_logs')) {
            return;
        }

        try {
            $this->forge->dropKey('t_activity_logs', 't_activity_logs_uuid_unique');
        } catch (\Throwable $droppedKeyException) {
            /* Abaikan jika index sudah tidak ada (MySQLi maupun SQLite3) */
            $message = $droppedKeyException->getMessage();
            if (! (str_contains($message, 'no such index') || str_contains($message, "Can't DROP INDEX"))) {
                throw $droppedKeyException;
            }
        }

        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->dropColumn('t_activity_logs', 'uuid');
    }

    private function generateUuidV4(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0f) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3f) | 0x80);
        $hexadecimalUuid = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimalUuid, 0, 8),
            substr($hexadecimalUuid, 8, 4),
            substr($hexadecimalUuid, 12, 4),
            substr($hexadecimalUuid, 16, 4),
            substr($hexadecimalUuid, 20, 12),
        );
    }
}
