<?php

namespace Tests\Unit\Modules\AdminMenus;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Contract for the backend CRUD surface (server-rendered HTML + AJAX).
 * The admin area never re-implements authorization in JavaScript: the page
 * itself is guarded, every AJAX verb re-checks the session permission, and
 * the global FMS.js library owns transport, pagination, summary, and tables.
 */
final class FMSAdminMenusBackendCrudTest extends CIUnitTestCase
{
    public function testBackendRoutesExposeCrudVerbs(): void
    {
        $backendRoutes = (string) file_get_contents(APPPATH . 'Modules/AdminMenus/Config/BackendRoutes.php');

        $this->assertStringContainsString("get('admin-menus'", $backendRoutes);
        $this->assertStringContainsString("get('data'", $backendRoutes);
        $this->assertStringContainsString("get('(:segment)'", $backendRoutes);
        $this->assertStringContainsString("post('data'", $backendRoutes);
        $this->assertStringContainsString("put('(:segment)'", $backendRoutes);
        $this->assertStringContainsString("delete('(:segment)'", $backendRoutes);
        $this->assertStringContainsString("post('reorder'", $backendRoutes);
    }

    public function testBackendControllerAuthorizesEveryVerb(): void
    {
        $controller = (string) file_get_contents(
            APPPATH . 'Modules/AdminMenus/Controllers/Backend/FMSAdminMenusBackendController.php'
        );

        foreach (['menus.view', 'menus.create', 'menus.update', 'menus.delete', 'menus.reorder'] as $permissionCode) {
            $this->assertStringContainsString($permissionCode, $controller);
        }

        $this->assertStringContainsString('FMSApiResponse', $controller);
        $this->assertStringContainsString('fms_backend_permissions', $controller);
    }

    public function testViewsRenderPhpEchoLongFormOnly(): void
    {
        foreach ([
            APPPATH . 'Modules/AdminMenus/Views/backend/index.php',
            APPPATH . 'Views/backend/_partials/sidebar.php',
            APPPATH . 'Views/backend/_partials/breadcrumb.php',
            APPPATH . 'Views/backend/_partials/header.php',
        ] as $viewPath) {
            $this->assertStringNotContainsString('<?=', (string) file_get_contents($viewPath), $viewPath);
        }
    }

    public function testGlobalLibraryIsRegisteredForBackendPages(): void
    {
        $baseController = (string) file_get_contents(APPPATH . 'Core/FMSController.php');

        $this->assertStringContainsString(
            "fmsBottomScript(\$this->assetBasePath.'/js/fms.js')",
            $baseController
        );
        $this->assertFileExists(FCPATH . 'assets/fms/js/fms.js');
    }

    public function testPageTitleComesFromMenuNameWithFallback(): void
    {
        $backendController = (string) file_get_contents(APPPATH . 'Core/FMSBackendController.php');

        $this->assertStringContainsString('fallbackPageTitle', $backendController);
        $this->assertStringContainsString("\$this->fmsMeta(['title' => \$pageTitle])", $backendController);
        $this->assertStringContainsString('getMenuParents', $backendController);
    }
}
