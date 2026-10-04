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
    }
}
