<?php

namespace Tests\Unit\Modules\Authentication;

use CodeIgniter\Test\CIUnitTestCase;

final class FMSAuthenticationDefaultsSeederTest extends CIUnitTestCase
{
    public function testDefaultMigrationSeedsAuthenticationTablesWithLogicalNames(): void
    {
        $seeder = (string) file_get_contents(APPPATH . 'Database/Seeds/FMSApiKeyAndBasicAuthSeeder.php');
        $defaultsMigration = (string) file_get_contents(APPPATH . 'Database/Migrations/2026-09-30-110000_SeedFMSDefaults.php');
        $backfillMigration = (string) file_get_contents(APPPATH . 'Database/Migrations/2026-10-09-092700_SeedAuthenticationDefaults.php');

        foreach (["table('c_auth_settings')", "table('c_api_keys')", "table('c_basic_auth_clients')"] as $logicalTable) {
            $this->assertStringContainsString($logicalTable, $seeder);
        }
        $this->assertStringNotContainsString("table('fms_c_", $seeder);
        $this->assertStringContainsString("'fmsdefaultkey01'", $seeder);
        $this->assertStringContainsString('FMSApiKeyAndBasicAuthSeeder', $defaultsMigration);
        $this->assertStringContainsString('FMSApiKeyAndBasicAuthSeeder', $backfillMigration);
    }

    public function testEmptyAuthenticationSettingsFallbackMatchesSchemaDefaults(): void
    {
        foreach ([
            APPPATH . 'Modules/Authentication/Services/FMSBackendAuthenticationService.php',
            APPPATH . 'Modules/Authentication/Controllers/Api/FMSAuthenticationApiController.php',
        ] as $path) {
            $source = (string) file_get_contents($path);
            $this->assertStringContainsString("admin_created_email_verification_required'] ?? false", $source);
            $this->assertStringContainsString("public_email_verification_required'] ?? true", $source);
        }
    }
}
