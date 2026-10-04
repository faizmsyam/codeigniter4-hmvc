<?php

namespace App\Modules\AdminMenus\Services;

/**
 * Pure menu-visibility decision.
 *
 * Membership grants permissions; a menu declares which permissions open it.
 * The rules are intentionally deny-by-default once a menu is permission-bound:
 *
 *  - a menu with no permission mapping is a plain container/navigation entry,
 *    so it stays visible to any authenticated user;
 *  - a menu with a mapping is visible only when the user's effective
 *    permission codes intersect the *gate* mapping (`is_system = 1`), e.g.
 *    `brand.read`. Action permissions (`is_system = 0`, e.g. `brand.update`)
 *    control buttons inside the page but never open the menu itself;
 *  - the global `*` permission (super administrator) opens every menu.
 *
 * Explicit `deny` rows are already removed from the effective permission codes
 * upstream, so a denied code simply never matches here.
 */
final class FMSAdminMenuVisibilityPolicy
{
    public const GLOBAL_WILDCARD = '*';

    /**
     * @param array<int, string> $mappedPermissionCodes
     * @param array<int, string> $effectivePermissionCodes
     */
    public function isVisible(array $mappedPermissionCodes, array $effectivePermissionCodes): bool
    {
        if ($mappedPermissionCodes === []) {
            return true;
        }

        foreach ($effectivePermissionCodes as $effectivePermissionCode) {
            if (trim((string) $effectivePermissionCode) === self::GLOBAL_WILDCARD) {
                return true;
            }
        }

        foreach ($mappedPermissionCodes as $mappedPermissionCode) {
            $normalizedMappedCode = trim((string) $mappedPermissionCode);
            if ($normalizedMappedCode === '') {
                continue;
            }

            foreach ($effectivePermissionCodes as $effectivePermissionCode) {
                if (trim((string) $effectivePermissionCode) === $normalizedMappedCode) {
                    return true;
                }
            }
        }

        return false;
    }
}
