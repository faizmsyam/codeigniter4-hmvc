<?php

namespace Tests\Unit\Modules\Privileges;

use App\Modules\Privileges\Services\FMSPrivilegesAuthorizationService;
use App\Modules\Privileges\Services\FMSPrivilegesManagementService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

final class FMSPrivilegesManagementServiceTest extends CIUnitTestCase
{
    public function testAuthorizationDeniesMissingPermissionAndHonorsExplicitDeny(): void
    {
        $authorizationService = new FMSPrivilegesAuthorizationService();

        $this->assertFalse($authorizationService->allows([], 'privileges.groups.read'));
        $this->assertFalse($authorizationService->allows([
            'permissions' => ['*'],
            'denied_permissions' => ['privileges.groups.read'],
        ], 'privileges.groups.read'));
        $this->assertTrue($authorizationService->allows([
            'permissions' => ['privileges.groups.*'],
        ], 'privileges.groups.read'));
    }

    public function testNormalizationRejectsInvalidAndDuplicateIdentifiers(): void
    {
        $managementService = new FMSPrivilegesManagementService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not contain duplicate identifiers');
        $managementService->normalizeIdentifierList([1, '1'], 'Permission');
    }

    public function testGroupPayloadAndPermissionPayloadUseStrictAllowlists(): void
    {
        $managementService = new FMSPrivilegesManagementService();

        $groupData = $managementService->prepareGroupData([
            'code' => 'FINANCE_ADMIN',
            'name' => 'Finance Administrator',
            'description' => 'Finance access',
            'is_active' => true,
            'is_system' => true,
            'created_by' => 999,
        ], 7, true);
        $permissionData = $managementService->preparePermissionData([
            'permission_key' => 'finance.invoices.read',
            'module_name' => 'finance',
            'action_name' => 'read',
            'description' => 'Read invoices',
            'is_active' => true,
            'deleted_by' => 999,
        ], 7, true);

        $this->assertSame('finance_admin', $groupData['code']);
        $this->assertSame(7, $groupData['created_by']);
        $this->assertArrayNotHasKey('is_system', $groupData);
        $this->assertArrayNotHasKey('deleted_by', $permissionData);
        $this->assertSame(7, $permissionData['created_by']);
        $this->assertSame('finance.invoices.read', $permissionData['permission_key']);
    }

    public function testAssignmentExpiryMustBeInFuture(): void
    {
        $managementService = new FMSPrivilegesManagementService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration must be in the future');
        $managementService->normalizeExpirationTimestamp('2020-01-01 00:00:00');
    }

    public function testPrivilegeEscalationGuardRejectsGrantActorDoesNotOwn(): void
    {
        $managementService = new FMSPrivilegesManagementService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Privilege escalation was refused');
        $managementService->assertNoPrivilegeEscalation(
            [
                ['permission_key' => 'users.delete', 'effect' => 'allow'],
                ['permission_key' => 'users.read', 'effect' => 'deny'],
            ],
            ['users.read'],
            false,
        );
    }
}
