<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Privileges\Contracts;

/**
 * Loads the raw RBAC rows required for effective-permission resolution.
 * Implementations own storage; the Privileges decision engine stays pure.
 *
 * @phpstan-type PermissionGrantRow array{permission_code: string, effect: string, expires_at?: string|null, is_active?: int|string|null, group_id?: int|string|null}
 */
interface FMSPrivilegesPermissionRepositoryInterface
{
    /**
     * Direct user grants, including explicit deny rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadDirectUserPermissionRows(int $userIdentifier): array;

    /**
     * Active group identifiers assigned to the user.
     *
     * @return array<int, int>
     */
    public function loadUserGroupIdentifiers(int $userIdentifier): array;

    /**
     * Grants attached to the supplied groups, including explicit deny rows.
     *
     * @param array<int, int> $groupIdentifiers
     * @return array<int, array<string, mixed>>
     */
    public function loadGroupPermissionRows(array $groupIdentifiers): array;

    /**
     * Permission codes required by the supplied menus, including rows whose
     * permission code is the global `*` super-administrator marker.
     *
     * @param array<int, int> $menuIdentifiers
     * @return array<int, array{menu_id: int, permission_code: string}>
     */
    public function loadMenuPermissionRows(array $menuIdentifiers): array;
}
