<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * Per-user TOTP requirement override.
 *
 * NULL = follow c_auth_settings.totp_enforced.
 * 1    = require TOTP for this user.
 * 0    = skip TOTP for this user, even when global enforcement is enabled.
 */
final class AddTotpRequiredToUsers20261104 extends Migration
{
    public function up(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        if (! $db->fieldExists('totp_required', 'm_users')) {
            $this->forge->addColumn('m_users', [
                'totp_required' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'totp_enabled_at',
                ],
            ]);
        }

        $db->query(
            'UPDATE ' . $db->prefixTable('m_users')
            . ' SET totp_required = 1 WHERE totp_enabled_at IS NOT NULL AND totp_required IS NULL'
        );
    }

    public function down(): void
    {
        /** @var BaseConnection $db */
        $db = db_connect();

        if ($db->fieldExists('totp_required', 'm_users')) {
            $this->forge->dropColumn('m_users', 'totp_required');
        }
    }
}
