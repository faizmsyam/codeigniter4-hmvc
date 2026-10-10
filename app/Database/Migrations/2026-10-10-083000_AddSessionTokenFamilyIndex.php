<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\BaseConnection;

final class AddSessionTokenFamilyIndex extends Migration
{
    public function up(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        // Resolve physical table names respecting DBPrefix
        $sessionTable   = $db->prefixTable('t_user_sessions');
        $refreshTable   = $db->prefixTable('t_api_refresh_tokens');

        // Guard: skip if table doesn't exist (fresh install — create-table migration handles it)
        if (! $db->tableExists($sessionTable)) {
            return;
        }

        // 1. Add token_family_id column if missing (forge uses DBPrefix automatically)
        $fields = $db->getFieldData($sessionTable);
        $fieldNames = array_column($fields, 'name');
        if (! in_array('token_family_id', $fieldNames, true)) {
            // Use logical name — forge adds prefix internally
            $this->forge->addColumn('t_user_sessions', [
                'token_family_id' => [
                    'type'       => 'CHAR',
                    'constraint' => 36,
                    'null'       => true,
                    'after'      => 'user_id',
                ],
            ]);
        }

        // 2. Create indexes — guard with a simple check; MySQL ignores duplicate creation silently
        $this->safeCreateIndex($db, $sessionTable, 'idx_sessions_user_family',
            'user_id, token_family_id, revoked_at, expires_at, id');

        $this->safeCreateIndex($db, $sessionTable, 'idx_sessions_last_activity',
            'last_activity_at, revoked_at');

        if ($db->tableExists($refreshTable)) {
            $this->safeCreateIndex($db, $refreshTable, 'idx_refresh_tokens_user_family_active',
                'user_id, token_family_id, used_at, revoked_at, expires_at');
        }
    }

    private function safeCreateIndex(BaseConnection $db, string $table, string $indexName, string $columns): void
    {
        try {
            // MySQL 8.0+ / MariaDB 10.5+: CREATE INDEX IF NOT EXISTS is supported
            $db->query("CREATE INDEX IF NOT EXISTS `{$indexName}` ON {$table} ({$columns})");
        } catch (\Throwable $e) {
            // MySQL < 8.0 fallback: check existence first
            $result = $db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName])->getResult();
            if (empty($result)) {
                $db->query("CREATE INDEX `{$indexName}` ON {$table} ({$columns})");
            }
        }
    }

    public function down(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        $sessionTable = $db->prefixTable('t_user_sessions');
        $refreshTable = $db->prefixTable('t_api_refresh_tokens');

        foreach (['idx_sessions_user_family', 'idx_sessions_last_activity'] as $idx) {
            try { $db->query("DROP INDEX {$idx} ON {$sessionTable}"); } catch (\Throwable $e) { /* ignore */ }
        }
        try { $db->query("DROP INDEX idx_refresh_tokens_user_family_active ON {$refreshTable}"); } catch (\Throwable $e) { /* ignore */ }

        if ($db->fieldExists('token_family_id', $sessionTable)) {
            $this->forge->dropColumn('t_user_sessions', 'token_family_id');
        }
    }
}
