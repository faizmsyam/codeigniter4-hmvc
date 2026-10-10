<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateFMSStorageTables extends Migration
{
    public function up(): void
    {
        $forge = $this->forge;

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'source_backend_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'is_super_admin' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'failed_login_count' => ['type' => 'INT', 'default' => 0],
            'locked_until' => ['type' => 'DATETIME', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'password_changed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('uuid');
        $forge->addUniqueKey('username');
        $forge->addUniqueKey('email');
        $forge->addUniqueKey('source_backend_user_id');
        $forge->createTable('storage_users', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'permissions_json' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('name');
        $forge->createTable('storage_roles', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'permission_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('permission_key');
        $forge->createTable('storage_permissions', true);

        $forge->addField([
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'role_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $forge->addKey(['user_id', 'role_id'], true);
        $forge->createTable('storage_user_roles', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'selector' => ['type' => 'CHAR', 'constraint' => 24],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'user_agent_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'expires_at' => ['type' => 'DATETIME'],
            'last_seen_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('selector');
        $forge->addUniqueKey('token_hash');
        $forge->addKey('user_id');
        $forge->addKey('expires_at');
        $forge->createTable('storage_sessions', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'object_key' => ['type' => 'VARCHAR', 'constraint' => 1024],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'expires_at' => ['type' => 'DATETIME'],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('token_hash');
        $forge->addKey('expires_at');
        $forge->createTable('storage_shares', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'event' => ['type' => 'VARCHAR', 'constraint' => 100],
            'object_key' => ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => true],
            'ip_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'metadata_json' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('created_at');
        $forge->createTable('storage_audit_logs', true);

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $db = $this->db;
        $now = date('Y-m-d H:i:s');
        $backendUser = $db->table('m_users')
            ->groupStart()
            ->where('username_normalized', 'faizmsyam')
            ->orWhere('username', 'faizmsyam')
            ->groupEnd()
            ->get(1)->getRowArray();

        $storageUser = $db->table('storage_users')->where('username', 'faizmsyam')->get(1)->getRowArray();
        if (! is_array($storageUser) && is_array($backendUser)) {
            $db->table('storage_users')->insert([
                'uuid' => $this->generateUuidV4(),
                'source_backend_user_id' => (int) $backendUser['id'],
                'username' => 'faizmsyam',
                'email' => $backendUser['email'] ?? null,
                'password_hash' => (string) $backendUser['password_hash'],
                'full_name' => $backendUser['full_name'] ?? 'faizmsyam',
                'status' => 'active',
                'is_super_admin' => 1,
                'password_changed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $storageUser = $db->table('storage_users')->where('username', 'faizmsyam')->get(1)->getRowArray();
        }

        $permissions = ['storage.read', 'storage.upload', 'storage.edit', 'storage.delete', 'storage.share', 'storage.users'];
        foreach ($permissions as $permission) {
            if ($db->table('storage_permissions')->where('permission_key', $permission)->countAllResults() === 0) {
                $db->table('storage_permissions')->insert([
                    'permission_key' => $permission,
                    'description' => 'FMS Storage: ' . $permission,
                    'created_at' => $now,
                ]);
            }
        }

        $role = $db->table('storage_roles')->where('name', 'super_admin')->get(1)->getRowArray();
        if (! is_array($role)) {
            $db->table('storage_roles')->insert([
                'name' => 'super_admin',
                'permissions_json' => json_encode($permissions, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $role = $db->table('storage_roles')->where('name', 'super_admin')->get(1)->getRowArray();
        }

        if (is_array($storageUser) && is_array($role)) {
            $exists = $db->table('storage_user_roles')
                ->where('user_id', (int) $storageUser['id'])
                ->where('role_id', (int) $role['id'])
                ->countAllResults();
            if ($exists === 0) {
                $db->table('storage_user_roles')->insert([
                    'user_id' => (int) $storageUser['id'],
                    'role_id' => (int) $role['id'],
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['storage_audit_logs', 'storage_shares', 'storage_sessions', 'storage_user_roles', 'storage_permissions', 'storage_roles', 'storage_users'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function generateUuidV4(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0F) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3F) | 0x80);
        $hexadecimalUuid = bin2hex($randomBytes);
        return implode('-', [
            substr($hexadecimalUuid, 0, 8),
            substr($hexadecimalUuid, 8, 4),
            substr($hexadecimalUuid, 12, 4),
            substr($hexadecimalUuid, 16, 4),
            substr($hexadecimalUuid, 20, 12),
        ]);
    }
}
