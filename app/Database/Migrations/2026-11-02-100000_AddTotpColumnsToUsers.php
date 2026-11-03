<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddTotpColumnsToUsers20261102 extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('m_users', [
            'totp_secret'     => ['type' => 'TEXT', 'null' => true, 'after' => 'token_version'],
            'totp_enabled_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'totp_secret'],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'code_hash'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('t_user_totp_backup_codes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('t_user_totp_backup_codes', true);
        if ($this->db->fieldExists('totp_enabled_at', 'm_users')) {
            $this->forge->dropColumn('m_users', 'totp_enabled_at');
        }
        if ($this->db->fieldExists('totp_secret', 'm_users')) {
            $this->forge->dropColumn('m_users', 'totp_secret');
        }
    }
}
