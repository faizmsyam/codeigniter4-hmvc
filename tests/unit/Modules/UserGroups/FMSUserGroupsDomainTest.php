<?php

namespace Tests\Unit\Modules\UserGroups;

use App\Modules\UserGroups\Services\FMSUserGroupService;
use App\Modules\UserGroups\Validation\FMSUserGroupValidation;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * Kontrak domain modul User Groups.
 *
 * @internal
 */
final class FMSUserGroupsDomainTest extends CIUnitTestCase
{
    public function testListAndSelectionEndpointsHideSuperAdministratorForOrdinaryAccounts(): void
    {
        $userGroupController = (string) file_get_contents(
            APPPATH . 'Modules/UserGroups/Controllers/Api/FMSUserGroupsApiController.php'
        );
        $userGroupService = (string) file_get_contents(
            APPPATH . 'Modules/UserGroups/Services/FMSUserGroupService.php'
        );
        $privilegesRepository = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Repositories/FMSDatabasePrivilegesManagementRepository.php'
        );

        $this->assertStringContainsString('isSuperAdministrator($authenticatedSubject)', $userGroupController);
        $this->assertStringContainsString('$includeSuperAdministrator', $userGroupService);
        $this->assertStringContainsString("where('id !=', 1)", $userGroupService);
        $this->assertStringContainsString("'exclude_super_administrator'", $privilegesRepository);
    }

    public function testServiceExposesStandardCrudWorkflow(): void
    {
        foreach (['listGroups', 'listGroupMembers', 'findById', 'createGroup', 'updateGroup', 'deleteGroup', 'restoreGroup', 'changeStatus'] as $method) {
            $this->assertTrue(
                method_exists(FMSUserGroupService::class, $method),
                'FMSUserGroupService harus menyediakan method ' . $method . '.'
            );
        }
    }


    public function testSystemGroupCannotBeDeletedOrHaveItsStatusChanged(): void
    {
        $service = new FMSUserGroupService();

        try {
            $service->deleteGroup(1, 99);
            $this->fail('System group deletion must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('tidak boleh dihapus', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status grup Super Administrator tidak boleh diubah.');
        $service->changeStatus(1, 0, 99);
    }

    public function testMemberQueryUsesLogicalTablesAndReturnsRequiredFields(): void
    {
        $source = (string) file_get_contents(
            APPPATH . 'Modules/UserGroups/Services/FMSUserGroupService.php'
        );

        $this->assertStringContainsString("table('t_user_groups')", $source);
        $this->assertStringContainsString("join('m_users'", $source);

        foreach (['full_name', 'username', 'email', 'is_active'] as $field) {
            $this->assertStringContainsString("'" . $field . "'", $source);
        }
    }

    public function testMembersRoutePrecedesGenericShowRoute(): void
    {
        $source = (string) file_get_contents(
            APPPATH . 'Modules/UserGroups/Config/BackendRoutes.php'
        );
        $membersPosition = strpos($source, "user-groups/(:segment)/members");
        $showPosition = strpos($source, "user-groups/(:segment)',");

        $this->assertNotFalse($membersPosition);
        $this->assertNotFalse($showPosition);
        $this->assertLessThan($showPosition, $membersPosition);
        $this->assertStringContainsString("'members'", $source);
        $this->assertStringNotContainsString("'members/$1'", $source);
    }

    public function testControllerUsesReadForMembersAndRestorePermissionForRestore(): void
    {
        $source = (string) file_get_contents(
            APPPATH . 'Modules/UserGroups/Controllers/Backend/FMSUserGroupsBackendController.php'
        );

        $this->assertMatchesRegularExpression(
            '/public function members\(string \$hash\).*?user_groups\.read/s',
            $source
        );
        $this->assertMatchesRegularExpression(
            '/public function restore\(string \$hash\).*?user_groups\.restore/s',
            $source
        );
    }

    public function testValidationRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FMSUserGroupValidation::validateName('');
    }

    public function testValidationRejectsTooShortName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FMSUserGroupValidation::validateName('ab');
    }

    public function testValidationRejectsTooLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FMSUserGroupValidation::validateName(str_repeat('a', 101));
    }

    public function testValidationAcceptsValidName(): void
    {
        $this->assertSame('Admin Support', FMSUserGroupValidation::validateName('  Admin Support '));
    }

    public function testValidationStatusNormalizesCorrectly(): void
    {
        $this->assertSame(1, FMSUserGroupValidation::validateStatus(1));
        $this->assertSame(0, FMSUserGroupValidation::validateStatus(0));
        $this->assertSame(1, FMSUserGroupValidation::validateStatus(true));
        $this->assertSame(0, FMSUserGroupValidation::validateStatus(false));
        $this->assertSame(1, FMSUserGroupValidation::validateStatus(99)); /* fallback */
    }

    public function testBackendViewUsesLongEchoAndBlockComments(): void
    {
        $viewPath = APPPATH . 'Modules/UserGroups/Views/backend/index.php';
        $this->assertFileExists($viewPath);

        $source = (string) file_get_contents($viewPath);

        $this->assertDoesNotMatchRegularExpression(
            '/<\?=(?!php|xml)/',
            $source,
            'View backend User Groups tidak boleh memakai short-echo <?=.'
        );
        $this->assertDoesNotMatchRegularExpression('/(^|\n)\s*\/\//', $source);
        $this->assertStringContainsString('<div class="row">', $source);
        $this->assertStringContainsString('<div class="col-lg-12">', $source);
        $this->assertStringContainsString('groupMembersModal', $source);
        $this->assertStringContainsString('js-status', $source);
        $this->assertStringContainsString('DOMContentLoaded', $source);
        $this->assertStringNotContainsString('groupIsActive', $source);
        $this->assertStringNotContainsString('fetch(', $source);
        $this->assertStringContainsString('FMS.del(', $source);
        $this->assertStringNotContainsString('confirmDelete', $source);
        $this->assertStringNotContainsString('window.Swal', $source);
        $this->assertStringNotContainsString("confirm(`", $source);
        $this->assertStringNotContainsString('groupDeleteModal', $source);
        $this->assertStringNotContainsString('groupDeleteConfirm', $source);
        $this->assertStringContainsString('FMS.confirm({', $source);
    }

    public function testControllerGatesAllActionsWithUserGroupPermissions(): void
    {
        $source = (string) file_get_contents(
            APPPATH . 'Modules/UserGroups/Controllers/Backend/FMSUserGroupsBackendController.php'
        );

        foreach (['user_groups.read', 'user_groups.create', 'user_groups.update', 'user_groups.delete', 'user_groups.restore'] as $permission) {
            $this->assertStringContainsString($permission, $source);
        }
    }

    public function testViewGatesButtonsWithUserGroupPermissions(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Modules/UserGroups/Views/backend/index.php');

        $this->assertStringContainsString('user_groups.create', $source);
        $this->assertStringContainsString('user_groups.update', $source);
        $this->assertStringContainsString('user_groups.delete', $source);
        $this->assertStringContainsString('user_groups.restore', $source);
    }

    public function testPermissionSeederCoversUserGroupCrud(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Database/Seeds/FMSPrivilegesPermissionSeeder.php');

        foreach (['user_groups', 'read', 'create', 'update', 'delete', 'restore'] as $fragment) {
            $this->assertStringContainsString($fragment, $source);
        }
    }

    public function testMenuSeederPlacesUserGroupsBeforeUsers(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Database/Seeds/FMSCMenusSeeder.php');

        $this->assertStringContainsString('User Groups', $source);
        $this->assertStringContainsString('user-groups', $source);
        $this->assertLessThan(strpos($source, "'name'         => 'Users'"), strpos($source, "'name'         => 'User Groups'"));
    }
}
