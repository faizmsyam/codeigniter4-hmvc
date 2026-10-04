<?php

namespace Tests\Unit\Modules\AdminMenus;

use App\Modules\AdminMenus\Contracts\FMSAdminMenuRowProviderInterface;
use App\Modules\AdminMenus\Services\FMSAdminMenuAccessService;
use App\Modules\AdminMenus\Services\FMSAdminMenuTreeService;
use App\Modules\AdminMenus\Services\FMSAdminMenuVisibilityPolicy;
use App\Modules\Privileges\Contracts\FMSPrivilegesPermissionRepositoryInterface;
use App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService;
use CodeIgniter\Test\CIUnitTestCase;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * The access service chains effective permissions (user -> groups -> grants),
 * the menu-permission mapping (`t_menu_permissions`), and the tree policy into
 * one sidebar resolver, so navigation follows the assigned user group.
 */
final class FMSAdminMenuAccessServiceTest extends CIUnitTestCase
{
    private const REFERENCE_TIME = '2026-09-29 10:00:00';

    public function testVisibilityPolicyOpensBoundMenuOnlyForMatchingGroupGrant(): void
    {
        $visibilityPolicy = new FMSAdminMenuVisibilityPolicy();

        $this->assertTrue($visibilityPolicy->isVisible([], []));
        $this->assertTrue($visibilityPolicy->isVisible(['menus.users.read'], ['menus.users.read']));
        $this->assertFalse($visibilityPolicy->isVisible(['menus.users.read'], ['menus.brands.read']));
        $this->assertTrue($visibilityPolicy->isVisible(['menus.users.read'], ['*']));
    }

    public function testSidebarKeepsAncestorContainerAndDropsDeniedMenu(): void
    {
        $accessService = $this->accessServiceFor([
            ['permission_code' => 'menus.brands.read', 'effect' => 'allow', 'is_active' => 1],
            ['permission_code' => 'menus.users.read', 'effect' => 'deny', 'is_active' => 1],
        ]);

        $sidebarTree = $accessService->sidebarForUserIdentifier(
            7,
            new DateTimeImmutable(self::REFERENCE_TIME, new DateTimeZone('UTC')),
        );

        $this->assertSame([1], array_column($sidebarTree, 'id'));
        $this->assertSame([2], array_column($sidebarTree[0]['children'], 'id'));
    }

    public function testSuperAdministratorGrantOpensEveryPermissionBoundMenu(): void
    {
        $accessService = $this->accessServiceFor([
            ['permission_code' => '*', 'effect' => 'allow', 'is_active' => 1, 'is_super_admin' => 1],
        ]);

        $sidebarTree = $accessService->sidebarForUserIdentifier(
            7,
            new DateTimeImmutable(self::REFERENCE_TIME, new DateTimeZone('UTC')),
        );

        $this->assertSame([1], array_column($sidebarTree, 'id'));
        $this->assertSame([2, 3], array_column($sidebarTree[0]['children'], 'id'));
    }

    public function testSidebarCanConsumeExplicitPermissionCodesFromActiveRoleSession(): void
    {
        $accessService = $this->accessServiceFor([
            ['permission_code' => '*', 'effect' => 'allow', 'is_active' => 1, 'is_super_admin' => 1],
        ]);

        /* User punya hak wildcard di level user/multi-group, tetapi session aktif hanya punya menu brand */
        $sidebarTree = $accessService->sidebarForPermissionCodes(
            ['menus.brands.read'],
        );

        $this->assertSame([1], array_column($sidebarTree, 'id'));
        $this->assertSame([2], array_column($sidebarTree[0]['children'], 'id'));
        $this->assertNotContains(3, array_column($sidebarTree[0]['children'], 'id'));
    }

    public function testSidebarRejectsInvalidUserIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->accessServiceFor([])->sidebarForUserIdentifier(0);
    }

    /**
     * @param array<int, array<string, mixed>> $groupPermissionRows
     */
    private function accessServiceFor(array $groupPermissionRows): FMSAdminMenuAccessService
    {
        $permissionRepository = new FakePrivilegesPermissionRepository($groupPermissionRows);

        return new FMSAdminMenuAccessService(
            new FakeAdminMenuRowProvider(),
            new FMSAdminMenuTreeService(),
            new FMSAdminMenuVisibilityPolicy(),
            new FMSPrivilegesEffectivePermissionService($permissionRepository),
        );
    }
}

/**
 * Database-free membership/grant fixture: one active group with the supplied grants.
 */
final class FakePrivilegesPermissionRepository implements FMSPrivilegesPermissionRepositoryInterface
{
    /**
     * @param array<int, array<string, mixed>> $groupPermissionRows
     */
    public function __construct(private readonly array $groupPermissionRows = [])
    {
    }

    /** @return array<int, int> */
    public function loadUserGroupIdentifiers(int $userIdentifier): array
    {
        return [11];
    }

    /** @return array<int, array<string, mixed>> */
    public function loadDirectUserPermissionRows(int $userIdentifier): array
    {
        return [];
    }

    /** @return array<int, array<string, mixed>> */
    public function loadGroupPermissionRows(array $groupIdentifiers): array
    {
        return $this->groupPermissionRows;
    }

    /** @return array<int, array{menu_id: int, permission_code: string}> */
    public function loadMenuPermissionRows(array $menuIdentifiers): array
    {
        return [
            ['menu_id' => 2, 'permission_code' => 'menus.brands.read'],
            ['menu_id' => 3, 'permission_code' => 'menus.users.read'],
        ];
    }
}

/**
 * Database-free menu fixture: container 1 holding brand (2) and users (3).
 */
final class FakeAdminMenuRowProvider implements FMSAdminMenuRowProviderInterface
{
    /** @return array<int, array<string, mixed>> */
    public function findAllOrdered(bool $activeOnly = false): array
    {
        return [
            ['id' => 1, 'id_parent' => null, 'name' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'ri-dashboard-line', 'position' => 1, 'is_active' => 1, 'target_blank' => 0],
            ['id' => 2, 'id_parent' => 1, 'name' => 'Brand', 'url' => 'brand', 'icon' => 'ri-store-line', 'position' => 1, 'is_active' => 1, 'target_blank' => 0],
            ['id' => 3, 'id_parent' => 1, 'name' => 'Users', 'url' => 'users', 'icon' => 'ri-user-line', 'position' => 2, 'is_active' => 1, 'target_blank' => 0],
        ];
    }
}
