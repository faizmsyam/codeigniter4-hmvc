<?php

namespace App\Libraries;

use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository;
use App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService;

/**
 * Resolves the effective permission claim shared by login and refresh flows.
 *
 * JWT claims and backend sessions must use the same permission source so API
 * authorization does not drift from the rendered backend access state.
 */
final class FMSPermissionClaimService
{
    /**
     * @return array<int, string>
     */
    public function forUser(int $userIdentifier): array
    {
        if ($userIdentifier <= 0) {
            return [];
        }

        $permissionCodes = (new FMSPrivilegesEffectivePermissionService(
            new FMSDatabasePrivilegesPermissionRepository(),
        ))->resolveEffectivePermissionCodesForUser($userIdentifier);

        return array_values(array_unique(array_map('strval', $permissionCodes)));
    }

    /**
     * @param array<int, string> $permissionCodes
     * @return array{user_id: int, permissions: array<int, string>}
     */
    public function claims(int $userIdentifier, array $permissionCodes): array
    {
        return [
            'user_id' => $userIdentifier,
            'permissions' => array_values(array_unique(array_map('strval', $permissionCodes))),
        ];
    }
}
