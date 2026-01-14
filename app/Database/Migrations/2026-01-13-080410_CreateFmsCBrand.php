<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateFmsCBrand extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id' => [
				'type'           => 'BIGINT',
				'constraint'     => 11,
				'unsigned'       => true,
				'auto_increment' => true,
			],
			'name' => [
				'type'       => 'VARCHAR',
				'constraint' => 100,
				'null'       => true,
			],
			'email' => [
				'type'       => 'VARCHAR',
				'constraint' => 100,
				'null'       => true,
			],
			'address' => [
				'type' => 'TEXT',
				'null' => true,
			],
			'phone' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => true,
			],
			'logo' => [
				'type' => 'BLOB',
				'null' => true,
			],
			'logo_light' => [
				'type' => 'BLOB',
				'null' => true,
			],
			'favicon' => [
				'type' => 'BLOB',
				'null' => true,
			],
			'created_at' => [
				'type' => 'TIMESTAMP',
				'null' => new RawSql('CURRENT_TIMESTAMP'),
			],
			'updated_at' => [
				'type' => 'TIMESTAMP',
				'null' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
			],
		]);

		// Primary Key
		$this->forge->addKey('id', true);

		// Create table
		$this->forge->createTable('fms_c_brand', true, [
			'ENGINE'  => 'InnoDB',
			'CHARSET' => 'utf8mb4',
			'COLLATE' => 'utf8mb4_general_ci',
		]);
	}

	public function down()
	{
		$this->forge->dropTable('fms_c_brand', true);
	}
}
