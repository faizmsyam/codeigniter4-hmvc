<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FmsCBrandSeeder extends Seeder
{
	public function run()
	{
		$data = [
			'id'      => 1,
			'name'    => 'App Starter',
			'email'   => 'sobatdukcapil@tangerangkota.go.id',
			'address' => 'Jl. Satria - Sudirman No.1 Kecamatan Tangerang, Kota Tangerang',
			'phone'   => null,
			'logo'    => null,
			'logo_light' => null,
			'favicon' => null,
		];

		$this->db->table('fms_c_brand')->insert($data);
	}
}
