<?php

namespace App\Modules\Privileges\Controllers\Api;

use App\Core\FMSApiController;
use App\Filters\FMSRequestContext;
use App\Modules\AdminMenus\Models\FMSAdminMenuModel;
use App\Modules\Privileges\Contracts\FMSPrivilegesManagementRepositoryInterface;
use App\Modules\Privileges\Exceptions\FMSLastSuperAdminProtectionException;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesManagementRepository;
use App\Modules\Privileges\Services\FMSPrivilegesAuthorizationService;
use App\Modules\Privileges\Services\FMSPrivilegesManagementService;
use App\Modules\Privileges\Validation\FMSPrivilegesValidation;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Thin authenticated API boundary for RBAC administration.
 *
 * Authentication context is read only from FMSRequestContext, which is set by
 * the verified JWT filter. Every operation then performs its own permission
 * check; authenticated does not imply authorized.
 */
final class FMSPrivilegesApiController extends FMSApiController
{
    private FMSPrivilegesManagementRepositoryInterface $managementRepository;

    private FMSPrivilegesManagementService $managementService;

    private FMSPrivilegesAuthorizationService $privilegesAuthorizationService;

    public function __construct(
        ?FMSPrivilegesManagementRepositoryInterface $managementRepository = null,
        ?FMSPrivilegesManagementService $managementService = null,
        ?FMSPrivilegesAuthorizationService $authorizationService = null,
    ) {
        parent::__construct();
        $this->managementRepository = $managementRepository ?? new FMSDatabasePrivilegesManagementRepository();
        $this->managementService = $managementService ?? new FMSPrivilegesManagementService();
        $this->privilegesAuthorizationService = $authorizationService ?? new FMSPrivilegesAuthorizationService();
    }

    public function groups(): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.groups.read');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $pageNumber = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 25)));
        $result = $this->managementRepository->paginateGroups($pageNumber, $pageSize, [
            'search'                      => trim((string) ($this->request->getGet('search') ?? '')),
            'include_deleted'             => $this->request->getGet('include_deleted') === '1',
            'exclude_super_administrator' => ! $this->subjectIsSuperAdministrator($authenticatedSubject),
        ]);

        return $this->respondSuccess(200, 'Daftar group berhasil dimuat.', $result);
    }

    public function group(string $groupUuid): ResponseInterface
    {
        if ($this->authorizedSubject('privileges.groups.read') === null) {
            return $this->forbiddenResponse();
        }

        if (! FMSPrivilegesValidation::isValidUuid($groupUuid)) {
            return $this->respondValidationError(['group_uuid' => ['UUID group tidak valid.']]);
        }

        $groupRow = $this->managementRepository->findGroupByUuid($groupUuid);
        if ($groupRow === null) {
            return $this->respondError(404, 'Group tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Group berhasil dimuat.', $groupRow);
    }

    public function createGroup(): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.groups.create');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validateGroupCreate($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $createdGroup = $this->managementRepository->createGroup(
                $this->managementService->prepareGroupData($requestPayload, $this->actorIdentifier($authenticatedSubject), true),
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            return $this->respondSuccess(201, 'Group berhasil dibuat.', $createdGroup, 201);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Group creation', 'FMS_PRIVILEGES_GROUP_CREATE_FAILED', 'Group gagal dibuat.', $unexpectedException);
        }
    }

    public function updateGroup(string $groupUuid): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.groups.update');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        if (! FMSPrivilegesValidation::isValidUuid($groupUuid)) {
            return $this->respondValidationError(['group_uuid' => ['UUID group tidak valid.']]);
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validateGroupUpdate($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $updatedGroup = $this->managementRepository->updateGroup(
                $groupUuid,
                $this->managementService->prepareGroupData($requestPayload, $this->actorIdentifier($authenticatedSubject), false),
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            if ($updatedGroup === null) {
                return $this->respondError(404, 'Group tidak ditemukan.', null, 404);
            }

            return $this->respondSuccess(200, 'Group berhasil diperbarui.', $updatedGroup);
        } catch (FMSLastSuperAdminProtectionException $protectionException) {
            return $this->lastSuperAdminResponse($protectionException);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Group update', 'FMS_PRIVILEGES_GROUP_UPDATE_FAILED', 'Group gagal diperbarui.', $unexpectedException);
        }
    }

    public function deleteGroup(string $groupUuid): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.groups.delete');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        if (! FMSPrivilegesValidation::isValidUuid($groupUuid)) {
            return $this->respondValidationError(['group_uuid' => ['UUID group tidak valid.']]);
        }

        try {
            if (! $this->managementRepository->deleteGroup($groupUuid, $this->actorIdentifier($authenticatedSubject), $this->auditContext())) {
                return $this->respondError(404, 'Group tidak ditemukan.', null, 404);
            }

            return $this->respondSuccess(200, 'Group berhasil dihapus.');
        } catch (FMSLastSuperAdminProtectionException $protectionException) {
            return $this->lastSuperAdminResponse($protectionException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Group deletion', 'FMS_PRIVILEGES_GROUP_DELETE_FAILED', 'Group gagal dihapus.', $unexpectedException);
        }
    }

    public function groupPermissions(string $groupUuid): ResponseInterface
    {
        if ($this->authorizedSubject('privileges.group_permissions.read') === null) {
            return $this->forbiddenResponse();
        }

        $groupRow = $this->groupByValidatedUuid($groupUuid);
        if ($groupRow instanceof ResponseInterface) {
            return $groupRow;
        }

        return $this->respondSuccess(200,
            'Mapping group-permission berhasil dimuat.',
            ['permissions' => $this->managementRepository->groupPermissionMappings((int) $groupRow['id'])],
        );
    }

    public function replaceGroupPermissions(string $groupUuid): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.group_permissions.update');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $groupRow = $this->groupByValidatedUuid($groupUuid);
        if ($groupRow instanceof ResponseInterface) {
            return $groupRow;
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validateGroupPermissionMapping($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        $permissionMappings = $this->normalizedPermissionMappings($requestPayload);
        try {
            $permissionRows = $this->managementRepository->findPermissionsByIdentifiers(array_column($permissionMappings, 'permission_id'));
            $permissionKeyByIdentifier = [];
            foreach ($permissionRows as $permissionRow) {
                $permissionKeyByIdentifier[(int) ($permissionRow['id'] ?? 0)] = (string) ($permissionRow['permission_key'] ?? '');
            }

            $grantEntries = [];
            foreach ($permissionMappings as $permissionMapping) {
                $grantEntries[] = [
                    'permission_key' => $permissionKeyByIdentifier[$permissionMapping['permission_id']] ?? '',
                    'effect'         => $permissionMapping['effect'],
                ];
            }

            $this->managementService->assertNoPrivilegeEscalation(
                $grantEntries,
                $this->subjectPermissionKeys($authenticatedSubject),
                $this->subjectIsSuperAdministrator($authenticatedSubject),
            );

            $savedMappings = $this->managementRepository->replaceGroupPermissionMappings(
                (int) $groupRow['id'],
                $permissionMappings,
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            return $this->respondSuccess(200, 'Mapping group-permission berhasil disimpan.', ['permissions' => $savedMappings]);
        } catch (FMSLastSuperAdminProtectionException $protectionException) {
            return $this->lastSuperAdminResponse($protectionException);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Group-permission replacement', 'FMS_PRIVILEGES_GROUP_PERMISSIONS_UPDATE_FAILED', 'Mapping group-permission gagal disimpan.', $unexpectedException);
        }
    }

    public function permissions(): ResponseInterface
    {
        if ($this->authorizedSubject('privileges.permissions.read') === null) {
            return $this->forbiddenResponse();
        }

        $pageNumber = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 25)));

        return $this->respondSuccess(200,
            'Daftar permission berhasil dimuat.',
            $this->managementRepository->paginatePermissions($pageNumber, $pageSize, [
                'search'          => trim((string) ($this->request->getGet('search') ?? '')),
                'include_deleted' => $this->request->getGet('include_deleted') === '1',
            ]),
        );
    }

    public function permission(string $permissionUuid): ResponseInterface
    {
        if ($this->authorizedSubject('privileges.permissions.read') === null) {
            return $this->forbiddenResponse();
        }

        if (! FMSPrivilegesValidation::isValidUuid($permissionUuid)) {
            return $this->respondValidationError(['permission_uuid' => ['UUID permission tidak valid.']]);
        }

        $permissionRow = $this->managementRepository->findPermissionByUuid($permissionUuid);
        if ($permissionRow === null) {
            return $this->respondError(404, 'Permission tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Permission berhasil dimuat.', $permissionRow);
    }

    public function createPermission(): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.permissions.create');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validatePermissionCreate($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $permissionData = $this->managementService->preparePermissionData(
                $requestPayload,
                $this->actorIdentifier($authenticatedSubject),
                true,
            );
            $this->managementService->assertNoPrivilegeEscalation(
                [['permission_key' => $permissionData['permission_key'], 'effect' => 'allow']],
                $this->subjectPermissionKeys($authenticatedSubject),
                $this->subjectIsSuperAdministrator($authenticatedSubject),
            );

            $createdPermission = $this->managementRepository->createPermission(
                $permissionData,
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            return $this->respondSuccess(201, 'Permission berhasil dibuat.', $createdPermission, 201);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Permission creation', 'FMS_PRIVILEGES_PERMISSION_CREATE_FAILED', 'Permission gagal dibuat.', $unexpectedException);
        }
    }

    public function updatePermission(string $permissionUuid): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.permissions.update');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        if (! FMSPrivilegesValidation::isValidUuid($permissionUuid)) {
            return $this->respondValidationError(['permission_uuid' => ['UUID permission tidak valid.']]);
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validatePermissionUpdate($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $permissionData = $this->managementService->preparePermissionData(
                $requestPayload,
                $this->actorIdentifier($authenticatedSubject),
                false,
            );
            if (isset($permissionData['permission_key'])) {
                $this->managementService->assertNoPrivilegeEscalation(
                    [['permission_key' => $permissionData['permission_key'], 'effect' => 'allow']],
                    $this->subjectPermissionKeys($authenticatedSubject),
                    $this->subjectIsSuperAdministrator($authenticatedSubject),
                );
            }

            $updatedPermission = $this->managementRepository->updatePermission(
                $permissionUuid,
                $permissionData,
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            if ($updatedPermission === null) {
                return $this->respondError(404, 'Permission tidak ditemukan.', null, 404);
            }

            return $this->respondSuccess(200, 'Permission berhasil diperbarui.', $updatedPermission);
        } catch (FMSLastSuperAdminProtectionException $protectionException) {
            return $this->lastSuperAdminResponse($protectionException);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Permission update', 'FMS_PRIVILEGES_PERMISSION_UPDATE_FAILED', 'Permission gagal diperbarui.', $unexpectedException);
        }
    }

    public function overviewWithHashes(): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.manage');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $isSuperAdministrator = $this->subjectIsSuperAdministrator($authenticatedSubject);
        $repository = new FMSDatabasePrivilegesManagementRepository();
        $menuRows = array_values(array_filter(
            (new FMSAdminMenuModel())->findAllOrdered(),
            static fn (array $row): bool => trim((string) ($row['url'] ?? '')) !== 'settings',
        ));
        $menuIdentifiers = array_map(static fn (array $row): int => (int) $row['id'], $menuRows);
        $menuPermissionRows = $repository->menuPermissionMappingsForMenus($menuIdentifiers);
        $overviewGroups = $repository->paginateGroups(1, 100, [
            'exclude_super_administrator' => ! $isSuperAdministrator,
        ])['items'];
        $overviewPermissions = array_values(array_filter(
            $repository->paginatePermissions(1, 100)['items'],
            static fn (array $row): bool => trim((string) ($row['permission_key'] ?? '')) !== '*'
                && trim((string) ($row['module_name'] ?? '')) !== 'settings',
        ));
        return $this->respondSuccess(200, 'Data privileges berhasil dimuat.', [
            'groups' => array_map(fn (array $row): array => $this->withRowHash($row), $overviewGroups),
            'permissions' => array_map(fn (array $row): array => $this->withRowHash($row), $overviewPermissions),
            'menus' => array_map(fn (array $row): array => $this->withRowHash($row), $menuRows),
            'menu_permissions' => array_map(static function (array $row): array {
                return [
                    'menu_id' => fmsEncodeId((int) $row['menu_id']),
                    'permission_id' => fmsEncodeId((int) $row['permission_id']),
                    'permission_key' => (string) $row['permission_key'],
                ];
            }, $menuPermissionRows),
        ]);
    }

    public function groupPermissionsWithHash(string $groupHash): ResponseInterface
    {
        if ($this->authorizedSubject('privileges.manage') === null) {
            return $this->forbiddenResponse();
        }
        $groupIdentifier = fmsDecodeId($groupHash);
        if (! $groupIdentifier) {
            return $this->respondError(404, 'Group tidak ditemukan.', null, 404);
        }
        $repository = new FMSDatabasePrivilegesManagementRepository();
        return $this->respondSuccess(200, 'Permission group berhasil dimuat.', [
            'items' => array_map(function (array $item): array {
                $item['permission_id'] = fmsEncodeId((int) $item['permission_id']);
                return $item;
            }, $repository->groupPermissionMappings($groupIdentifier)),
        ]);
    }

    public function replaceGroupPermissionsWithHash(string $groupHash): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.manage');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }
        $groupIdentifier = fmsDecodeId($groupHash);
        if (! $groupIdentifier) {
            return $this->respondError(404, 'Group tidak ditemukan.', null, 404);
        }
        $payload = $this->requestPayload();
        $mappings = is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [];
        $resolvedMappings = [];
        foreach ($mappings as $mapping) {
            $permissionId = fmsDecodeId($mapping['permission_id'] ?? '');
            if ($permissionId) {
                $resolvedMappings[] = ['permission_id' => $permissionId, 'effect' => $mapping['effect'] ?? 'allow'];
            }
        }
        try {
            $items = $this->managementRepository->replaceGroupPermissionMappings($groupIdentifier, $resolvedMappings, $this->actorIdentifier($authenticatedSubject));
        } catch (FMSLastSuperAdminProtectionException $e) {
            return $this->respondError(422, 'Gagal menyimpan: Sistem menolak mencabut permission\'*\'. Harus ada minimal 1 Super Admin yang tersisa.', null, 422);
        } catch (Throwable $exception) {
            return $this->respondError(422, $exception->getMessage(), null, 422);
        }
        return $this->respondSuccess(200, 'Permission group berhasil disimpan.', [
            'items' => array_map(function ($item) {
                $item['permission_id'] = fmsEncodeId((int) $item['permission_id']);
                return $item;
            }, $items),
        ]);
    }

    private function withRowHash(array $row): array
    {
        if (isset($row['id'])) {
            $row['hash'] = fmsEncodeId((int) $row['id']);
        }
        return $row;
    }

    public function deletePermission(string $permissionUuid): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.permissions.delete');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        if (! FMSPrivilegesValidation::isValidUuid($permissionUuid)) {
            return $this->respondValidationError(['permission_uuid' => ['UUID permission tidak valid.']]);
        }

        try {
            if (! $this->managementRepository->deletePermission($permissionUuid, $this->actorIdentifier($authenticatedSubject), $this->auditContext())) {
                return $this->respondError(404, 'Permission tidak ditemukan.', null, 404);
            }

            return $this->respondSuccess(200, 'Permission berhasil dihapus.');
        } catch (FMSLastSuperAdminProtectionException $protectionException) {
            return $this->lastSuperAdminResponse($protectionException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Permission deletion', 'FMS_PRIVILEGES_PERMISSION_DELETE_FAILED', 'Permission gagal dihapus.', $unexpectedException);
        }
    }

    public function menuPermissions(string $menuIdentifier): ResponseInterface
    {
        if ($this->authorizedSubject('privileges.menu_permissions.read') === null) {
            return $this->forbiddenResponse();
        }

        $normalizedMenuIdentifier = $this->positiveIdentifier($menuIdentifier);
        if ($normalizedMenuIdentifier === null) {
            return $this->respondValidationError(['menu_id' => ['Identifier menu harus berupa integer positif.']]);
        }

        return $this->respondSuccess(200,
            'Mapping menu-permission berhasil dimuat.',
            ['permissions' => $this->managementRepository->menuPermissionMappings($normalizedMenuIdentifier)],
        );
    }

    public function replaceMenuPermissions(string $menuIdentifier): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.menu_permissions.update');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $normalizedMenuIdentifier = $this->positiveIdentifier($menuIdentifier);
        if ($normalizedMenuIdentifier === null) {
            return $this->respondValidationError(['menu_id' => ['Identifier menu harus berupa integer positif.']]);
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validateMenuPermissionMapping($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $permissionIdentifiers = $this->normalizedMenuPermissionIdentifiers($requestPayload);
            $permissionRows = $this->managementRepository->findPermissionsByIdentifiers($permissionIdentifiers);
            $grantEntries = array_map(
                static fn (array $permissionRow): array => [
                    'permission_key' => (string) ($permissionRow['permission_key'] ?? ''),
                    'effect'         => 'allow',
                ],
                $permissionRows,
            );
            $this->managementService->assertNoPrivilegeEscalation(
                $grantEntries,
                $this->subjectPermissionKeys($authenticatedSubject),
                $this->subjectIsSuperAdministrator($authenticatedSubject),
            );

            $savedMappings = $this->managementRepository->replaceMenuPermissionMappings(
                $normalizedMenuIdentifier,
                $permissionIdentifiers,
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            return $this->respondSuccess(200, 'Mapping menu-permission berhasil disimpan.', ['permissions' => $savedMappings]);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('Menu-permission replacement', 'FMS_PRIVILEGES_MENU_PERMISSIONS_UPDATE_FAILED', 'Mapping menu-permission gagal disimpan.', $unexpectedException);
        }
    }

    public function userGroups(string $userIdentifier): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.user_groups.read');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $normalizedUserIdentifier = $this->positiveIdentifier($userIdentifier);
        if ($normalizedUserIdentifier === null) {
            return $this->respondValidationError(['user_id' => ['Identifier user harus berupa integer positif.']]);
        }

        return $this->respondSuccess(200,
            'Assignment user-group berhasil dimuat.',
            ['groups' => $this->managementRepository->userGroupAssignments($normalizedUserIdentifier)],
        );
    }

    public function replaceUserGroups(string $userIdentifier): ResponseInterface
    {
        $authenticatedSubject = $this->authorizedSubject('privileges.user_groups.update');
        if ($authenticatedSubject === null) {
            return $this->forbiddenResponse();
        }

        $normalizedUserIdentifier = $this->positiveIdentifier($userIdentifier);
        if ($normalizedUserIdentifier === null) {
            return $this->respondValidationError(['user_id' => ['Identifier user harus berupa integer positif.']]);
        }

        $requestPayload = $this->requestPayload();
        $validationErrors = FMSPrivilegesValidation::validateUserGroupAssignment($requestPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $groupAssignments = $this->normalizedGroupAssignments($requestPayload);
            $savedAssignments = $this->managementRepository->replaceUserGroupAssignments(
                $normalizedUserIdentifier,
                $groupAssignments,
                $this->actorIdentifier($authenticatedSubject),
                $this->auditContext(),
            );

            return $this->respondSuccess(200, 'Assignment user-group berhasil disimpan.', ['groups' => $savedAssignments]);
        } catch (FMSLastSuperAdminProtectionException $protectionException) {
            return $this->lastSuperAdminResponse($protectionException);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->domainValidationResponse($invalidArgumentException);
        } catch (Throwable $unexpectedException) {
            return $this->unexpectedErrorResponse('User-group replacement', 'FMS_PRIVILEGES_USER_GROUPS_UPDATE_FAILED', 'Assignment user-group gagal disimpan.', $unexpectedException);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authorizedSubject(string $requiredPermission): ?array
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return null;
        }

        return $this->privilegesAuthorizationService->allows($authenticatedSubject, $requiredPermission)
            ? $authenticatedSubject
            : null;
    }

    private function forbiddenResponse(): ResponseInterface
    {
        return $this->respondError(403, 'Akses ditolak.', null, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(): array
    {
        $decodedPayload = $this->request->getJSON(true);
        if (is_array($decodedPayload)) {
            return $decodedPayload;
        }

        $requestBody = (string) $this->request->getBody();
        if ($requestBody === '') {
            return [];
        }

        $decodedPayload = json_decode($requestBody, true);

        return is_array($decodedPayload) ? $decodedPayload : [];
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    private function actorIdentifier(array $authenticatedSubject): int
    {
        return (int) ($authenticatedSubject['user_id'] ?? $authenticatedSubject['sub'] ?? 0);
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     * @return array<int, string>
     */
    private function subjectPermissionKeys(array $authenticatedSubject): array
    {
        $permissionKeys = [];
        foreach (['permissions', 'scopes', 'scope'] as $claimName) {
            $claimValue = $authenticatedSubject[$claimName] ?? [];
            if (is_string($claimValue)) {
                $claimValue = preg_split('/[\s,]+/', trim($claimValue)) ?: [];
            }

            if (is_array($claimValue)) {
                foreach ($claimValue as $permissionValue) {
                    if (is_string($permissionValue) && trim($permissionValue) !== '') {
                        $permissionKeys[] = mb_strtolower(trim($permissionValue), 'UTF-8');
                    }
                }
            }
        }

        return array_values(array_unique($permissionKeys));
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    private function subjectIsSuperAdministrator(array $authenticatedSubject): bool
    {
        return in_array('*', $this->subjectPermissionKeys($authenticatedSubject), true);
    }

    /**
     * @return array<string, mixed>|ResponseInterface
     */
    private function groupByValidatedUuid(string $groupUuid): array|ResponseInterface
    {
        if (! FMSPrivilegesValidation::isValidUuid($groupUuid)) {
            return $this->respondValidationError(['group_uuid' => ['UUID group tidak valid.']]);
        }

        $groupRow = $this->managementRepository->findGroupByUuid($groupUuid);
        if ($groupRow === null) {
            return $this->respondError(404, 'Group tidak ditemukan.', null, 404);
        }

        return $groupRow;
    }

    /**
     * @param array<string, mixed> $requestPayload
     * @return array<int, array{permission_id: int, effect: string}>
     */
    private function normalizedPermissionMappings(array $requestPayload): array
    {
        $permissionMappings = $requestPayload['permissions'] ?? $requestPayload['mappings'] ?? [];
        $normalizedMappings = [];
        foreach (is_array($permissionMappings) ? $permissionMappings : [] as $permissionMapping) {
            if (! is_array($permissionMapping)) {
                continue;
            }

            $normalizedMappings[] = [
                'permission_id' => (int) ($permissionMapping['permission_id'] ?? $permissionMapping['permissionId'] ?? 0),
                'effect'        => mb_strtolower(trim((string) ($permissionMapping['effect'] ?? 'allow')), 'UTF-8'),
            ];
        }

        return $normalizedMappings;
    }

    /**
     * @param array<string, mixed> $requestPayload
     * @return array<int, int>
     */
    private function normalizedMenuPermissionIdentifiers(array $requestPayload): array
    {
        $permissionEntries = $requestPayload['permissions'] ?? $requestPayload['mappings'] ?? $requestPayload['permission_ids'] ?? [];
        if (! is_array($permissionEntries)) {
            return [];
        }

        $permissionIdentifiers = [];
        foreach ($permissionEntries as $permissionEntry) {
            $permissionIdentifiers[] = is_array($permissionEntry)
                ? (int) ($permissionEntry['permission_id'] ?? 0)
                : (int) $permissionEntry;
        }

        return $this->managementService->normalizeIdentifierList($permissionIdentifiers, 'Permission');
    }

    /**
     * @param array<string, mixed> $requestPayload
     * @return array<int, array{group_id: int, expires_at: string|null}>
     */
    private function normalizedGroupAssignments(array $requestPayload): array
    {
        $groupEntries = $requestPayload['groups'] ?? $requestPayload['group_ids'] ?? [];
        if (! is_array($groupEntries)) {
            return [];
        }

        $normalizedAssignments = [];
        foreach ($groupEntries as $groupEntry) {
            if (is_array($groupEntry)) {
                $normalizedAssignments[] = [
                    'group_id'   => (int) ($groupEntry['group_id'] ?? 0),
                    'expires_at' => $this->managementService->normalizeExpirationTimestamp($groupEntry['expires_at'] ?? null),
                ];
                continue;
            }

            $normalizedAssignments[] = [
                'group_id'   => (int) $groupEntry,
                'expires_at' => null,
            ];
        }

        $this->managementService->normalizeIdentifierList(array_column($normalizedAssignments, 'group_id'), 'Group');

        return $normalizedAssignments;
    }

    private function positiveIdentifier(string $identifierValue): ?int
    {
        if (! ctype_digit($identifierValue) || (int) $identifierValue <= 0) {
            return null;
        }

        return (int) $identifierValue;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditContext(): array
    {
        return [
            'request_id'  => FMSRequestContext::requestIdentifier($this->request) ?? $this->request->getHeaderLine('X-Request-ID'),
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => $this->request->getUserAgent()->getAgentString(),
            'http_method' => $this->request->getMethod(),
            'route_name'  => $this->request->getUri()->getPath(),
            'status_code' => 200,
        ];
    }

    private function domainValidationResponse(InvalidArgumentException $invalidArgumentException): ResponseInterface
    {
        return $this->respondValidationError(['payload' => [$invalidArgumentException->getMessage()]]);
    }

    private function lastSuperAdminResponse(FMSLastSuperAdminProtectionException $protectionException): ResponseInterface
    {
        return $this->respondError(400,
            $protectionException->getMessage(),
            null,
            409,
        );
    }

    private function unexpectedErrorResponse(
        string $operationName,
        string $responseCode,
        string $responseMessage,
        Throwable $unexpectedException,
    ): ResponseInterface {
        log_message('error', '{operation} failed: {message}', [
            'operation' => $operationName,
            'message'   => $unexpectedException->getMessage(),
        ]);

        return $this->respondError($responseCode, $responseMessage, null, 500);
    }
}
