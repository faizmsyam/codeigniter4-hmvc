<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateFMSCMenus extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id' => [
				'type'           => 'BIGINT',
				'unsigned'       => true,
				'auto_increment' => true,
			],
			'id_parent' => [
				'type'       => 'BIGINT',
				'unsigned'   => true,
				'null'       => true,
			],
			'name' => [
				'type'       => 'VARCHAR',
				'constraint' => 100,
			],
			'url' => [
				'type'       => 'VARCHAR',
				'constraint' => 100,
				'null'       => true,
			],
			'position' => [
				'type'       => 'INT',
				'constraint' => 11,
				'null'       => true,
			],
			'icon' => [
				'type'       => 'LONGTEXT',
				'null'       => true,
			],
			'is_active' => [
				'type'       => 'TINYINT',
				'constraint' => 1,
				'default'    => 1,
			],
			'target_blank' => [
				'type'       => 'TINYINT',
				'constraint' => 1,
				'default'    => 0,
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
		$this->forge->addKey('id_parent');
		$this->forge->addKey('is_active');

		// Self Foreign Key (menu parent)
		$this->forge->addForeignKey(
			'id_parent',
			'c_menus',
			'id',
			'SET NULL',
			'CASCADE'
		);

		$tableAttributes = $this->db->DBDriver === 'MySQLi'
			? ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']
			: [];
		$this->forge->createTable('c_menus', true, $tableAttributes);

		$this->seedDefaultMenus();
	}

	private function seedDefaultMenus(): void
	{
		$data = [
			[
				'id'           => 1,
				'id_parent'    => null,
				'name'         => 'Dashboard',
				'url'          => 'dashboard',
				'position'     => 1,
				'icon'         => 'ph-duotone ph-house-line',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 2,
				'id_parent'    => null,
				'name'         => 'Control Panel',
				'url'          => '#',
				'position'     => 50,
				'icon'         => 'ph-duotone ph-gear',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 3,
				'id_parent'    => 2,
				'name'         => 'Brand',
				'url'          => 'brand',
				'position'     => 1,
				'icon'         => 'ph-duotone ph-palette',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 4,
				'id_parent'    => 2,
				'name'         => 'Admin Menus',
				'url'          => 'admin-menus',
				'position'     => 2,
				'icon'         => 'ph-duotone ph-list',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 5,
				'id_parent'    => 2,
				'name'         => 'User Groups',
				'url'          => 'user-groups',
				'position'     => 3,
				'icon'         => 'ph-duotone ph-users-three',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 6,
				'id_parent'    => 2,
				'name'         => 'Privileges',
				'url'          => 'privileges',
				'position'     => 4,
				'icon'         => 'ph-duotone ph-shield-check',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 7,
				'id_parent'    => 2,
				'name'         => 'Users',
				'url'          => 'users',
				'position'     => 5,
				'icon'         => 'ph-duotone ph-user',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 8,
				'id_parent'    => 2,
				'name'         => 'Profil Saya',
				'url'          => 'profile',
				'position'     => 6,
				'icon'         => 'ph-duotone ph-user-circle',
				'is_active'    => 1,
				'target_blank' => 0,
			],
			[
				'id'           => 9,
				'id_parent'    => 2,
				'name'         => 'Activity Logs',
				'url'          => 'activity-logs',
				'position'     => 7,
				'icon'         => 'ph-duotone ph-clock-counter-clockwise',
				'is_active'    => 1,
				'target_blank' => 0,
			],
		];

		$this->db->table('c_menus')->insertBatch($data);
	}

	public function down()
	{
		$this->forge->dropTable('c_menus', true);
	}
}
