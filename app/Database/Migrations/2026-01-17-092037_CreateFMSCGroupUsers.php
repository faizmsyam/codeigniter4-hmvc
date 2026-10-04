<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateFMSCGroupUsers extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id' => [
				'type'           => 'BIGINT',
				'unsigned'       => true,
				'auto_increment' => true,
			],
			'name' => [
				'type'       => 'VARCHAR',
				'constraint' => 100,
			],
			'is_active' => [
				'type'       => 'TINYINT',
				'constraint' => 1,
				'default'    => 1,
			],
		
			'created_by' => [
				'type' => 'BIGINT',
				'unsigned' => true,
				'null' => true,
			],
			'created_at' => [
				'type' => 'TIMESTAMP',
				'default' => new RawSql('CURRENT_TIMESTAMP'),
			],
			'updated_by' => [
				'type' => 'BIGINT',
				'unsigned' => true,
				'null' => true,
			],
			'updated_at' => [
				'type' => 'TIMESTAMP',
				'null' => true,
			],
			'deleted_by' => [
				'type' => 'BIGINT',
				'unsigned' => true,
				'null' => true,
			],
			'deleted_at' => [
				'type' => 'TIMESTAMP',
				'null' => true,
			],
		]);

		// Primary Key
		$this->forge->addKey('id', true);

		// Indexes
		$this->forge->addKey('is_active');

		$tableAttributes = $this->db->DBDriver === 'MySQLi'
			? ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']
			: [];
		$this->forge->createTable('c_group_users', true, $tableAttributes);
	}

	public function down()
	{
		$this->forge->dropTable('c_group_users', true);
	}
}
