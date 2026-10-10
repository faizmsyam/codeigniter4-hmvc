<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateFMSEmailSettings extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('c_email_settings')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'protocol' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'smtp'],
            'smtp_host' => ['type' => 'VARCHAR', 'constraint' => 190, 'default' => ''],
            'smtp_port' => ['type' => 'INT', 'unsigned' => true, 'default' => 587],
            'smtp_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'default' => ''],
            'smtp_password_encrypted' => ['type' => 'TEXT', 'null' => true],
            'smtp_crypto' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'tls'],
            'from_email' => ['type' => 'VARCHAR', 'constraint' => 190, 'default' => ''],
            'from_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'default' => ''],
            'reply_to' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'timeout_seconds' => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('c_email_settings', true);

        $this->db->table('c_email_settings')->insert([
            'enabled' => 0,
            'protocol' => 'smtp',
            'smtp_port' => 587,
            'smtp_crypto' => 'tls',
            'timeout_seconds' => 10,
            'version' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('c_email_settings', true);
    }
}
