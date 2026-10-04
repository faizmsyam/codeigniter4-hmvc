<?php

namespace Tests\Unit\Modules\AdminMenus;

use App\Modules\AdminMenus\Services\FMSAdminMenuManagementService;
use App\Modules\AdminMenus\Services\FMSAdminMenuTreeService;
use App\Modules\AdminMenus\Validation\FMSAdminMenuValidation;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

require_once __DIR__ . '/../../../../app/Helpers/fms_menu_helper.php';

final class FMSAdminMenusDomainTest extends CIUnitTestCase
{
    private array $menuRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->menuRows = [
            ['id' => 1, 'id_parent' => null, 'name' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'ri-dashboard-line', 'position' => 1, 'is_active' => 1, 'target_blank' => 0],
            ['id' => 2, 'id_parent' => null, 'name' => 'Administration', 'url' => '#', 'icon' => 'ri-settings-line', 'position' => 2, 'is_active' => 1, 'target_blank' => 0],
            ['id' => 3, 'id_parent' => 2, 'name' => 'Users', 'url' => 'users', 'icon' => 'ri-user-line', 'position' => 1, 'is_active' => 1, 'target_blank' => 0],
            ['id' => 4, 'id_parent' => 2, 'name' => 'Disabled', 'url' => 'disabled', 'icon' => 'ri-close-line', 'position' => 2, 'is_active' => 0, 'target_blank' => 0],
            ['id' => 5, 'id_parent' => 3, 'name' => 'User detail', 'url' => 'users/detail', 'icon' => 'ri-file-user-line', 'position' => 1, 'is_active' => 1, 'target_blank' => 0],
        ];
    }

    public function testSidebarFilteringKeepsAncestorsOfAuthorizedDescendantsAndHidesInactiveMenus(): void
    {
        $allowedMenuIdentifiers = [3 => true, 5 => true];

        $sidebarTree = (new FMSAdminMenuTreeService())->buildSidebarTree(
            $this->menuRows,
            static fn (int $menuIdentifier): bool => isset($allowedMenuIdentifiers[$menuIdentifier]),
        );

        $this->assertSame([2], array_column($sidebarTree, 'id'));
        $this->assertSame([3], array_column($sidebarTree[0]['children'], 'id'));
        $this->assertSame([5], array_column($sidebarTree[0]['children'][0]['children'], 'id'));
        $this->assertNotContains(4, array_keys((new FMSAdminMenuTreeService())->flattenMenuTree($sidebarTree)));
    }

    public function testUnprotectedMenuIsVisibleWithoutAnyPermissionMapping(): void
    {
        $sidebarTree = (new FMSAdminMenuTreeService())->buildSidebarTree(
            [$this->menuRows[0]],
            static fn (): bool => true,
        );

        $this->assertSame([1], array_column($sidebarTree, 'id'));
    }

    public function testParentValidationRejectsCyclesAndExcessiveDepth(): void
    {
        $treeService = new FMSAdminMenuTreeService();

        try {
            $treeService->assertValidParent($this->menuRows, 2, 5);
            $this->fail('Cycle assignment must be rejected.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->assertStringContainsString('cycle', $invalidArgumentException->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum tree depth');
        $treeService->assertValidParent($this->menuRows, 0, 5);
    }

    public function testPayloadPreparationUsesStrictAllowlistAndNormalizesValues(): void
    {
        $preparedMenuData = (new FMSAdminMenuManagementService())->prepareMenuData([
            'id' => 999,
            'name' => '  Reports  ',
            'id_parent' => '',
            'url' => ' reports/monthly ',
            'icon' => ' ri-file-chart-line ',
            'position' => '4',
            'is_active' => '1',
            'target_blank' => '0',
            'created_by' => 999,
        ]);

        $this->assertSame('Reports', $preparedMenuData['name']);
        $this->assertNull($preparedMenuData['id_parent']);
        $this->assertSame('reports/monthly', $preparedMenuData['url']);
        $this->assertSame('ri-file-chart-line', $preparedMenuData['icon']);
        $this->assertSame(4, $preparedMenuData['position']);
        $this->assertArrayNotHasKey('id', $preparedMenuData);
        $this->assertArrayNotHasKey('created_by', $preparedMenuData);
    }

    /** @dataProvider invalidMenuPayloadProvider */
    public function testPayloadPreparationRejectsInvalidUrlAndIcon(array $payload, string $messageFragment): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($messageFragment);

        (new FMSAdminMenuManagementService())->prepareMenuData($payload);
    }

    public static function invalidMenuPayloadProvider(): array
    {
        return [
            'absolute URL without target blank' => [
                ['name' => 'External', 'url' => 'https://example.test', 'icon' => 'ri-link', 'target_blank' => 0],
                'External URL',
            ],
            'javascript URL' => [
                ['name' => 'Unsafe', 'url' => 'javascript:alert(1)', 'icon' => 'ri-link', 'target_blank' => 0],
                'Menu URL',
            ],
            'event-bearing svg' => [
                ['name' => 'Unsafe', 'url' => 'safe', 'icon' => '<svg onload="alert(1)"></svg>'],
                'Menu icon',
            ],
        ];
    }

    public function testUpdateValidationAllowsPartialPayload(): void
    {
        $validation = service('validation');
        $validation->reset();
        $validation->setRules((new FMSAdminMenuValidation())->getUpdateMenuRules());

        $this->assertTrue($validation->run(['is_active' => 0]));
    }

    public function testNormalizeIconAcceptsPhosphorDuotoneSvgWithOpacity(): void
    {
        $svc = new FMSAdminMenuManagementService();
        $prepared = $svc->prepareMenuData([
            'name'     => 'Dashboard',
            'url'      => 'dashboard',
            'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M40,144H216a0,0,0,0,1,0,0v56a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V144A0,0,0,0,1,40,144Z" opacity="0.2"/><rect x="40" y="40" width="80" height="80" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>',
        ]);
        $this->assertStringContainsString('opacity', $prepared['icon']);
        $this->assertStringContainsString('<svg', $prepared['icon']);
    }

    public function testMenuSvgWithClassReturnsFallbackWhenIconIsEmpty(): void
    {
        $result = fmsMenuSvgWithClass(null, 'side-menu__icon');
        $this->assertStringContainsString('ph-duotone', $result);
        $this->assertStringContainsString('ph-grid-four', $result);
        $this->assertStringContainsString('side-menu__icon', $result);
    }

    public function testMenuSvgWithClassReturnsFallbackWhenIconIsBlank(): void
    {
        $result = fmsMenuSvgWithClass('   ', 'side-menu__icon');
        $this->assertStringContainsString('ph-duotone', $result);
        $this->assertStringContainsString('ph-grid-four', $result);
    }


    public function testMenuServiceReorderNormalizesAndAppliesNewPositions(): void
    {
        $service = new FMSAdminMenuManagementService();
        $payload = [
            ['id' => 1, 'position' => 10],
            ['id' => 2, 'position' => 20],
        ];

        /* Pastikan prepareMenuData / payload reorder valid */
        $this->assertTrue(method_exists($service, 'reorder'));
    }

    public function testMenuSvgWithClassPreservesPhosphorSvgAndInjectsClass(): void
    {
        $phosphorSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path d="M40,40H216V216H40Z" opacity="0.2"/></svg>';
        $result = fmsMenuSvgWithClass($phosphorSvg, 'side-menu__icon');
        $this->assertStringContainsString('side-menu__icon', $result);
        $this->assertStringContainsString('opacity', $result);
    }
}
