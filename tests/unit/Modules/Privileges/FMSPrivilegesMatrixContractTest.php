<?php

namespace Tests\Unit\Modules\Privileges;

use App\Modules\Privileges\Contracts\FMSPrivilegesManagementRepositoryInterface;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * UI menampilkan izin menu eksplisit dari t_menu_permissions, bukan tebakan URL.
 */
final class FMSPrivilegesMatrixContractTest extends CIUnitTestCase
{
    public function testManagementRepositoryExposesBatchMenuMappings(): void
    {
        $this->assertTrue(
            (new ReflectionClass(FMSPrivilegesManagementRepositoryInterface::class))
                ->hasMethod('menuPermissionMappingsForMenus'),
        );
    }

    public function testOverviewExposesOpaqueMenuPermissionMappings(): void
    {
        $apiControllerSource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Controllers/Api/FMSPrivilegesApiController.php',
        );
        $backendControllerSource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Controllers/Backend/FMSPrivilegesBackendController.php',
        );

        $this->assertStringContainsString('menuPermissionMappingsForMenus', $apiControllerSource);
        $this->assertStringContainsString('menu_permissions', $apiControllerSource);
        $this->assertStringContainsString('fmsEncodeId((int) $row[\'menu_id\'])', $apiControllerSource);
        $this->assertStringContainsString('fmsEncodeId((int) $row[\'permission_id\'])', $apiControllerSource);
        $this->assertStringNotContainsString('menuPermissionMappingsForMenus', $backendControllerSource);
    }

    public function testViewUsesExplicitMappingsAndMenuLevelToggle(): void
    {
        $viewSource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Views/backend/index.php',
        );

        $this->assertStringContainsString('Bolehkan semua fitur', $viewSource);
        $this->assertStringContainsString('Boleh dibuka', $viewSource);
        $this->assertStringContainsString('Hal yang boleh dilakukan', $viewSource);
        $this->assertStringContainsString('menu_permissions', $viewSource);
        $this->assertStringContainsString('js-menu-access', $viewSource);
        $this->assertStringContainsString('js-action-permission', $viewSource);
        $this->assertStringContainsString('var priorities = { create: 1, update: 2, delete: 3 };', $viewSource);
        $this->assertStringContainsString("document.querySelectorAll('.js-menu-access, .js-action-permission')", $viewSource);
        $this->assertStringContainsString('setAllFeatures(true)', $viewSource);
        $this->assertStringContainsString('setAllFeatures(false)', $viewSource);
        $this->assertStringContainsString("'exclude_super_administrator'", (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Controllers/Api/FMSPrivilegesApiController.php',
        ));
        $this->assertStringContainsString('btn btn-primary btn-glare btn-wave label-btn', $viewSource);
        $this->assertStringContainsString('ri-save-line label-btn-icon', $viewSource);
        $this->assertStringContainsString('Menyimpan...', $viewSource);

        foreach (['moduleFromMenu', 'permissionFor(menu', 'actionFromPermission', 'js-toggle-column'] as $removedFragment) {
            $this->assertStringNotContainsString($removedFragment, $viewSource);
        }
    }

    public function testWildcardLockRequiresExistingGroupGrant(): void
    {
        $viewSource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Views/backend/index.php',
        );

        $this->assertStringContainsString('selectedPermissionHashes.indexOf(permission.hash) !== -1', $viewSource);
        $this->assertStringNotContainsString(
            ".filter(function (permission) { return permissionKey(permission) === WILDCARD_KEY; })",
            $viewSource,
        );
    }

    public function testPermissionButtonOrderPrioritizesCreateUpdateDelete(): void
    {
        $repositorySource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Repositories/FMSDatabasePrivilegesManagementRepository.php',
        );

        $this->assertStringContainsString("CASE action_name WHEN 'create' THEN 1", $repositorySource);
        $this->assertStringContainsString("WHEN 'update' THEN 2", $repositorySource);
        $this->assertStringContainsString("WHEN 'delete' THEN 3", $repositorySource);
        $this->assertStringContainsString('ELSE 4 END', $repositorySource);
    }

    public function testSettingsMenuIsNotAssignableFromPermissionMatrix(): void
    {
        $controllerSource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Controllers/Api/FMSPrivilegesApiController.php',
        );

        $this->assertStringContainsString("trim((string) (\$row['url'] ?? '')) !== 'settings'", $controllerSource);
        $this->assertStringContainsString("trim((string) (\$row['module_name'] ?? '')) !== 'settings'", $controllerSource);
    }

    public function testOperationPermissionCatalogueIncludesExplicitMenuRequirements(): void
    {
        $seederSource = (string) file_get_contents(
            APPPATH . 'Database/Seeds/FMSPrivilegesPermissionSeeder.php',
        );

        /* Modul katalog harus terdaftar di MODULE_ACTIONS */
        foreach (['dashboard', 'brand', 'menus', 'user_groups', 'users', 'privileges', 'activity_logs', /* uploads dihapus */] as $moduleName) {
            $this->assertStringContainsString("'{$moduleName}' =>", $seederSource);
        }
        /* Aksi operasional harus ada di katalog */
        foreach (['assign_groups', 'change_status', 'reset_password', 'revoke_sessions', 'export', 'reorder'] as $actionName) {
            $this->assertStringContainsString($actionName, $seederSource);
        }
        /* Pemetaan eksplisit menu → modul & aturan is_system */
        $this->assertStringContainsString('MENU_MODULE_MAP', $seederSource);
        $this->assertStringContainsString('SYSTEM_GATE_ACTIONS', $seederSource);

        $controllerSource = (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Controllers/Api/FMSPrivilegesApiController.php',
        );
        $this->assertStringContainsString('$isSuperAdministrator = $this->subjectIsSuperAdministrator($authenticatedSubject)', $controllerSource);
        $this->assertStringContainsString("'exclude_super_administrator'", $controllerSource);
        $this->assertStringContainsString("'exclude_super_administrator'", (string) file_get_contents(
            APPPATH . 'Modules/Privileges/Repositories/FMSDatabasePrivilegesManagementRepository.php',
        ));
        $this->assertStringContainsString("permission_key'] ?? '')) !== '*'", $controllerSource);
    }
}
