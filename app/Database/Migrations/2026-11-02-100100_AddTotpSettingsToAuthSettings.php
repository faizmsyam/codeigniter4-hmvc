<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * Kolom global switch TOTP di c_auth_settings:
 * - totp_enforced (0/1): kalau 1, SEMUA login wajib TOTP.
 * - totp_issuer: nama tampil di Google Authenticator.
 */
final class AddTotpSettingsToAuthSettings20261102 extends Migration
{
    public function up(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();
        $table = $db->prefixTable('c_auth_settings');
        if (! $db->tableExists($table)) {
            return;
        }

        $fields = array_column($db->getFieldData($table), 'name');

        if (! in_array('totp_enforced', $fields, true)) {
            $db->query("ALTER TABLE {$table} ADD COLUMN `totp_enforced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `admin_must_change_password`");
        }
        if (! in_array('totp_issuer', $fields, true)) {
            $db->query("ALTER TABLE {$table} ADD COLUMN `totp_issuer` VARCHAR(64) NOT NULL DEFAULT 'FMS' AFTER `totp_enforced`");
        }
    }

    public function down(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();
        $table = $db->prefixTable('c_auth_settings');
        if ($db->fieldExists('totp_issuer', 'c_auth_settings')) {
            $db->query("ALTER TABLE {$table} DROP COLUMN `totp_issuer`");
        }
        if ($db->fieldExists('totp_enforced', 'c_auth_settings')) {
            $db->query("ALTER TABLE {$table} DROP COLUMN `totp_enforced`");
        }
    }
}
