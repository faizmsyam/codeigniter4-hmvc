<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class FMSDomainSchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $namespace = 'App';

    public function testExpectedPhysicalDomainTablesExistWithFMSPrefix(): void
    {
        $databaseConnection = $this->db;
        $expectedPhysicalTables = [
            'fms_c_brands',
            'fms_c_group_users',
            'fms_t_user_groups',
            'fms_c_permissions',
            'fms_t_group_permissions',
            'fms_t_menu_permissions',
            'fms_t_user_sessions',
            'fms_t_activity_logs',
            'fms_t_file_uploads',
        ];

        foreach ($expectedPhysicalTables as $physicalTableName) {
            $this->assertTrue(
                $databaseConnection->tableExists($physicalTableName),
                sprintf('Physical table %s must exist with FMS prefix.', $physicalTableName)
            );
        }
    }

    public function testActivityLogTableHasPublicUuid(): void
    {
        $tableColumns = array_map(
            static fn (object $columnDefinition): string => $columnDefinition->name,
            $this->db->getFieldData('fms_t_activity_logs')
        );

        $this->assertContains('uuid', $tableColumns);
    }

    public function testFileUploadTableHasPrivateObjectStorageColumns(): void
    {
        $tableColumns = array_map(
            static fn (object $columnDefinition): string => $columnDefinition->name,
            $this->db->getFieldData('fms_t_file_uploads')
        );

        foreach (['uuid', 'storage_driver', 'bucket_name', 'object_key', 'content_hash', 'status'] as $requiredColumn) {
            $this->assertContains($requiredColumn, $tableColumns);
        }
    }
}
