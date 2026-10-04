<?php

namespace App\Modules\Privileges\Contracts;

/**
 * Persistence boundary for the Privileges (RBAC) write model.
 *
 * Implementations own transactions: every mutating method either commits its
 * whole change set or leaves no partial state behind, and every mutation bumps
 * the authentication version of the users whose effective permissions changed.
 */
interface FMSPrivilegesManagementRepositoryInterface
{
    /**
     * @param array<string, mixed> $searchFilters
     * @return array<string, mixed>
     */
    public function paginateGroups(int $pageNumber, int $pageSize, array $searchFilters = []): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findGroupByUuid(string $groupUuid, bool $includeDeleted = false): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findGroupByIdentifier(int $groupIdentifier, bool $includeDeleted = false): ?array;

    /**
     * @param array<string, mixed> $groupData
     * @return array<string, mixed>
     */
    public function createGroup(array $groupData, int $actorIdentifier, array $auditContext = []): array;

    /**
     * @param array<string, mixed> $groupData
     * @return array<string, mixed>|null
     */
    public function updateGroup(string $groupUuid, array $groupData, int $actorIdentifier, array $auditContext = []): ?array;

    public function deleteGroup(string $groupUuid, int $actorIdentifier, array $auditContext = []): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupPermissionMappings(int $groupIdentifier): array;

    /**
     * @param array<int, array{permission_id: int, effect: string}> $permissionMappings
     * @return array<int, array<string, mixed>>
     */
    public function replaceGroupPermissionMappings(int $groupIdentifier, array $permissionMappings, int $actorIdentifier, array $auditContext = []): array;

    /**
     * @param array<string, mixed> $searchFilters
     * @return array<string, mixed>
     */
    public function paginatePermissions(int $pageNumber, int $pageSize, array $searchFilters = []): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findPermissionByUuid(string $permissionUuid, bool $includeDeleted = false): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findPermissionByIdentifier(int $permissionIdentifier, bool $includeDeleted = false): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPermissionsByIdentifiers(array $permissionIdentifiers): array;

    /**
     * @param array<string, mixed> $permissionData
     * @return array<string, mixed>
     */
    public function createPermission(array $permissionData, int $actorIdentifier, array $auditContext = []): array;

    /**
     * @param array<string, mixed> $permissionData
     * @return array<string, mixed>|null
     */
    public function updatePermission(string $permissionUuid, array $permissionData, int $actorIdentifier, array $auditContext = []): ?array;

    public function deletePermission(string $permissionUuid, int $actorIdentifier, array $auditContext = []): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menuPermissionMappings(int $menuIdentifier): array;

    /**
     * @param array<int, int> $menuIdentifiers
     * @return array<int, array<string, mixed>>
     */
    public function menuPermissionMappingsForMenus(array $menuIdentifiers): array;

    /**
     * @param array<int, int> $permissionIdentifiers
     * @return array<int, array<string, mixed>>
     */
    public function replaceMenuPermissionMappings(int $menuIdentifier, array $permissionIdentifiers, int $actorIdentifier, array $auditContext = []): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function userGroupAssignments(int $userIdentifier): array;

    /**
     * @param array<int, array{group_id: int, expires_at: string|null}> $groupAssignments
     * @return array<int, array<string, mixed>>
     */
    public function replaceUserGroupAssignments(int $userIdentifier, array $groupAssignments, int $actorIdentifier, array $auditContext = []): array;

    /**
     * Users whose effective permissions currently include the wildcard grant.
     *
     * @param array<int, int> $excludedGroupIdentifiers
     * @return array<int, int>
     */
    public function superAdminUserIdentifiers(
        array $excludedGroupIdentifiers = [],
        array $excludedPermissionIdentifiers = [],
        int $excludedUserIdentifier = 0,
    ): array;
}
