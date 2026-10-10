<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

final class AddAdminMustChangePasswordSetting extends Migration
{
    public function up(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        $table = $db->prefixTable('c_auth_settings');
        if (! $db->tableExists($table)) {
            return;
        }

        $fields = $db->getFieldData($table);
        $fieldNames = array_column($fields, 'name');

        if (! in_array('admin_must_change_password', $fieldNames, true)) {
            // Use raw SQL with the prefixed table name directly
            $db->query(
                "ALTER TABLE {$table} ADD COLUMN `admin_must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `admin_created_email_verification_required`"
            );
        }
    }

    public function down(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        $table = $db->prefixTable('c_auth_settings');
        if ($db->fieldExists('admin_must_change_password', $table)) {
            $db->query("ALTER TABLE {$table} DROP COLUMN `admin_must_change_password`");
        }
    }
}
