<?php

namespace App\Modules\Privileges\Repositories;

use App\Modules\Privileges\Contracts\FMSPrivilegesPermissionRepositoryInterface;
use App\Modules\Privileges\Models\FMSGroupPermissionModel;
use App\Modules\Privileges\Models\FMSGroupUserModel;
use App\Modules\Privileges\Models\FMSMenuPermissionModel;
use App\Modules\Privileges\Models\FMSPermissionModel;
use App\Modules\Privileges\Models\FMSUserGroupModel;

/**
 * Database-backed RBAC row loader.
 *
 * The schema stores membership in `t_user_groups` and grants in
 * `t_group_permissions`, with permission metadata in `c_permissions`. The
 * decision engine receives flattened grant rows keyed by `permission_code`,
 * so the join is collapsed here instead of leaking storage shape into it.
 */
final class FMSDatabasePrivilegesPermissionRepository implements FMSPrivilegesPermissionRepositoryInterface
{
    private const SUPER_ADMIN_PERMISSION_KEY = '*';

    public function __construct(
        private readonly FMSUserGroupModel $userGroupModel = new FMSUserGroupModel(),
        private readonly FMSGroupPermissionModel $groupPermissionModel = new FMSGroupPermissionModel(),
        private readonly FMSPermissionModel $permissionModel = new FMSPermissionModel(),
        private readonly FMSGroupUserModel $groupUserModel = new FMSGroupUserModel(),
        private readonly FMSMenuPermissionModel $menuPermissionModel = new FMSMenuPermissionModel(),
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function loadDirectUserPermissionRows(int $userIdentifier): array
    {
        $userIdentifier = (int) $userIdentifier;
        if ($userIdentifier <= 0) {
            return [];
        }

        // The schema has no direct user-to-permission table: every grant flows
        // through group membership, so there is nothing to load here.
        return [];
    }

    /**
     * @return array<int, int>
     */
    public function loadUserGroupIdentifiers(int $userIdentifier): array
    {
        $userIdentifier = (int) $userIdentifier;
        if ($userIdentifier <= 0) {
            return [];
        }

        $membershipRows = $this->userGroupModel
            ->select('group_id, expires_at')
            ->where('user_id', $userIdentifier)
            ->findAll();

        $activeGroupIdentifiers = [];
        $currentTimestamp = date('Y-m-d H:i:s');
        foreach (is_array($membershipRows) ? $membershipRows : [] as $membershipRow) {
            $expirationTimestamp = (string) ($membershipRow['expires_at'] ?? '');
            if ($expirationTimestamp !== '' && $expirationTimestamp <= $currentTimestamp) {
                continue;
            }

            $groupIdentifier = (int) ($membershipRow['group_id'] ?? 0);
            if ($groupIdentifier > 0) {
                $activeGroupIdentifiers[] = $groupIdentifier;
            }
        }

        return array_values(array_unique($activeGroupIdentifiers));
    }

    /**
     * @param array<int, int> $groupIdentifiers
     * @return array<int, array<string, mixed>>
     */
    public function loadGroupPermissionRows(array $groupIdentifiers): array
    {
        $normalizedGroupIdentifiers = [];
        foreach ($groupIdentifiers as $groupIdentifier) {
            $normalizedGroupIdentifier = (int) $groupIdentifier;
            if ($normalizedGroupIdentifier > 0) {
                $normalizedGroupIdentifiers[] = $normalizedGroupIdentifier;
            }
        }

        if ($normalizedGroupIdentifiers === []) {
            return [];
        }

        $groupRows = $this->groupUserModel
            ->select('id, is_active')
            ->whereIn('id', $normalizedGroupIdentifiers)
            ->findAll();
        $activeGroupByIdentifier = [];
        foreach (is_array($groupRows) ? $groupRows : [] as $groupRow) {
            if ((int) ($groupRow['is_active'] ?? 0) === 1) {
                $activeGroupByIdentifier[(int) $groupRow['id']] = true;
            }
        }

        $activeGroupIdentifiers = array_values(array_filter(
            $normalizedGroupIdentifiers,
            static fn (int $groupIdentifier): bool => isset($activeGroupByIdentifier[$groupIdentifier]),
        ));

        if ($activeGroupIdentifiers === []) {
            return [];
        }

        $grantRows = $this->groupPermissionModel
            ->select('group_id, permission_id, effect')
            ->whereIn('group_id', $activeGroupIdentifiers)
            ->findAll();

        if (! is_array($grantRows) || $grantRows === []) {
            return [];
        }

        $permissionIdentifiers = array_values(array_unique(array_map(
            static fn (array $grantRow): int => (int) ($grantRow['permission_id'] ?? 0),
            $grantRows,
        )));
        $permissionIdentifiers = array_values(array_filter(
            $permissionIdentifiers,
            static fn (int $permissionIdentifier): bool => $permissionIdentifier > 0,
        ));

        if ($permissionIdentifiers === []) {
            return [];
        }

        $permissionRows = $this->permissionModel
            ->select('id, permission_key, is_active')
            ->whereIn('id', $permissionIdentifiers)
            ->findAll();

        $permissionKeyByIdentifier = [];
        foreach (is_array($permissionRows) ? $permissionRows : [] as $permissionRow) {
            $permissionKeyByIdentifier[(int) $permissionRow['id']] = [
                'permission_key' => (string) ($permissionRow['permission_key'] ?? ''),
                'is_active'      => (int) ($permissionRow['is_active'] ?? 0),
            ];
        }

        $flattenedGrantRows = [];
        foreach ($grantRows as $grantRow) {
            $permissionIdentifier = (int) ($grantRow['permission_id'] ?? 0);
            if (! isset($permissionKeyByIdentifier[$permissionIdentifier])) {
                continue;
            }

            $permissionKey = $permissionKeyByIdentifier[$permissionIdentifier]['permission_key'];
            if ($permissionKey === '') {
                continue;
            }

            $flattenedGrantRows[] = [
                'permission_code'  => $permissionKey,
                'effect'           => (string) ($grantRow['effect'] ?? 'allow'),
                'is_active'        => $permissionKeyByIdentifier[$permissionIdentifier]['is_active'],
                'is_super_admin'   => $permissionKey === self::SUPER_ADMIN_PERMISSION_KEY ? 1 : 0,
                'group_id'         => (int) ($grantRow['group_id'] ?? 0),
            ];
        }

        return $flattenedGrantRows;
    }

    /**
     * Gate permission codes required to open the supplied menus.
     *
     * Hanya permission gate (`is_system = 1`, mis. `brand.read`, `menus.view`) yang
     * membuka menu di sidebar. Permission aksi (`is_system = 0`, mis.
     * `brand.update`) tetap mengendalikan tombol/fitur di dalam halaman, tetapi
     * tidak boleh membuka pintu menu. Tanpa aturan ini, `brand.update` yang ON
     * membuat Brand tetap tampil walau `brand.read` OFF — persis bug yang
     * dilaporkan (klik 404 tapi menu tidak hide).
     *
     * @param array<int, int> $menuIdentifiers
     * @return array<int, array{menu_id: int, permission_code: string}>
     */
    public function loadMenuPermissionRows(array $menuIdentifiers): array
    {
        $normalizedMenuIdentifiers = [];
        foreach ($menuIdentifiers as $menuIdentifier) {
            $normalizedMenuIdentifier = (int) $menuIdentifier;
            if ($normalizedMenuIdentifier > 0) {
                $normalizedMenuIdentifiers[] = $normalizedMenuIdentifier;
            }
        }

        if ($normalizedMenuIdentifiers === []) {
            return [];
        }

        $menuPermissionRows = $this->menuPermissionModel
            ->select('menu_id, permission_id')
            ->whereIn('menu_id', array_values(array_unique($normalizedMenuIdentifiers)))
            ->findAll();

        if (! is_array($menuPermissionRows) || $menuPermissionRows === []) {
            return [];
        }

        $permissionIdentifiers = [];
        foreach ($menuPermissionRows as $menuPermissionRow) {
            $permissionIdentifier = (int) ($menuPermissionRow['permission_id'] ?? 0);
            if ($permissionIdentifier > 0) {
                $permissionIdentifiers[] = $permissionIdentifier;
            }
        }

        $permissionIdentifiers = array_values(array_unique($permissionIdentifiers));
        if ($permissionIdentifiers === []) {
            return [];
        }

        $permissionRows = $this->permissionModel
            ->select('id, permission_key, is_active, is_system')
            ->whereIn('id', $permissionIdentifiers)
            ->findAll();

        $permissionCodeByIdentifier = [];
        foreach (is_array($permissionRows) ? $permissionRows : [] as $permissionRow) {
            if ((int) ($permissionRow['is_system'] ?? 0) !== 1) {
                continue;
            }
            if ((int) ($permissionRow['is_active'] ?? 0) !== 1) {
                continue;
            }

            $permissionKey = trim((string) ($permissionRow['permission_key'] ?? ''));
            if ($permissionKey === '') {
                continue;
            }

            $permissionCodeByIdentifier[(int) $permissionRow['id']] = $permissionKey;
        }

        $flattenedMenuPermissionRows = [];
        foreach ($menuPermissionRows as $menuPermissionRow) {
            $menuIdentifier = (int) ($menuPermissionRow['menu_id'] ?? 0);
            $permissionIdentifier = (int) ($menuPermissionRow['permission_id'] ?? 0);
            if ($menuIdentifier <= 0 || ! isset($permissionCodeByIdentifier[$permissionIdentifier])) {
                continue;
            }

            $flattenedMenuPermissionRows[] = [
                'menu_id'         => $menuIdentifier,
                'permission_code' => $permissionCodeByIdentifier[$permissionIdentifier],
            ];
        }

        return $flattenedMenuPermissionRows;
    }
}
