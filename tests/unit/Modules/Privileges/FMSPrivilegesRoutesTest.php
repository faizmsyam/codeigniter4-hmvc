<?php

namespace Tests\Unit\Modules\Privileges;

use CodeIgniter\Test\CIUnitTestCase;

final class FMSPrivilegesRoutesTest extends CIUnitTestCase
{
    public function testApiRoutesCoverGroupPermissionAndMappingLifecycle(): void
    {
        $apiRoutes = (string) file_get_contents(APPPATH . 'Modules/Privileges/Config/ApiRoutes.php');

        foreach ([
            '$privilegesApiFilter',
            "[FMSPrivilegesApiController::class, 'groups']",
            "[FMSPrivilegesApiController::class, 'createGroup']",
            "[FMSPrivilegesApiController::class, 'group']",
            "[FMSPrivilegesApiController::class, 'updateGroup']",
            "[FMSPrivilegesApiController::class, 'deleteGroup']",
            "[FMSPrivilegesApiController::class, 'groupPermissions']",
            "[FMSPrivilegesApiController::class, 'replaceGroupPermissions']",
            "[FMSPrivilegesApiController::class, 'permissions']",
            "[FMSPrivilegesApiController::class, 'createPermission']",
            "[FMSPrivilegesApiController::class, 'permission']",
            "[FMSPrivilegesApiController::class, 'updatePermission']",
            "[FMSPrivilegesApiController::class, 'deletePermission']",
            "[FMSPrivilegesApiController::class, 'menuPermissions']",
            "[FMSPrivilegesApiController::class, 'replaceMenuPermissions']",
            "[FMSPrivilegesApiController::class, 'userGroups']",
            "[FMSPrivilegesApiController::class, 'replaceUserGroups']",
            "'filter' => \$privilegesApiFilter",
        ] as $expectedRouteFragment) {
            $this->assertStringContainsString($expectedRouteFragment, $apiRoutes);
        }
    }

    public function testOnlyExplicitHttpVerbsAreRegistered(): void
    {
        $apiRoutes = (string) file_get_contents(APPPATH . 'Modules/Privileges/Config/ApiRoutes.php');

        $this->assertStringNotContainsString('routes->add(', $apiRoutes);
        $this->assertStringNotContainsString('routes->match(', $apiRoutes);
        $this->assertStringNotContainsString('routes->resource(', $apiRoutes);
        $this->assertStringContainsString("get('(:segment)/permissions'", $apiRoutes);
        $this->assertStringContainsString("put('(:segment)/permissions'", $apiRoutes);
    }

    public function testControllerReadsOnlyVerifiedRequestContext(): void
    {
        $controllerSource = (string) file_get_contents(APPPATH . 'Modules/Privileges/Controllers/Api/FMSPrivilegesApiController.php');

        $this->assertStringContainsString('FMSRequestContext::authenticatedSubject($this->request)', $controllerSource);
        $this->assertStringNotContainsString('getHeaderLine(\'Authorization\')', $controllerSource);
        $this->assertStringNotContainsString('jwt_decode', $controllerSource);
        $this->assertStringNotContainsString('<?=', $controllerSource);
    }
}
