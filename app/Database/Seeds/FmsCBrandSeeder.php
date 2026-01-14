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
			'email'   => 'faimsyam@gmail.com',
			'address' => 'Jl. Pembangunan 3 RT. 05/05 Kel. Karang Sari Kec. Neglasari Kota Tangerang',
			'phone'   => null,
			'logo'    => 'assets/fms/img/logo/logo.png',
			'logo_light' => 'assets/fms/img/logo/logo.png',
			'favicon' => 'favicon.ico',
		];

		$this->db->table('fms_c_brand')->insert($data);
	}
}
