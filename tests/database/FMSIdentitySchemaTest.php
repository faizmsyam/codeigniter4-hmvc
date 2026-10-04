<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class FMSIdentitySchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $namespace = 'App';

    public function testExpectedPhysicalTablesExistWithFMSPrefix(): void
    {
        $databaseConnection = $this->db;
        $expectedPhysicalTables = [
            'fms_m_users',
            'fms_c_auth_settings',
            'fms_t_email_verification_tokens',
            'fms_t_api_refresh_tokens',
            'fms_t_api_revoked_tokens',
            'fms_t_api_auth_attempts',
            'fms_c_api_keys',
            'fms_c_basic_auth_clients',
            'fms_t_user_sessions',
        ];

        foreach ($expectedPhysicalTables as $physicalTableName) {
            $this->assertTrue(
                $databaseConnection->tableExists($physicalTableName),
                sprintf('Physical table %s must exist with FMS prefix.', $physicalTableName)
            );
        }
    }
}
