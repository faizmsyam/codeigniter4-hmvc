<?php

namespace App\Database\Migrations;

use App\Database\Seeds\FMSApiKeyAndBasicAuthSeeder;
use CodeIgniter\Database\Migration;

/**
 * Backfills authentication defaults for installations migrated before the
 * API-key/basic-auth seeder was attached to SeedFMSDefaults.
 */
final class SeedAuthenticationDefaults extends Migration
{
    public function up(): void
    {
        $config = new \Config\Database();
        (new FMSApiKeyAndBasicAuthSeeder($config, $this->db))->run();
    }

    public function down(): void
    {
        $this->db->table('c_api_keys')->where('key_id', 'fmsdefaultkey01')->delete();
        $this->db->table('c_basic_auth_clients')->where('username', 'fms_default_client')->delete();
    }
}
