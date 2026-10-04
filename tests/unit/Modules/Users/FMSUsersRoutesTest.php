<?php

namespace Tests\Unit\Modules\Users;

use CodeIgniter\Test\CIUnitTestCase;

final class FMSUsersRoutesTest extends CIUnitTestCase
{
    public function testApiRoutesCoverUsersLifecycleOperations(): void
    {
        $apiRoutes = (string) file_get_contents(APPPATH . 'Modules/Users/Config/ApiRoutes.php');
        $backendRoutes = (string) file_get_contents(APPPATH . 'Modules/Users/Config/BackendRoutes.php');

        foreach ([
            "[FMSUsersApiController::class, 'index']",
            "[FMSUsersApiController::class, 'show']",
            "[FMSUsersApiController::class, 'create']",
            "[FMSUsersApiController::class, 'update']",
            "[FMSUsersApiController::class, 'destroy']",
            "[FMSUsersApiController::class, 'restore']",
            "[FMSUsersApiController::class, 'unlock']",
            "[FMSUsersApiController::class, 'resetPassword']",
            "[FMSUsersApiController::class, 'verifyEmail']",
            "[FMSUsersApiController::class, 'unverifyEmail']",
            "[FMSUsersApiController::class, 'changeStatus']",
            "[FMSUsersApiController::class, 'sessions']",
            "[FMSUsersApiController::class, 'revokeSessions']",
            "[FMSUsersApiController::class, 'groups']",
            "[FMSUsersApiController::class, 'replaceGroups']",
            '(:segment)',
            "filter' => ",
        ] as $expectedFragment) {
            $this->assertStringContainsString($expectedFragment, $apiRoutes);
        }

        $this->assertStringContainsString("[FMSUsersBackendController::class, 'index']", $backendRoutes);
        $this->assertStringContainsString("'users'", $backendRoutes);
    }

    public function testBackendViewContainsNoShortEchoTags(): void
    {
        $backendView = (string) file_get_contents(APPPATH . 'Modules/Users/Views/backend/index.php');

        $this->assertStringNotContainsString('<?=', $backendView);
        $this->assertStringContainsString('Users', $backendView);
    }
}
