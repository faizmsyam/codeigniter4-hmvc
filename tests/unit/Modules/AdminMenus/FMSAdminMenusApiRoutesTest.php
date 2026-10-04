<?php

namespace Tests\Unit\Modules\AdminMenus;

use App\Modules\AdminMenus\Controllers\Api\FMSAdminMenusApiController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Route-level contract for the AdminMenus CRUD surface.
 *
 * The API routes live in a per-module ApiRoutes.php discovered by the router,
 * so this test asserts the actual registered definitions, the controller
 * methods they point at, and the permission gate each verb uses.
 */
final class FMSAdminMenusApiRoutesTest extends CIUnitTestCase
{
    private string $apiRoutes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiRoutes = (string) file_get_contents(
            APPPATH . 'Modules/AdminMenus/Config/ApiRoutes.php',
        );
    }

    public function testMenuCollectionRoutesAreRegisteredForEveryVerb(): void
    {
        $this->assertStringContainsString(
            "\$routes->get('/', [FMSAdminMenusApiController::class, 'index']",
            $this->apiRoutes,
        );
        $this->assertStringContainsString(
            "\$routes->post('/', [FMSAdminMenusApiController::class, 'create']",
            $this->apiRoutes,
        );
    }

    public function testMenuItemRoutesAreRegisteredForReadUpdateAndDelete(): void
    {
        $this->assertStringContainsString(
            "\$routes->get('(:segment)', [FMSAdminMenusApiController::class, 'show']",
            $this->apiRoutes,
        );
        $this->assertStringContainsString(
            "\$routes->patch('(:segment)', [FMSAdminMenusApiController::class, 'update']",
            $this->apiRoutes,
        );
        $this->assertStringContainsString(
            "\$routes->delete('(:segment)', [FMSAdminMenusApiController::class, 'delete']",
            $this->apiRoutes,
        );
    }

    public function testReorderIsExposedAsPatchToMatchTheDocumentedContract(): void
    {
        $this->assertStringContainsString(
            "\$routes->patch('reorder', [FMSAdminMenusApiController::class, 'reorder']",
            $this->apiRoutes,
        );
    }

    public function testEveryMenuMutationAndReadRouteRequiresJwtAuthentication(): void
    {
        $menuRouteGroupCount = substr_count($this->apiRoutes, "\$routes->group('menus'");
        $this->assertSame(1, $menuRouteGroupCount);
        $this->assertStringContainsString("['filter' => 'fms-jwt-authentication']", $this->apiRoutes);
    }

    public function testControllerExposesTheCrudActionsReferencedByTheRoutes(): void
    {
        foreach (['index', 'show', 'create', 'update', 'delete', 'reorder', 'sidebar'] as $controllerAction) {
            $this->assertTrue(
                method_exists(FMSAdminMenusApiController::class, $controllerAction),
                sprintf('FMSAdminMenusApiController must expose %s().', $controllerAction),
            );
        }
    }

    public function testControllerGatesEachVerbWithItsOwnPermissionKey(): void
    {
        $controllerSource = (string) file_get_contents(
            APPPATH . 'Modules/AdminMenus/Controllers/Api/FMSAdminMenusApiController.php',
        );

        foreach (['menus.view', 'menus.create', 'menus.update', 'menus.delete', 'menus.reorder'] as $permissionKey) {
            $this->assertStringContainsString(
                $permissionKey,
                $controllerSource,
                sprintf('Menu API must gate actions with %s.', $permissionKey),
            );
        }
    }
}
