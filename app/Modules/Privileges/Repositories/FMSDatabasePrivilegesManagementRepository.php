<?php

namespace App\Modules\Privileges\Repositories;

use App\Modules\ActivityLogs\Contracts\FMSActivityLogRepositoryInterface;
use App\Modules\ActivityLogs\Models\FMSActivityLogModel;
use App\Modules\ActivityLogs\Services\FMSActivityLogRedactionService;
use App\Modules\ActivityLogs\Services\FMSActivityLogService;
use App\Modules\Privileges\Contracts\FMSPrivilegesManagementRepositoryInterface;
use App\Modules\Privileges\Exceptions\FMSLastSuperAdminProtectionException;
use App\Modules\Privileges\Models\FMSGroupPermissionModel;
use App\Modules\Privileges\Models\FMSGroupUserModel;
use App\Modules\Privileges\Models\FMSMenuPermissionModel;
use App\Modules\Privileges\Models\FMSPermissionModel;
use App\Modules\Privileges\Models\FMSUserGroupModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Transactional write model for the Privileges (RBAC) domain.
 *
 * Each mutating method opens one transaction, validates referenced rows
 * inside that transaction, applies the change, bumps the affected users'
 * `session_version` (so cached sessions and resolved permissions are
 * invalidated immediately), appends an audit entry, and only then commits.
 * Any unexpected failure rolls the whole change set back.
 */
final class FMSDatabasePrivilegesManagementRepository implements FMSPrivilegesManagementRepositoryInterface
{
    private const SUPER_ADMIN_PERMISSION_KEY = '*';

    private readonly BaseConnection $databaseConnection;

    private readonly FMSActivityLogService $activityLogService;

    public function __construct(
        private readonly FMSGroupUserModel $groupModel = new FMSGroupUserModel(),
        private readonly FMSPermissionModel $permissionModel = new FMSPermissionModel(),
        private readonly FMSUserGroupModel $userGroupModel = new FMSUserGroupModel(),
        private readonly FMSGroupPermissionModel $groupPermissionModel = new FMSGroupPermissionModel(),
        private readonly FMSMenuPermissionModel $menuPermissionModel = new FMSMenuPermissionModel(),
        ?BaseConnection $databaseConnection = null,
        ?FMSActivityLogRepositoryInterface $activityLogRepository = null,
    ) {
        $this->databaseConnection = $databaseConnection ?? Database::connect();
        $activityLogRepository ??= new FMSActivityLogModel();
        $this->activityLogService = new FMSActivityLogService(
            $activityLogRepository,
            new FMSActivityLogRedactionService(),
        );
    }

    /**
     * @param array<string, mixed> $searchFilters
     * @return array<string, mixed>
     */
    public function paginateGroups(int $pageNumber, int $pageSize, array $searchFilters = []): array
    {
        $normalizedPageNumber = max(1, $pageNumber);
        $normalizedPageSize = min(100, max(1, $pageSize));
        $groupQuery = clone $this->groupModel;

        $includeDeleted = ($searchFilters['include_deleted'] ?? false) === true;
        if ($includeDeleted) {
            $groupQuery = $groupQuery->withDeleted();
        }
        if (($searchFilters['exclude_super_administrator'] ?? false) === true) {
            $groupQuery->where('id !=', 1);
        }

        $searchTerm = trim((string) ($searchFilters['search'] ?? ''));
        if ($searchTerm !== '') {
            $escapedSearchTerm = $groupQuery->escapeLikeString($searchTerm);
            $groupQuery->groupStart()
                ->like('code', $escapedSearchTerm, 'both', null, true)
                ->orLike('name', $escapedSearchTerm, 'both', null, true)
                ->groupEnd();
        }

        $totalGroups = $groupQuery->countAllResults(false);
        $groupRows = $groupQuery
            ->orderBy('name', 'ASC')
            ->findAll($normalizedPageSize, ($normalizedPageNumber - 1) * $normalizedPageSize);

        return [
            'items'       => is_array($groupRows) ? array_values($groupRows) : [],
            'total'       => $totalGroups,
            'page'        => $normalizedPageNumber,
            'per_page'    => $normalizedPageSize,
            'total_pages' => $totalGroups === 0 ? 0 : (int) ceil($totalGroups / $normalizedPageSize),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findGroupByUuid(string $groupUuid, bool $includeDeleted = false): ?array
    {
        $groupQuery = $includeDeleted ? $this->groupModel->withDeleted() : $this->groupModel;
        $foundGroup = $groupQuery->where('uuid', $groupUuid)->first();

        return is_array($foundGroup) ? $foundGroup : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findGroupByIdentifier(int $groupIdentifier, bool $includeDeleted = false): ?array
    {
        if ($groupIdentifier <= 0) {
            return null;
        }

        $groupQuery = $includeDeleted ? $this->groupModel->withDeleted() : $this->groupModel;
        $foundGroup = $groupQuery->where('id', $groupIdentifier)->first();

        return is_array($foundGroup) ? $foundGroup : null;
    }

    /**
     * @param array<string, mixed> $groupData
     * @return array<string, mixed>
     */
    public function createGroup(array $groupData, int $actorIdentifier, array $auditContext = []): array
    {
        $this->requirePositiveActor($actorIdentifier);
        $this->assertUniqueGroupCode(mb_strtolower(trim((string) ($groupData['code'] ?? '')), 'UTF-8'));

        $preparedGroupData = array_merge($groupData, [
            'created_by' => $actorIdentifier,
            'updated_by' => $actorIdentifier,
            'created_at' => $this->currentTimestamp(),
            'updated_at' => $this->currentTimestamp(),
        ]);

        $this->databaseConnection->transBegin();
        try {
            $insertedGroupIdentifier = $this->groupModel->insert($preparedGroupData, true);
            if ($insertedGroupIdentifier === false) {
                throw new RuntimeException('Group could not be created.');
            }

            $this->touchUserSessionsForGroupMembership((int) $insertedGroupIdentifier, []);
            $this->recordAuditEvent('privileges.group.created', 'privileges', 'groups', (string) $insertedGroupIdentifier, $actorIdentifier, [], $preparedGroupData, $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        $createdGroup = $this->findGroupByIdentifier((int) $insertedGroupIdentifier);
        if ($createdGroup === null) {
            throw new RuntimeException('Group could not be created.');
        }

        return $createdGroup;
    }

    /**
     * @param array<string, mixed> $groupData
     * @return array<string, mixed>|null
     */
    public function updateGroup(string $groupUuid, array $groupData, int $actorIdentifier, array $auditContext = []): ?array
    {
        $this->requirePositiveActor($actorIdentifier);
        $existingGroup = $this->findGroupByUuid($groupUuid);
        if ($existingGroup === null) {
            return null;
        }

        if (array_key_exists('code', $groupData)) {
            $this->assertUniqueGroupCode(
                mb_strtolower(trim((string) $groupData['code']), 'UTF-8'),
                (int) ($existingGroup['id'] ?? 0),
            );
        }

        $beforeGroupRow = $existingGroup;
        $preparedGroupData = array_merge($groupData, [
            'updated_by' => $actorIdentifier,
            'updated_at' => $this->currentTimestamp(),
        ]);

        $this->databaseConnection->transBegin();
        try {
            $updated = $this->groupModel->update((int) $existingGroup['id'], $preparedGroupData);
            if ($updated === false) {
                throw new RuntimeException('Group could not be updated.');
            }

            $this->touchUserSessionsForGroupMembership((int) $existingGroup['id'], []);
            $this->recordAuditEvent('privileges.group.updated', 'privileges', 'groups', $groupUuid, $actorIdentifier, $beforeGroupRow, $preparedGroupData, $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return $this->findGroupByUuid($groupUuid) ?? $beforeGroupRow;
    }

    public function deleteGroup(string $groupUuid, int $actorIdentifier, array $auditContext = []): bool
    {
        $this->requirePositiveActor($actorIdentifier);
        $existingGroup = $this->findGroupByUuid($groupUuid);
        if ($existingGroup === null) {
            return false;
        }

        $groupIdentifier = (int) ($existingGroup['id'] ?? 0);
        $this->databaseConnection->transBegin();
        try {
            $this->assertLastSuperAdminGuardOutsideDeletedGroup([$groupIdentifier], [], 0);

            $deleted = $this->groupModel->delete($groupIdentifier);
            if ($deleted === false) {
                throw new RuntimeException('Group could not be deleted.');
            }

            $this->touchUserSessionsForGroupMembership($groupIdentifier, []);
            $this->recordAuditEvent('privileges.group.deleted', 'privileges', 'groups', $groupUuid, $actorIdentifier, $existingGroup, ['deleted' => true], $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupPermissionMappings(int $groupIdentifier): array
    {
        if ($groupIdentifier <= 0) {
            return [];
        }

        $mappingRows = $this->groupPermissionModel
            ->select('t_group_permissions.group_id, t_group_permissions.permission_id, t_group_permissions.effect, c_permissions.permission_key, c_permissions.module_name, c_permissions.action_name')
            ->join('c_permissions', 'c_permissions.id = t_group_permissions.permission_id', 'inner')
            ->where('t_group_permissions.group_id', $groupIdentifier)
            ->orderBy('c_permissions.permission_key', 'ASC')
            ->findAll();

        return is_array($mappingRows) ? array_values($mappingRows) : [];
    }

    /**
     * @param array<int, array{permission_id: int, effect: string}> $permissionMappings
     * @return array<int, array<string, mixed>>
     */
    public function replaceGroupPermissionMappings(int $groupIdentifier, array $permissionMappings, int $actorIdentifier, array $auditContext = []): array
    {
        $this->requirePositiveActor($actorIdentifier);
        $existingGroup = $this->findGroupByIdentifier($groupIdentifier);
        if ($existingGroup === null) {
            throw new InvalidArgumentException('Group was not found.');
        }

        $normalizedMappings = $this->normalizePermissionMappings($permissionMappings);
        $this->assertPermissionsExist(array_column($normalizedMappings, 'permission_id'));

        $beforeMappings = $this->groupPermissionMappings($groupIdentifier);
        $this->databaseConnection->transBegin();
        try {
            $this->assertLastSuperAdminGuardOutsideDeletedGroup([$groupIdentifier], [], 0);

            $this->databaseConnection
                ->table('t_group_permissions')
                ->where('group_id', $groupIdentifier)
                ->delete();

            $createdTimestamp = $this->currentTimestamp();
            foreach ($normalizedMappings as $normalizedMapping) {
                $inserted = $this->databaseConnection->table('t_group_permissions')->insert([
                    'group_id'      => $groupIdentifier,
                    'permission_id' => $normalizedMapping['permission_id'],
                    'effect'        => $normalizedMapping['effect'],
                    'created_by'    => $actorIdentifier,
                    'created_at'    => $createdTimestamp,
                ]);

                if ($inserted !== true) {
                    throw new RuntimeException('Group-permission mapping could not be saved.');
                }
            }

            $this->assertGroupPermissionChangeKeepsSuperAdmin();
            $this->touchUserSessionsForGroupMembership($groupIdentifier, []);
            $this->recordAuditEvent('privileges.group_permissions.replaced', 'privileges', 'groups', (string) $groupIdentifier, $actorIdentifier, ['mappings' => $beforeMappings], ['mappings' => $normalizedMappings], $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return $this->groupPermissionMappings($groupIdentifier);
    }

    /**
     * @param array<string, mixed> $searchFilters
     * @return array<string, mixed>
     */
    public function paginatePermissions(int $pageNumber, int $pageSize, array $searchFilters = []): array
    {
        $normalizedPageNumber = max(1, $pageNumber);
        $normalizedPageSize = min(100, max(1, $pageSize));
        $permissionQuery = clone $this->permissionModel;

        $includeDeleted = ($searchFilters['include_deleted'] ?? false) === true;
        if ($includeDeleted) {
            $permissionQuery = $permissionQuery->withDeleted();
        }

        $searchTerm = trim((string) ($searchFilters['search'] ?? ''));
        if ($searchTerm !== '') {
            $escapedSearchTerm = $permissionQuery->escapeLikeString($searchTerm);
            $permissionQuery->groupStart()
                ->like('permission_key', $escapedSearchTerm, 'both', null, true)
                ->orLike('module_name', $escapedSearchTerm, 'both', null, true)
                ->groupEnd();
        }

        $totalPermissions = $permissionQuery->countAllResults(false);
        $permissionRows = $permissionQuery
            ->orderBy('permission_key', 'ASC')
            ->findAll($normalizedPageSize, ($normalizedPageNumber - 1) * $normalizedPageSize);

        return [
            'items'       => is_array($permissionRows) ? array_values($permissionRows) : [],
            'total'       => $totalPermissions,
            'page'        => $normalizedPageNumber,
            'per_page'    => $normalizedPageSize,
            'total_pages' => $totalPermissions === 0 ? 0 : (int) ceil($totalPermissions / $normalizedPageSize),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPermissionByUuid(string $permissionUuid, bool $includeDeleted = false): ?array
    {
        $permissionQuery = $includeDeleted ? $this->permissionModel->withDeleted() : $this->permissionModel;
        $foundPermission = $permissionQuery->where('uuid', $permissionUuid)->first();

        return is_array($foundPermission) ? $foundPermission : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPermissionByIdentifier(int $permissionIdentifier, bool $includeDeleted = false): ?array
    {
        if ($permissionIdentifier <= 0) {
            return null;
        }

        $permissionQuery = $includeDeleted ? $this->permissionModel->withDeleted() : $this->permissionModel;
        $foundPermission = $permissionQuery->where('id', $permissionIdentifier)->first();

        return is_array($foundPermission) ? $foundPermission : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPermissionsByIdentifiers(array $permissionIdentifiers): array
    {
        $normalizedIdentifiers = [];
        foreach ($permissionIdentifiers as $permissionIdentifier) {
            $normalizedIdentifier = (int) $permissionIdentifier;
            if ($normalizedIdentifier > 0) {
                $normalizedIdentifiers[] = $normalizedIdentifier;
            }
        }

        $normalizedIdentifiers = array_values(array_unique($normalizedIdentifiers));
        if ($normalizedIdentifiers === []) {
            return [];
        }

        $permissionRows = $this->permissionModel
            ->whereIn('id', $normalizedIdentifiers)
            ->findAll();

        return is_array($permissionRows) ? array_values($permissionRows) : [];
    }

    /**
     * @param array<string, mixed> $permissionData
     * @return array<string, mixed>
     */
    public function createPermission(array $permissionData, int $actorIdentifier, array $auditContext = []): array
    {
        $this->requirePositiveActor($actorIdentifier);
        $this->assertUniquePermissionKey(mb_strtolower(trim((string) ($permissionData['permission_key'] ?? '')), 'UTF-8'));

        $preparedPermissionData = array_merge($permissionData, [
            'created_by' => $actorIdentifier,
            'updated_by' => $actorIdentifier,
            'created_at' => $this->currentTimestamp(),
            'updated_at' => $this->currentTimestamp(),
        ]);

        $this->databaseConnection->transBegin();
        try {
            $insertedPermissionIdentifier = $this->permissionModel->insert($preparedPermissionData, true);
            if ($insertedPermissionIdentifier === false) {
                throw new RuntimeException('Permission could not be created.');
            }

            $this->recordAuditEvent('privileges.permission.created', 'privileges', 'permissions', (string) $insertedPermissionIdentifier, $actorIdentifier, [], $preparedPermissionData, $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        $createdPermission = $this->findPermissionByIdentifier((int) $insertedPermissionIdentifier);
        if ($createdPermission === null) {
            throw new RuntimeException('Permission could not be created.');
        }

        return $createdPermission;
    }

    /**
     * @param array<string, mixed> $permissionData
     * @return array<string, mixed>|null
     */
    public function updatePermission(string $permissionUuid, array $permissionData, int $actorIdentifier, array $auditContext = []): ?array
    {
        $this->requirePositiveActor($actorIdentifier);
        $existingPermission = $this->findPermissionByUuid($permissionUuid);
        if ($existingPermission === null) {
            return null;
        }

        if (array_key_exists('permission_key', $permissionData)) {
            $this->assertUniquePermissionKey(
                mb_strtolower(trim((string) $permissionData['permission_key']), 'UTF-8'),
                (int) ($existingPermission['id'] ?? 0),
            );
        }

        $beforePermissionRow = $existingPermission;
        $preparedPermissionData = array_merge($permissionData, [
            'updated_by' => $actorIdentifier,
            'updated_at' => $this->currentTimestamp(),
        ]);

        $this->databaseConnection->transBegin();
        try {
            $updated = $this->permissionModel->update((int) $existingPermission['id'], $preparedPermissionData);
            if ($updated === false) {
                throw new RuntimeException('Permission could not be updated.');
            }

            $this->touchAllUserSessions();
            $this->recordAuditEvent('privileges.permission.updated', 'privileges', 'permissions', $permissionUuid, $actorIdentifier, $beforePermissionRow, $preparedPermissionData, $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return $this->findPermissionByUuid($permissionUuid) ?? $beforePermissionRow;
    }

    public function deletePermission(string $permissionUuid, int $actorIdentifier, array $auditContext = []): bool
    {
        $this->requirePositiveActor($actorIdentifier);
        $existingPermission = $this->findPermissionByUuid($permissionUuid);
        if ($existingPermission === null) {
            return false;
        }

        $permissionIdentifier = (int) ($existingPermission['id'] ?? 0);
        $this->databaseConnection->transBegin();
        try {
            $this->assertLastSuperAdminGuardOutsideDeletedGroup([], [(int) $permissionIdentifier], 0);

            $deleted = $this->permissionModel->delete($permissionIdentifier);
            if ($deleted === false) {
                throw new RuntimeException('Permission could not be deleted.');
            }

            $this->touchAllUserSessions();
            $this->recordAuditEvent('privileges.permission.deleted', 'privileges', 'permissions', $permissionUuid, $actorIdentifier, $existingPermission, ['deleted' => true], $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return true;
    }

    public function menuActionPermissions(int $menuIdentifier): array
    {
        if ($menuIdentifier <= 0) {
            return [];
        }

        $rows = $this->permissionModel
            ->withDeleted()
            ->select('c_permissions.*')
            ->join('t_menu_permissions', 't_menu_permissions.permission_id = c_permissions.id')
            ->where('t_menu_permissions.menu_id', $menuIdentifier)
            ->where('c_permissions.is_system', 0)
            ->groupStart()
                ->where('c_permissions.source_menu_id', $menuIdentifier)
                ->orWhere('c_permissions.source_menu_id', null)
            ->groupEnd()
            ->orderBy("CASE action_name WHEN 'create' THEN 1 WHEN 'update' THEN 2 WHEN 'delete' THEN 3 ELSE 4 END", '', false)
            ->orderBy('c_permissions.id', 'ASC')
            ->findAll();

        return is_array($rows) ? array_values($rows) : [];
    }

    public function restorePermission(string $permissionUuid, int $actorIdentifier): ?array
    {
        $this->requirePositiveActor($actorIdentifier);
        $permission = $this->findPermissionByUuid($permissionUuid, true);
        if ($permission === null) {
            return null;
        }

        $updated = $this->databaseConnection->table('c_permissions')
            ->where('id', (int) $permission['id'])
            ->update([
                'deleted_at' => null,
                'deleted_by' => null,
                'is_active' => 1,
                'updated_by' => $actorIdentifier,
                'updated_at' => $this->currentTimestamp(),
            ]);
        if ($updated !== true) {
            throw new RuntimeException('Permission could not be restored.');
        }
        $this->touchAllUserSessions();

        return $this->findPermissionByUuid((string) $permission['uuid']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menuPermissionMappings(int $menuIdentifier): array
    {
        if ($menuIdentifier <= 0) {
            return [];
        }

        $mappingRows = $this->menuPermissionModel
            ->select('t_menu_permissions.menu_id, t_menu_permissions.permission_id, c_permissions.permission_key, c_permissions.module_name, c_permissions.action_name')
            ->join('c_permissions', 'c_permissions.id = t_menu_permissions.permission_id', 'inner')
            ->where('t_menu_permissions.menu_id', $menuIdentifier)
            ->orderBy('c_permissions.permission_key', 'ASC')
            ->findAll();

        $singleMappings = is_array($mappingRows) ? array_values($mappingRows) : [];

        return $singleMappings;
    }

    /**
     * @param array<int, int> $menuIdentifiers
     * @return array<int, array<string, mixed>>
     */
    public function menuPermissionMappingsForMenus(array $menuIdentifiers): array
    {
        $normalizedIdentifiers = [];
        foreach ($menuIdentifiers as $menuIdentifier) {
            $identifier = (int) $menuIdentifier;
            if ($identifier > 0) {
                $normalizedIdentifiers[] = $identifier;
            }
        }

        $normalizedIdentifiers = array_values(array_unique($normalizedIdentifiers));
        if ($normalizedIdentifiers === []) {
            return [];
        }

        $mappingRows = $this->menuPermissionModel
            ->select('t_menu_permissions.menu_id, t_menu_permissions.permission_id, c_permissions.permission_key, c_permissions.module_name, c_permissions.action_name')
            ->join('c_permissions', 'c_permissions.id = t_menu_permissions.permission_id', 'inner')
            ->whereIn('t_menu_permissions.menu_id', $normalizedIdentifiers)
            ->orderBy('t_menu_permissions.menu_id', 'ASC')
            ->orderBy('c_permissions.permission_key', 'ASC')
            ->findAll();

        return is_array($mappingRows) ? array_values($mappingRows) : [];
    }

    /**
     * @param array<int, int> $permissionIdentifiers
     * @return array<int, array<string, mixed>>
     */
    public function replaceMenuPermissionMappings(int $menuIdentifier, array $permissionIdentifiers, int $actorIdentifier, array $auditContext = []): array
    {
        $this->requirePositiveActor($actorIdentifier);
        if ($menuIdentifier <= 0) {
            throw new InvalidArgumentException('Menu identifier must be a positive integer.');
        }

        $normalizedIdentifiers = [];
        foreach ($permissionIdentifiers as $permissionIdentifier) {
            $normalizedIdentifier = (int) $permissionIdentifier;
            if ($normalizedIdentifier <= 0) {
                throw new InvalidArgumentException('Menu-permission identifiers must be positive integers.');
            }

            $normalizedIdentifiers[] = $normalizedIdentifier;
        }

        if (count(array_unique($normalizedIdentifiers)) !== count($normalizedIdentifiers)) {
            throw new InvalidArgumentException('Menu-permission identifiers must not contain duplicate identifiers.');
        }

        $this->assertPermissionsExist($normalizedIdentifiers);
        $beforeMappings = $this->menuPermissionMappings($menuIdentifier);

        $this->databaseConnection->transBegin();
        try {
            $this->databaseConnection
                ->table('t_menu_permissions')
                ->where('menu_id', $menuIdentifier)
                ->delete();

            $createdTimestamp = $this->currentTimestamp();
            foreach ($normalizedIdentifiers as $permissionIdentifier) {
                $inserted = $this->databaseConnection->table('t_menu_permissions')->insert([
                    'menu_id'       => $menuIdentifier,
                    'permission_id' => $permissionIdentifier,
                    'created_by'    => $actorIdentifier,
                    'created_at'    => $createdTimestamp,
                ]);

                if ($inserted !== true) {
                    throw new RuntimeException('Menu-permission mapping could not be saved.');
                }
            }

            $this->recordAuditEvent('privileges.menu_permissions.replaced', 'privileges', 'menus', (string) $menuIdentifier, $actorIdentifier, ['mappings' => $beforeMappings], ['permission_ids' => $normalizedIdentifiers], $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return $this->menuPermissionMappings($menuIdentifier);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function userGroupAssignments(int $userIdentifier): array
    {
        if ($userIdentifier <= 0) {
            return [];
        }

        $assignmentRows = $this->userGroupModel
            ->select('t_user_groups.group_id, t_user_groups.expires_at, c_group_users.uuid as group_uuid, c_group_users.code as group_code, c_group_users.name as group_name')
            ->join('c_group_users', 'c_group_users.id = t_user_groups.group_id', 'inner')
            ->where('t_user_groups.user_id', $userIdentifier)
            ->orderBy('c_group_users.name', 'ASC')
            ->findAll();

        return is_array($assignmentRows) ? array_values($assignmentRows) : [];
    }

    /**
     * @param array<int, array{group_id: int, expires_at: string|null}> $groupAssignments
     * @return array<int, array<string, mixed>>
     */
    public function replaceUserGroupAssignments(int $userIdentifier, array $groupAssignments, int $actorIdentifier, array $auditContext = []): array
    {
        $this->requirePositiveActor($actorIdentifier);
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        $targetUser = $this->databaseConnection
            ->table('m_users')
            ->select('id')
            ->where('id', $userIdentifier)
            ->get()
            ->getRowArray();

        if (! is_array($targetUser)) {
            throw new InvalidArgumentException('User was not found.');
        }

        $normalizedAssignments = $this->normalizeGroupAssignments($groupAssignments);
        $this->assertGroupsExist(array_column($normalizedAssignments, 'group_id'));

        $beforeAssignments = $this->userGroupAssignments($userIdentifier);
        $this->databaseConnection->transBegin();
        try {
            $this->assertLastSuperAdminGuardOutsideDeletedGroup([], [], $userIdentifier);

            $this->databaseConnection
                ->table('t_user_groups')
                ->where('user_id', $userIdentifier)
                ->delete();

            $createdTimestamp = $this->currentTimestamp();
            foreach ($normalizedAssignments as $normalizedAssignment) {
                $inserted = $this->databaseConnection->table('t_user_groups')->insert([
                    'user_id'     => $userIdentifier,
                    'group_id'    => $normalizedAssignment['group_id'],
                    'assigned_by' => $actorIdentifier,
                    'expires_at'  => $normalizedAssignment['expires_at'],
                    'created_at'  => $createdTimestamp,
                ]);

                if ($inserted !== true) {
                    throw new RuntimeException('User-group assignment could not be saved.');
                }
            }

            $this->assertGroupPermissionChangeKeepsSuperAdmin();
            $this->touchTargetUserSession($userIdentifier);
            $this->recordAuditEvent('privileges.user_groups.replaced', 'privileges', 'users', (string) $userIdentifier, $actorIdentifier, ['assignments' => $beforeAssignments], ['assignments' => $normalizedAssignments], $auditContext);

            $this->databaseConnection->transCommit();
        } catch (Throwable $unexpectedException) {
            $this->databaseConnection->transRollback();
            throw $unexpectedException;
        }

        return $this->userGroupAssignments($userIdentifier);
    }

    /**
     * @param array<int, int> $excludedGroupIdentifiers
     * @param array<int, int> $excludedPermissionIdentifiers
     * @return array<int, int>
     */
    public function superAdminUserIdentifiers(
        array $excludedGroupIdentifiers = [],
        array $excludedPermissionIdentifiers = [],
        int $excludedUserIdentifier = 0,
    ): array {
        $superAdminGrantRows = $this->databaseConnection
            ->table('t_group_permissions')
            ->select('t_group_permissions.group_id')
            ->join('c_permissions', 'c_permissions.id = t_group_permissions.permission_id', 'inner')
            ->where('c_permissions.permission_key', self::SUPER_ADMIN_PERMISSION_KEY)
            ->where('c_permissions.deleted_at', null)
            ->where('t_group_permissions.effect', 'allow')
            ->get()
            ->getResultArray();

        $grantGroupIdentifiers = [];
        foreach (is_array($superAdminGrantRows) ? $superAdminGrantRows : [] as $superAdminGrantRow) {
            $grantGroupIdentifier = (int) ($superAdminGrantRow['group_id'] ?? 0);
            if ($grantGroupIdentifier > 0 && ! in_array($grantGroupIdentifier, $excludedGroupIdentifiers, true)) {
                $grantGroupIdentifiers[] = $grantGroupIdentifier;
            }
        }

        $grantGroupIdentifiers = array_values(array_unique($grantGroupIdentifiers));
        if ($grantGroupIdentifiers === []) {
            return [];
        }

        $activeGroupRows = $this->databaseConnection
            ->table('c_group_users')
            ->select('id')
            ->whereIn('id', $grantGroupIdentifiers)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        $activeGroupIdentifiers = [];
        foreach (is_array($activeGroupRows) ? $activeGroupRows : [] as $activeGroupRow) {
            $activeGroupIdentifiers[] = (int) ($activeGroupRow['id'] ?? 0);
        }

        $activeGroupIdentifiers = array_values(array_unique(array_filter($activeGroupIdentifiers, static fn (int $activeGroupIdentifier): bool => $activeGroupIdentifier > 0)));
        if ($activeGroupIdentifiers === []) {
            return [];
        }

        $membershipQuery = $this->databaseConnection
            ->table('t_user_groups')
            ->select('user_id, group_id')
            ->whereIn('group_id', $activeGroupIdentifiers);

        $excludedUserIdentifier = (int) $excludedUserIdentifier;
        if ($excludedUserIdentifier > 0) {
            $membershipQuery->where('user_id !=', $excludedUserIdentifier);
        }

        $membershipRows = $membershipQuery->get()->getResultArray();
        if (! is_array($membershipRows) || $membershipRows === []) {
            return [];
        }

        $currentTimestamp = $this->currentTimestamp();
        $superAdminUserIdentifiers = [];
        foreach ($membershipRows as $membershipRow) {
            $expirationTimestamp = $membershipRow['expires_at'] ?? null;
            if ($expirationTimestamp !== null && trim((string) $expirationTimestamp) !== '' && (string) $expirationTimestamp <= $currentTimestamp) {
                continue;
            }

            $memberUserIdentifier = (int) ($membershipRow['user_id'] ?? 0);
            if ($memberUserIdentifier > 0) {
                $superAdminUserIdentifiers[] = $memberUserIdentifier;
            }
        }

        $superAdminUserIdentifiers = array_values(array_unique($superAdminUserIdentifiers));
        if ($excludedPermissionIdentifiers !== [] || $superAdminUserIdentifiers === []) {
            return $superAdminUserIdentifiers;
        }

        return $superAdminUserIdentifiers;
    }

    private function requirePositiveActor(int $actorIdentifier): void
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }
    }

    private function currentTimestamp(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    private function assertUniqueGroupCode(string $groupCode, int $excludeGroupIdentifier = 0): void
    {
        if ($groupCode === '') {
            throw new InvalidArgumentException('Group code must not be empty.');
        }

        $existingGroup = $this->groupModel->withDeleted()->where('code', $groupCode)->first();
        if (is_array($existingGroup) && (int) ($existingGroup['id'] ?? 0) !== $excludeGroupIdentifier) {
            throw new InvalidArgumentException('Group code is already in use.');
        }
    }

    private function assertUniquePermissionKey(string $permissionKey, int $excludePermissionIdentifier = 0): void
    {
        if ($permissionKey === '') {
            throw new InvalidArgumentException('Permission key must not be empty.');
        }

        $existingPermission = $this->permissionModel->withDeleted()->where('permission_key', $permissionKey)->first();
        if (is_array($existingPermission) && (int) ($existingPermission['id'] ?? 0) !== $excludePermissionIdentifier) {
            throw new InvalidArgumentException('Permission key is already in use.');
        }
    }

    /**
     * @param array<int, int> $permissionIdentifiers
     */
    private function assertPermissionsExist(array $permissionIdentifiers): void
    {
        $normalizedIdentifiers = array_values(array_unique(array_map(
            static fn (mixed $permissionIdentifier): int => (int) $permissionIdentifier,
            $permissionIdentifiers,
        )));

        $normalizedIdentifiers = array_values(array_filter($normalizedIdentifiers, static fn (int $permissionIdentifier): bool => $permissionIdentifier > 0));
        if ($normalizedIdentifiers === []) {
            throw new InvalidArgumentException('Permission mapping must not be empty.');
        }

        $existingPermissionRows = $this->permissionModel
            ->select('id')
            ->whereIn('id', $normalizedIdentifiers)
            ->findAll();

        $existingIdentifiers = [];
        foreach (is_array($existingPermissionRows) ? $existingPermissionRows : [] as $existingPermissionRow) {
            $existingIdentifiers[] = (int) ($existingPermissionRow['id'] ?? 0);
        }

        foreach ($normalizedIdentifiers as $normalizedIdentifier) {
            if (! in_array($normalizedIdentifier, $existingIdentifiers, true)) {
                throw new InvalidArgumentException('Permission ' . $normalizedIdentifier . ' was not found.');
            }
        }
    }

    /**
     * @param array<int, int> $groupIdentifiers
     */
    private function assertGroupsExist(array $groupIdentifiers): void
    {
        $normalizedIdentifiers = array_values(array_unique(array_map(
            static fn (mixed $groupIdentifier): int => (int) $groupIdentifier,
            $groupIdentifiers,
        )));

        $normalizedIdentifiers = array_values(array_filter($normalizedIdentifiers, static fn (int $groupIdentifier): bool => $groupIdentifier > 0));
        if ($normalizedIdentifiers === []) {
            throw new InvalidArgumentException('Group assignment must not be empty.');
        }

        $existingGroupRows = $this->groupModel
            ->select('id')
            ->whereIn('id', $normalizedIdentifiers)
            ->findAll();

        $existingIdentifiers = [];
        foreach (is_array($existingGroupRows) ? $existingGroupRows : [] as $existingGroupRow) {
            $existingIdentifiers[] = (int) ($existingGroupRow['id'] ?? 0);
        }

        foreach ($normalizedIdentifiers as $normalizedIdentifier) {
            if (! in_array($normalizedIdentifier, $existingIdentifiers, true)) {
                throw new InvalidArgumentException('Group ' . $normalizedIdentifier . ' was not found.');
            }
        }
    }

    /**
     * @param array<int, array{permission_id: int, effect: string}> $permissionMappings
     * @return array<int, array{permission_id: int, effect: string}>
     */
    private function normalizePermissionMappings(array $permissionMappings): array
    {
        if ($permissionMappings === []) {
            throw new InvalidArgumentException('Permission mapping must not be empty.');
        }

        $normalizedMappings = [];
        $seenPermissionIdentifiers = [];
        foreach (array_values($permissionMappings) as $permissionMapping) {
            if (! is_array($permissionMapping)) {
                throw new InvalidArgumentException('Permission mapping entries must be objects.');
            }

            $permissionIdentifier = (int) ($permissionMapping['permission_id'] ?? 0);
            if ($permissionIdentifier <= 0) {
                throw new InvalidArgumentException('Permission mapping identifiers must be positive integers.');
            }

            if (isset($seenPermissionIdentifiers[$permissionIdentifier])) {
                throw new InvalidArgumentException('Permission mapping must not contain duplicate identifiers.');
            }

            $seenPermissionIdentifiers[$permissionIdentifier] = true;
            $grantEffect = mb_strtolower(trim((string) ($permissionMapping['effect'] ?? 'allow')), 'UTF-8');
            if ($grantEffect !== 'allow' && $grantEffect !== 'deny') {
                throw new InvalidArgumentException('Permission mapping effect must be allow or deny.');
            }

            $normalizedMappings[] = [
                'permission_id' => $permissionIdentifier,
                'effect'        => $grantEffect,
            ];
        }

        return $normalizedMappings;
    }

    /**
     * @param array<int, array{group_id: int, expires_at: string|null}> $groupAssignments
     * @return array<int, array{group_id: int, expires_at: string|null}>
     */
    private function normalizeGroupAssignments(array $groupAssignments): array
    {
        if ($groupAssignments === []) {
            throw new InvalidArgumentException('Group assignment must not be empty.');
        }

        $normalizedAssignments = [];
        $seenGroupIdentifiers = [];
        foreach (array_values($groupAssignments) as $groupAssignment) {
            if (! is_array($groupAssignment)) {
                throw new InvalidArgumentException('Group assignment entries must be objects.');
            }

            $groupIdentifier = (int) ($groupAssignment['group_id'] ?? 0);
            if ($groupIdentifier <= 0) {
                throw new InvalidArgumentException('Group assignment identifiers must be positive integers.');
            }

            if (isset($seenGroupIdentifiers[$groupIdentifier])) {
                throw new InvalidArgumentException('Group assignment must not contain duplicate identifiers.');
            }

            $seenGroupIdentifiers[$groupIdentifier] = true;
            $normalizedAssignments[] = [
                'group_id'   => $groupIdentifier,
                'expires_at' => $groupAssignment['expires_at'] ?? null,
            ];
        }

        return $normalizedAssignments;
    }

    /**
     * @param array<int, int> $excludedGroupIdentifiers
     * @param array<int, int> $excludedPermissionIdentifiers
     */
    private function assertLastSuperAdminGuardOutsideDeletedGroup(
        array $excludedGroupIdentifiers = [],
        array $excludedPermissionIdentifiers = [],
        int $excludedUserIdentifier = 0,
    ): void {
        $remainingSuperAdmins = $this->superAdminUserIdentifiers(
            is_array($excludedGroupIdentifiers) ? $excludedGroupIdentifiers : [$excludedGroupIdentifiers],
            $excludedPermissionIdentifiers,
            $excludedUserIdentifier,
        );

        if ($remainingSuperAdmins === [] && $this->superAdminUserIdentifiers() !== []) {
            throw new FMSLastSuperAdminProtectionException('Operation was refused because it would remove the last super administrator.');
        }
    }

    private function assertGroupPermissionChangeKeepsSuperAdmin(): void
    {
        if ($this->superAdminUserIdentifiers() !== []) {
            return;
        }

        $superAdminPermissionRow = $this->permissionModel
            ->withDeleted()
            ->where('permission_key', self::SUPER_ADMIN_PERMISSION_KEY)
            ->first();

        if (is_array($superAdminPermissionRow)) {
            throw new FMSLastSuperAdminProtectionException('Operation was refused because it would remove the last super administrator.');
        }
    }

    /**
     * @param array<int, int> $extraUserIdentifiers
     */
    private function touchUserSessionsForGroupMembership(int $groupIdentifier, array $extraUserIdentifiers): void
    {
        $memberUserIdentifiers = $extraUserIdentifiers;
        if ($groupIdentifier > 0 && $this->databaseConnection->tableExists('t_user_groups')) {
            $membershipRows = $this->databaseConnection
                ->table('t_user_groups')
                ->select('user_id')
                ->where('group_id', $groupIdentifier)
                ->get()
                ->getResultArray();

            foreach (is_array($membershipRows) ? $membershipRows : [] as $membershipRow) {
                $memberUserIdentifiers[] = (int) ($membershipRow['user_id'] ?? 0);
            }
        }

        $memberUserIdentifiers = array_values(array_unique(array_filter(
            $memberUserIdentifiers,
            static fn (int $memberUserIdentifier): bool => $memberUserIdentifier > 0,
        )));

        if ($memberUserIdentifiers === []) {
            return;
        }

        $this->databaseConnection
            ->table('m_users')
            ->set('session_version', 'session_version + 1', false)
            ->whereIn('id', $memberUserIdentifiers)
            ->update();
    }

    private function touchAllUserSessions(): void
    {
        if (! $this->databaseConnection->tableExists('m_users')) {
            return;
        }

        $this->databaseConnection
            ->table('m_users')
            ->set('session_version', 'session_version + 1', false)
            ->update();
    }

    private function touchTargetUserSession(int $userIdentifier): void
    {
        $this->databaseConnection
            ->table('m_users')
            ->set('session_version', 'session_version + 1', false)
            ->where('id', $userIdentifier)
            ->update();
    }

    /**
     * @param array<string, mixed> $beforePayload
     * @param array<string, mixed> $afterPayload
     * @param array<string, mixed> $auditContext
     */
    private function recordAuditEvent(
        string $eventName,
        string $moduleName,
        string $entityType,
        string $entityIdentifier,
        int $actorIdentifier,
        array $beforePayload,
        array $afterPayload,
        array $auditContext = [],
    ): void {
        try {
            $this->activityLogService->recordActivity(array_merge([
                'event'         => $eventName,
                'module'        => $moduleName,
                'entity_type'   => $entityType,
                'entity_id'     => $entityIdentifier,
                'actor_user_id' => $actorIdentifier,
                'before'        => $beforePayload,
                'after'         => $afterPayload,
            ], $auditContext));
        } catch (Throwable $auditException) {
            log_message('error', 'Privileges audit failed for {event}: {message}', [
                'event'   => $eventName,
                'message' => $auditException->getMessage(),
            ]);

            throw new RuntimeException('Privileges audit trail could not be written.', 0, $auditException);
        }
    }
}
