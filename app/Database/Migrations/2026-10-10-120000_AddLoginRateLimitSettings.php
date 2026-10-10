<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * Menambahkan kebijakan rate limit login ke c_auth_settings.
 *
 * Rate limit dapat diaktifkan/dinonaktifkan, beserta ambang dan durasinya.
 */
final class AddLoginRateLimitSettings extends Migration
{
    public function up(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        $table = $db->prefixTable('c_auth_settings');
        if (! $db->tableExists($table)) {
            return;
        }

        $existingColumns = array_column($db->getFieldData($table), 'name');

        $columns = [
            'login_rate_limit_enabled' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `admin_must_change_password`",
            'login_max_failures' => "INT UNSIGNED NOT NULL DEFAULT 5 AFTER `login_rate_limit_enabled`",
            'login_failure_window_seconds' => "INT UNSIGNED NOT NULL DEFAULT 900 AFTER `login_max_failures`",
            'login_lockout_seconds' => "INT UNSIGNED NOT NULL DEFAULT 900 AFTER `login_failure_window_seconds`",
        ];

        foreach ($columns as $columnName => $columnDefinition) {
            if (in_array($columnName, $existingColumns, true)) {
                continue;
            }

            $db->query("ALTER TABLE {$table} ADD COLUMN `{$columnName}` {$columnDefinition}");
        }
    }

    public function down(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        $table = $db->prefixTable('c_auth_settings');
        if (! $db->tableExists($table)) {
            return;
        }

        foreach ([
            'login_lockout_seconds',
            'login_failure_window_seconds',
            'login_max_failures',
            'login_rate_limit_enabled',
        ] as $columnName) {
            if ($db->fieldExists($columnName, $table)) {
                $db->query("ALTER TABLE {$table} DROP COLUMN `{$columnName}`");
            }
        }
    }
}
