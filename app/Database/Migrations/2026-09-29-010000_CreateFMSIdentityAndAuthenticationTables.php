<?php

namespace App\Database\Migrations;

use App\Database\FMSAuditColumns;
use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

final class CreateFMSIdentityAndAuthenticationTables extends Migration
{
    use FMSAuditColumns;

    public function up(): void
    {
        $this->createUsersTable();
        $this->createAuthenticationSettingsTable();
        $this->createEmailVerificationTokensTable();
        $this->createApiRefreshTokensTable();
        $this->createApiRevokedTokensTable();
        $this->createApiAuthenticationAttemptsTable();
        $this->createApiKeysTable();
        $this->createBasicAuthenticationClientsTable();
        $this->createUserSessionsTable();
    }

    public function down(): void
    {
        foreach ([
            't_user_sessions',
            'c_basic_auth_clients',
            'c_api_keys',
            't_api_auth_attempts',
            't_api_revoked_tokens',
            't_api_refresh_tokens',
            't_email_verification_tokens',
            'c_auth_settings',
            'm_users',
        ] as $logicalTableName) {
            $this->forge->dropTable($logicalTableName, true);
        }
    }

    private function createUsersTable(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'username_normalized' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'email_normalized' => ['type' => 'VARCHAR', 'constraint' => 190],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'registration_source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'admin'],
            'email_verified_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'password_changed_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'failed_login_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'locked_until' => ['type' => 'TIMESTAMP', 'null' => true],
            'last_login_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'last_login_ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'token_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'session_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ], $this->fmsAuditColumnDefinitions()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('username_normalized');
        $this->forge->addUniqueKey('email_normalized');
        $this->forge->addKey(['status', 'locked_until', 'deleted_at']);
        $this->createTable('m_users');
    }

    private function createAuthenticationSettingsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'public_registration_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'public_email_verification_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'admin_created_email_verification_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'verification_ttl_minutes' => ['type' => 'INT', 'unsigned' => true, 'default' => 1440],
            'resend_cooldown_seconds' => ['type' => 'INT', 'unsigned' => true, 'default' => 120],
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->createTable('c_auth_settings');
    }

    private function createEmailVerificationTokensTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'selector' => ['type' => 'CHAR', 'constraint' => 32],
            'validator_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'purpose' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'verify'],
            'expires_at' => ['type' => 'TIMESTAMP'],
            'used_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'revoked_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('selector');
        $this->forge->addKey(['user_id', 'expires_at', 'revoked_at']);
        $this->forge->addForeignKey('user_id', 'm_users', 'id', 'CASCADE', 'CASCADE');
        $this->createTable('t_email_verification_tokens');
    }

    private function createApiRefreshTokensTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'token_family_id' => ['type' => 'CHAR', 'constraint' => 36],
            'selector' => ['type' => 'CHAR', 'constraint' => 32],
            'validator_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'issued_at' => ['type' => 'TIMESTAMP'],
            'expires_at' => ['type' => 'TIMESTAMP'],
            'used_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'revoked_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'replaced_by_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'device_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'user_agent_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('selector');
        $this->forge->addKey(['user_id', 'revoked_at', 'expires_at']);
        $this->forge->addKey('token_family_id');
        $this->forge->addForeignKey('user_id', 'm_users', 'id', 'CASCADE', 'CASCADE');
        $this->createTable('t_api_refresh_tokens');
    }

    private function createApiRevokedTokensTable(): void
    {
        $this->forge->addField([
            'jti_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'TIMESTAMP'],
            'revoked_at' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('jti_hash', true);
        $this->forge->addKey('expires_at');
        $this->createTable('t_api_revoked_tokens');
    }

    private function createApiAuthenticationAttemptsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'identifier_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'result_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'created_at' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['identifier_hash', 'ip_hash', 'created_at']);
        $this->createTable('t_api_auth_attempts');
    }

    private function createApiKeysTable(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'key_id' => ['type' => 'CHAR', 'constraint' => 24],
            'label' => ['type' => 'VARCHAR', 'constraint' => 100],
            'secret_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'scopes_json' => ['type' => 'TEXT'],
            'environment' => ['type' => 'VARCHAR', 'constraint' => 20],
            'ip_allowlist_json' => ['type' => 'TEXT', 'null' => true],
            'expires_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'revoked_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'last_used_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'last_used_ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
        ], $this->fmsAuditColumnDefinitions()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key_id');
        $this->forge->addKey(['revoked_at', 'expires_at']);
        $this->createTable('c_api_keys');
    }

    private function createBasicAuthenticationClientsTable(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'label' => ['type' => 'VARCHAR', 'constraint' => 100],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'scopes_json' => ['type' => 'TEXT'],
            'environment' => ['type' => 'VARCHAR', 'constraint' => 20],
            'expires_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'locked_until' => ['type' => 'TIMESTAMP', 'null' => true],
            'failed_attempts' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'revoked_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'last_used_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'last_used_ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
        ], $this->fmsAuditColumnDefinitions()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addKey(['revoked_at', 'expires_at', 'locked_until']);
        $this->createTable('c_basic_auth_clients');
    }

    private function createUserSessionsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'session_uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'token_family_id' => ['type' => 'CHAR', 'constraint' => 36],
            'device_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'user_agent_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'last_activity_at' => ['type' => 'TIMESTAMP'],
            'expires_at' => ['type' => 'TIMESTAMP'],
            'revoked_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('session_uuid');
        $this->forge->addKey(['user_id', 'revoked_at', 'expires_at']);
        $this->forge->addForeignKey('user_id', 'm_users', 'id', 'CASCADE', 'CASCADE');
        $this->createTable('t_user_sessions');
    }

    /**
     * @param array<string, string> $attributes
     */
    private function createTable(string $logicalTableName, array $attributes = []): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            $attributes = [
                'ENGINE' => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ];
        }

        $this->forge->createTable($logicalTableName, true, $attributes);
    }
}
