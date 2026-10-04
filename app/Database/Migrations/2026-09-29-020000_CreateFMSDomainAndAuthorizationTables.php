<?php

namespace App\Database\Migrations;

use App\Database\FMSAuditColumns;
use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

final class CreateFMSDomainAndAuthorizationTables extends Migration
{
    use FMSAuditColumns;

    public function up(): void
    {
        $this->createBrandTable();
        $this->createGroupTable();
        $this->createUserGroupTable();
        $this->createPermissionTable();
        $this->createGroupPermissionTable();
        $this->createMenuPermissionTable();
        $this->createUserSessionTable();
        $this->createActivityLogTable();
        $this->createFileUploadTable();
    }

    public function down(): void
    {
        foreach ([
            't_file_uploads',
            't_activity_logs',
            't_user_sessions',
            't_menu_permissions',
            't_group_permissions',
            'c_permissions',
            't_user_groups',
            'c_group_users',
            'c_brands',
        ] as $tableName) {
            $this->forge->dropTable($tableName, true);
        }
    }

    private function createBrandTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'email_normalized' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'logo_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'logo_light_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'favicon_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'settings_version' => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            ...$this->fmsAuditColumnDefinitions(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('is_active');
        $this->forge->createTable('c_brands', true, $this->tableAttributes());
    }

    private function createGroupTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_system' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ...$this->fmsAuditColumnDefinitions(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('c_group_users', true, $this->tableAttributes());
    }

    private function createUserGroupTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'group_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'assigned_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'group_id']);
        $this->forge->addKey('group_id');
        $this->forge->addForeignKey('user_id', 'm_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('group_id', 'c_group_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('t_user_groups', true, $this->tableAttributes());
    }

    private function createPermissionTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'permission_key' => ['type' => 'VARCHAR', 'constraint' => 120],
            'module_name' => ['type' => 'VARCHAR', 'constraint' => 60],
            'action_name' => ['type' => 'VARCHAR', 'constraint' => 60],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ...$this->fmsAuditColumnDefinitions(),
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('permission_key');
        $this->forge->addKey('module_name');
        $this->forge->createTable('c_permissions', true, $this->tableAttributes());
    }

    private function createGroupPermissionTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'group_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'permission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'effect' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'allow'],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group_id', 'permission_id']);
        $this->forge->addKey('permission_id');
        $this->forge->createTable('t_group_permissions', true, $this->tableAttributes());
    }

    private function createMenuPermissionTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'menu_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'permission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['menu_id', 'permission_id']);
        $this->forge->addKey('permission_id');
        $this->forge->createTable('t_menu_permissions', true, $this->tableAttributes());
    }

    private function createUserSessionTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'session_identifier_hash' => ['type' => 'VARCHAR', 'constraint' => 128],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'user_agent_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'last_activity_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'revoke_reason' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('session_identifier_hash');
        $this->forge->addKey(['user_id', 'revoked_at']);
        $this->forge->createTable('t_user_sessions', true, $this->tableAttributes());
    }

    private function createActivityLogTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'request_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'actor_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'event' => ['type' => 'VARCHAR', 'constraint' => 80],
            'module' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'before_json' => ['type' => 'TEXT', 'null' => true],
            'after_json' => ['type' => 'TEXT', 'null' => true],
            'metadata_json' => ['type' => 'TEXT', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'TEXT', 'null' => true],
            'device_label' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'user_agent_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'http_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'route_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status_code' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'previous_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'entry_hash' => ['type' => 'VARCHAR', 'constraint' => 128],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['event', 'created_at']);
        $this->forge->addKey('created_at');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('actor_user_id');
        $this->forge->addForeignKey('actor_user_id', 'm_users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('t_activity_logs', true, $this->tableAttributes());
    }

    private function createFileUploadTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'VARCHAR', 'constraint' => 36],
            'public_identifier' => ['type' => 'VARCHAR', 'constraint' => 64],
            'owner_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'module_name' => ['type' => 'VARCHAR', 'constraint' => 60],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'entity_identifier' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'storage_driver' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'local'],
            'bucket_name' => ['type' => 'VARCHAR', 'constraint' => 63],
            'object_key' => ['type' => 'VARCHAR', 'constraint' => 512],
            'original_mime_type' => ['type' => 'VARCHAR', 'constraint' => 100],
            'output_mime_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'image/webp'],
            'original_byte_size' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'output_byte_size' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'image_width' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'image_height' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'content_hash' => ['type' => 'VARCHAR', 'constraint' => 64],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('public_identifier');
        $this->forge->addKey(['module_name', 'entity_type', 'entity_identifier']);
        $this->forge->addKey(['owner_user_id', 'status']);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addForeignKey('owner_user_id', 'm_users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('t_file_uploads', true, $this->tableAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    private function tableAttributes(): array
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return [];
        }

        return ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci'];
    }
}
