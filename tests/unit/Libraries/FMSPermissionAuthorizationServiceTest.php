<?php

namespace Tests\Unit\Libraries;

use App\Libraries\FMSPermissionAuthorizationService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSPermissionAuthorizationServiceTest extends CIUnitTestCase
{
    public function testExactPermissionGrantsOnlyThatPermission(): void
    {
        $authorizationService = new FMSPermissionAuthorizationService();

        $this->assertTrue($authorizationService->allows(['permissions' => ['brand.read']], 'brand.read'));
        $this->assertFalse($authorizationService->allows(['permissions' => ['brand.read']], 'brand.update'));
        $this->assertFalse($authorizationService->allows(['permissions' => ['brand.read']], 'users.read'));
    }

    public function testNamespaceWildcardAndManageGrantOnlyTheirOwnNamespace(): void
    {
        $authorizationService = new FMSPermissionAuthorizationService();

        $this->assertTrue($authorizationService->allows(['permissions' => ['brand.*']], 'brand.update'));
        $this->assertFalse($authorizationService->allows(['permissions' => ['brand.*']], 'users.read'));

        $this->assertTrue($authorizationService->allows(['permissions' => ['uploads.manage']], 'uploads.delete'));
        $this->assertFalse($authorizationService->allows(['permissions' => ['uploads.manage']], 'brand.update'));
    }

    public function testGlobalWildcardGrantsEveryNamespace(): void
    {
        $authorizationService = new FMSPermissionAuthorizationService();

        $this->assertTrue($authorizationService->allows(['permissions' => ['*']], 'activity_logs.export'));
        $this->assertTrue($authorizationService->isSuperAdministrator(['permissions' => ['*']]));
        $this->assertFalse($authorizationService->isSuperAdministrator(['permissions' => ['brand.*']]));
    }

    public function testScopeStringClaimsAreSplit(): void
    {
        $authorizationService = new FMSPermissionAuthorizationService();

        $this->assertTrue($authorizationService->allows(
            ['scope' => 'profile brand.read uploads.create'],
            'uploads.create',
        ));
        $this->assertFalse($authorizationService->allows(
            ['scope' => 'profile brand.read'],
            'uploads.create',
        ));
    }

    public function testMissingOrEmptyPermissionIsDeniedByDefault(): void
    {
        $authorizationService = new FMSPermissionAuthorizationService();

        $this->assertFalse($authorizationService->allows([], 'brand.read'));
        $this->assertFalse($authorizationService->allows(['permissions' => ['brand.read']], ''));
        $this->assertFalse($authorizationService->allows(['permissions' => ['brand.read']], '   '));
    }

    public function testSelfAccessRequiresMatchingAuthenticatedUuid(): void
    {
        $authorizationService = new FMSPermissionAuthorizationService();
        $userUuid = '11111111-1111-4111-8111-111111111111';

        $this->assertTrue($authorizationService->allows(['sub' => $userUuid], 'users.read', $userUuid, true));
        $this->assertFalse($authorizationService->allows(
            ['sub' => '22222222-2222-4222-8222-222222222222'],
            'users.read',
            $userUuid,
            true,
        ));
        $this->assertFalse($authorizationService->allows(['sub' => $userUuid], '', $userUuid, true));
    }
}
