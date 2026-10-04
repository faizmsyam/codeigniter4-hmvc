<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FMSCGroupUsersSeeder extends Seeder
{
	public function run()
	{
		$data = [
			[
				'id' => 1,
				'name' => 'Super Administrator',
				'is_active' => 1,
				'created_by' => 1,
			],
			[
				'id' => 2,
				'name' => 'Administrator',
				'is_active' => 1,
				'created_by' => 1,
			],
		];

		$this->db->table('c_group_users')->insertBatch($data);
	}
}
