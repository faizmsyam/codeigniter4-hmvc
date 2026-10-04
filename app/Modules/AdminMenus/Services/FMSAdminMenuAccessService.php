<?php

namespace App\Modules\AdminMenus\Services;

use App\Modules\AdminMenus\Contracts\FMSAdminMenuRowProviderInterface;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository;
use App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Builds the sidebar tree for one authenticated user.
 *
 * The module chain is deliberate: user -> groups -> group permission grants
 * (`FMSPrivilegesEffectivePermissionService`) and menu -> required permissions
 * (`t_menu_permissions`). A menu is returned only when the user's effective
 * permission codes open it, so the navigation follows the assigned user group
 * rather than the logged-in flag alone.
 */
final class FMSAdminMenuAccessService
{
    public function __construct(
        private readonly FMSAdminMenuRowProviderInterface $adminMenuModel =
            new \App\Modules\AdminMenus\Models\FMSAdminMenuModel(),
        private readonly FMSAdminMenuTreeService $adminMenuTreeService = new FMSAdminMenuTreeService(),
        private readonly FMSAdminMenuVisibilityPolicy $visibilityPolicy = new FMSAdminMenuVisibilityPolicy(),
        private readonly FMSPrivilegesEffectivePermissionService $effectivePermissionService = new FMSPrivilegesEffectivePermissionService(
            new FMSDatabasePrivilegesPermissionRepository(),
        ),
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sidebarForUserIdentifier(int $userIdentifier, ?DateTimeImmutable $referenceTime = null): array
    {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        return $this->sidebarForPermissionCodes(
            $this->effectivePermissionService->resolveEffectivePermissionCodesForUser($userIdentifier, $referenceTime),
        );
    }

    /**
     * Build sidebar dari permission context role aktif yang tersimpan di session.
     *
     * @param array<int, string> $permissionCodes
     * @return array<int, array<string, mixed>>
     */
    public function sidebarForPermissionCodes(array $permissionCodes): array
    {
        $activeMenuRows = $this->adminMenuModel->findAllOrdered(true);
        $effectivePermissionCodes = array_values(array_unique(array_filter(
            array_map(static fn($permissionCode): string => trim((string) $permissionCode), $permissionCodes),
            static fn(string $permissionCode): bool => $permissionCode !== '',
        )));

        $menuIdentifiers = [];
        foreach ($activeMenuRows as $activeMenuRow) {
            $menuIdentifiers[] = (int) $activeMenuRow['id'];
        }

        $menuPermissionMap = $this->menuPermissionMap($menuIdentifiers);
        $visibilityPolicy = $this->visibilityPolicy;
        $resolvedPermissionCodes = $effectivePermissionCodes;

        return $this->adminMenuTreeService->buildSidebarTree(
            $activeMenuRows,
            static function (int $menuIdentifier, array $menuRow) use (
                $menuPermissionMap,
                $visibilityPolicy,
                $resolvedPermissionCodes,
            ): bool {
                return $visibilityPolicy->isVisible(
                    $menuPermissionMap[$menuIdentifier] ?? [],
                    $resolvedPermissionCodes,
                );
            },
        );
    }

    /**
     * @param array<int, int> $menuIdentifiers
     * @return array<int, array<int, string>>
     */
    private function menuPermissionMap(array $menuIdentifiers): array
    {
        $normalizedIdentifiers = [];
        foreach ($menuIdentifiers as $menuIdentifier) {
            $normalizedMenuIdentifier = (int) $menuIdentifier;
            if ($normalizedMenuIdentifier > 0) {
                $normalizedIdentifiers[] = $normalizedMenuIdentifier;
            }
        }

        if ($normalizedIdentifiers === []) {
            return [];
        }

        $menuPermissionMap = [];
        foreach ($this->effectivePermissionService->menuPermissionRows(array_values(array_unique($normalizedIdentifiers))) as $menuPermissionRow) {
            $menuIdentifier = (int) ($menuPermissionRow['menu_id'] ?? 0);
            $permissionCode = trim((string) ($menuPermissionRow['permission_code'] ?? ''));
            if ($menuIdentifier <= 0 || $permissionCode === '') {
                continue;
            }

            $menuPermissionMap[$menuIdentifier][] = $permissionCode;
        }

        return $menuPermissionMap;
    }
}
