<?php

namespace Tests\Unit\Modules\Users;

use App\Modules\Users\Services\FMSUserAuthorizationService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSUserAuthorizationServiceTest extends CIUnitTestCase
{
    public function testPermissionClaimsSupportExactManageWildcardAndScopeStrings(): void
    {
        $authorizationService = new FMSUserAuthorizationService();

        $this->assertTrue($authorizationService->allows(['permissions' => ['users.read']], 'users.read'));
        $this->assertTrue($authorizationService->allows(['permissions' => ['users.manage']], 'users.delete'));
        $this->assertTrue($authorizationService->allows(['scope' => 'profile users.*'], 'users.assign_groups'));
        $this->assertTrue($authorizationService->allows(['scopes' => ['*']], 'users.reset_password'));
        $this->assertFalse($authorizationService->allows(['permissions' => ['menus.read']], 'users.read'));
    }

    public function testSelfAccessIsBoundToTheAuthenticatedSubjectUuid(): void
    {
        $authorizationService = new FMSUserAuthorizationService();
        $userUuid = '11111111-1111-4111-8111-111111111111';

        $this->assertTrue($authorizationService->allows(['sub' => $userUuid], 'users.read', $userUuid, true));
        $this->assertFalse($authorizationService->allows(
            ['sub' => '22222222-2222-4222-8222-222222222222'],
            'users.read',
            $userUuid,
            true,
        ));
        $this->assertFalse($authorizationService->allows(['sub' => $userUuid], 'users.read', $userUuid, false));
    }
}
