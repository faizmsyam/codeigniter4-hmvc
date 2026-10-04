<?php

namespace App\Modules\AdminMenus\Controllers\Api;

use App\Core\FMSApiController;
use App\Modules\AdminMenus\Models\FMSAdminMenuModel;
use App\Modules\AdminMenus\Services\FMSAdminMenuAccessService;
use App\Modules\AdminMenus\Services\FMSAdminMenuManagementService;
use App\Modules\AdminMenus\Services\FMSAdminMenuTreeService;
use App\Modules\AdminMenus\Validation\FMSAdminMenuValidation;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesManagementRepository;
use App\Modules\Privileges\Services\FMSPrivilegesManagementService;
use App\Modules\Privileges\Validation\FMSPrivilegesValidation;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use Throwable;

/** Authenticated CRUD and permission-filtered navigation boundary. */
final class FMSAdminMenusApiController extends FMSApiController
{
    private FMSAdminMenuManagementService $menuManagementService;
    private FMSAdminMenuValidation $menuValidation;
    private FMSAdminMenuModel $adminMenuModel;
    private FMSAdminMenuAccessService $menuAccessService;
    private FMSAdminMenuTreeService $menuTreeService;
    private FMSDatabasePrivilegesManagementRepository $privilegesRepository;
    private FMSPrivilegesManagementService $privilegesManagementService;

    public function __construct(
        ?FMSAdminMenuManagementService $menuManagementService = null,
        ?FMSAdminMenuValidation $menuValidation = null,
        ?FMSAdminMenuModel $adminMenuModel = null,
        ?FMSAdminMenuAccessService $menuAccessService = null,
        ?FMSAdminMenuTreeService $menuTreeService = null,
        ?FMSDatabasePrivilegesManagementRepository $privilegesRepository = null,
        ?FMSPrivilegesManagementService $privilegesManagementService = null,
    ) {
        parent::__construct();
        $this->adminMenuModel = $adminMenuModel ?? new FMSAdminMenuModel();
        $this->menuManagementService = $menuManagementService ?? new FMSAdminMenuManagementService($this->adminMenuModel);
        $this->menuValidation = $menuValidation ?? new FMSAdminMenuValidation();
        $this->menuAccessService = $menuAccessService ?? new FMSAdminMenuAccessService();
        $this->menuTreeService = $menuTreeService ?? new FMSAdminMenuTreeService();
        $this->privilegesRepository = $privilegesRepository ?? new FMSDatabasePrivilegesManagementRepository();
        $this->privilegesManagementService = $privilegesManagementService ?? new FMSPrivilegesManagementService();
    }

    public function index(): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.view')) !== null) {
            return $failure;
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 20)));
        $allItems = $this->request->getGet('all') === '1';
        $rows = $allItems
            ? $this->menuManagementService->listMenus()
            : $this->adminMenuModel
                ->orderBy('id_parent', 'ASC')
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll($perPage, ($page - 1) * $perPage);
        $totalItems = $allItems ? count($rows) : $this->adminMenuModel->countAllResults();

        return $this->respondSuccess(200, 'Daftar menu berhasil dimuat.', [
            'items' => array_map($this->presentMenu(...), $rows),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalItems,
                'total_pages' => $totalItems === 0 ? 0 : (int) ceil($totalItems / $perPage),
            ],
        ]);
    }

    public function show(string $menuIdentifier): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.view')) !== null) {
            return $failure;
        }

        $menuRow = $this->findMenu($menuIdentifier);
        if ($menuRow === null) {
            return $this->respondError(404, 'Menu tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Menu berhasil dimuat.', $this->presentMenu($menuRow));
    }

    public function create(): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.create')) !== null) {
            return $failure;
        }

        $payload = $this->jsonPayload();
        if (($failure = $this->validatePayload($payload, $this->menuValidation->getCreateMenuRules())) !== null) {
            return $failure;
        }

        try {
            $menuIdentifier = $this->menuManagementService->createMenu($payload, $this->authenticatedApiUserIdentifier());
            $createdMenu = $this->menuManagementService->findMenu($menuIdentifier) ?? ['id' => $menuIdentifier];
        } catch (InvalidArgumentException $exception) {
            return $this->respondValidationError(['menu' => [$exception->getMessage()]]);
        } catch (Throwable $exception) {
            log_message('error', 'Menu create failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondError(500, 'Menu gagal disimpan.', null, 500);
        }

        return $this->respondSuccess(201, 'Menu berhasil disimpan.', $this->presentMenu($createdMenu), 201);
    }

    public function update(string $menuIdentifier): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.update')) !== null) {
            return $failure;
        }

        $normalizedIdentifier = $this->positiveIdentifier($menuIdentifier);
        if ($normalizedIdentifier === null || $this->adminMenuModel->find($normalizedIdentifier) === null) {
            return $this->respondError(404, 'Menu tidak ditemukan.', null, 404);
        }

        $payload = $this->jsonPayload();
        if ($payload === []) {
            return $this->respondValidationError(['menu' => ['Minimal satu field menu wajib dikirim.']]);
        }
        if (($failure = $this->validatePayload($payload, $this->menuValidation->getUpdateMenuRules())) !== null) {
            return $failure;
        }

        try {
            $this->menuManagementService->updateMenu($normalizedIdentifier, $payload, $this->authenticatedApiUserIdentifier());
            $updatedMenu = $this->menuManagementService->findMenu($normalizedIdentifier) ?? ['id' => $normalizedIdentifier];
        } catch (InvalidArgumentException $exception) {
            return $this->respondValidationError(['menu' => [$exception->getMessage()]]);
        } catch (Throwable $exception) {
            log_message('error', 'Menu update failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondError(500, 'Menu gagal diperbarui.', null, 500);
        }

        return $this->respondSuccess(200, 'Menu berhasil diperbarui.', $this->presentMenu($updatedMenu));
    }

    public function delete(string $menuIdentifier): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.delete')) !== null) {
            return $failure;
        }

        $normalizedIdentifier = $this->positiveIdentifier($menuIdentifier);
        if ($normalizedIdentifier === null || $this->adminMenuModel->find($normalizedIdentifier) === null) {
            return $this->respondError(404, 'Menu tidak ditemukan.', null, 404);
        }

        try {
            $this->menuManagementService->deleteMenu($normalizedIdentifier, $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondError(409, $exception->getMessage(), null, 409);
        } catch (Throwable $exception) {
            log_message('error', 'Menu delete failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondError(500, 'Menu gagal dihapus.', null, 500);
        }

        return $this->respondSuccess(200, 'Menu berhasil dihapus.');
    }

    public function reorder(): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.reorder')) !== null) {
            return $failure;
        }

        $payload = $this->jsonPayload();
        $ordering = $payload['ordering'] ?? $payload['order'] ?? null;
        if (! is_array($ordering)) {
            return $this->respondValidationError(['ordering' => ['Daftar urutan menu tidak valid.']]);
        }

        $normalizedOrderingPayload = [];
        foreach ($ordering as $orderingEntry) {
            if (! is_array($orderingEntry)) {
                $normalizedOrderingPayload[] = $orderingEntry;
                continue;
            }
            $menuIdentifier = $orderingEntry['menu_id'] ?? $orderingEntry['id'] ?? null;
            $normalizedOrderingPayload[] = [
                'menu_id' => $menuIdentifier,
                'position' => $orderingEntry['position'] ?? null,
            ];
        }

        try {
            $normalizedOrdering = $this->menuManagementService->reorder($normalizedOrderingPayload, $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondValidationError(['ordering' => [$exception->getMessage()]]);
        } catch (Throwable $exception) {
            log_message('error', 'Menu reorder failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondError(500, 'Urutan menu gagal disimpan.', null, 500);
        }

        return $this->respondSuccess(200, 'Urutan menu berhasil disimpan.', ['ordering' => $normalizedOrdering]);
    }

    public function menuActions(string $menuIdentifier): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.update')) !== null) {
            return $failure;
        }

        $normalizedMenuIdentifier = $this->positiveIdentifier($menuIdentifier);
        if ($normalizedMenuIdentifier === null || $this->adminMenuModel->find($normalizedMenuIdentifier) === null) {
            return $this->respondNotFound('Menu tidak ditemukan.');
        }

        return $this->respondOk('Daftar hak tombol berhasil dimuat.', [
            'items' => array_map($this->presentMenuAction(...), $this->privilegesRepository->menuActionPermissions($normalizedMenuIdentifier)),
        ]);
    }

    public function createMenuAction(string $menuIdentifier): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.update')) !== null) {
            return $failure;
        }

        $normalizedMenuIdentifier = $this->positiveIdentifier($menuIdentifier);
        $menuRow = $normalizedMenuIdentifier === null ? null : $this->adminMenuModel->find($normalizedMenuIdentifier);
        if (! is_array($menuRow)) {
            return $this->respondNotFound('Menu tidak ditemukan.');
        }

        $payload = $this->jsonPayload();
        $moduleName = $this->menuModuleName((string) ($menuRow['url'] ?? ''), (string) ($menuRow['name'] ?? ''));
        $actionName = $this->permissionActionName((string) ($payload['action_name'] ?? $payload['name'] ?? ''));
        if ($actionName === '') {
            return $this->respondValidationError(['action_name' => ['Nama tindakan wajib diisi menggunakan huruf atau angka.']]);
        }

        $permissionPayload = [
            'permission_key' => $moduleName . '.' . $actionName,
            'module_name' => $moduleName,
            'action_name' => $actionName,
            'description' => trim((string) ($payload['description'] ?? '')),
            'is_active' => 1,
            'is_system' => 0,
            'source_menu_id' => $normalizedMenuIdentifier,
        ];
        $validationErrors = FMSPrivilegesValidation::validatePermissionCreate($permissionPayload);
        if ($validationErrors !== []) {
            return $this->respondValidationError($validationErrors);
        }

        try {
            $actorIdentifier = $this->authenticatedApiUserIdentifier();
            $permissionData = $this->privilegesManagementService->preparePermissionData($permissionPayload, $actorIdentifier, true);
            $createdPermission = $this->privilegesRepository->createPermission($permissionData, $actorIdentifier);
            $permissionIdentifiers = array_map(
                static fn (array $mapping): int => (int) ($mapping['permission_id'] ?? 0),
                $this->privilegesRepository->menuPermissionMappings($normalizedMenuIdentifier),
            );
            $permissionIdentifiers[] = (int) ($createdPermission['id'] ?? 0);
            $this->privilegesRepository->replaceMenuPermissionMappings(
                $normalizedMenuIdentifier,
                array_values(array_unique(array_filter($permissionIdentifiers))),
                $actorIdentifier,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->respondValidationError(['action_name' => [$exception->getMessage()]]);
        } catch (Throwable $exception) {
            log_message('error', 'Menu action create failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Hak tombol gagal ditambahkan.');
        }

        return $this->respondCreated('Hak tombol berhasil ditambahkan.', [
            'permission' => $this->presentMenuAction($createdPermission),
        ]);
    }

    public function deleteMenuAction(string $menuIdentifier, string $permissionIdentifier): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('menus.update')) !== null) {
            return $failure;
        }

        $normalizedMenuIdentifier = $this->positiveIdentifier($menuIdentifier);
        $normalizedPermissionIdentifier = $this->positiveIdentifier($permissionIdentifier);
        $permissionRow = $normalizedPermissionIdentifier === null
            ? null
            : $this->privilegesRepository->findPermissionByIdentifier($normalizedPermissionIdentifier, true);
        if ($normalizedMenuIdentifier === null || ! is_array($permissionRow)
            || (int) ($permissionRow['source_menu_id'] ?? 0) !== $normalizedMenuIdentifier
            || (int) ($permissionRow['is_system'] ?? 0) === 1) {
            return $this->respondNotFound('Hak tombol tidak ditemukan.');
        }

        try {
            $this->privilegesRepository->deletePermission(
                (string) $permissionRow['uuid'],
                $this->authenticatedApiUserIdentifier(),
            );
        } catch (Throwable $exception) {
            log_message('error', 'Menu action delete failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Hak tombol gagal dihapus.');
        }

        return $this->respondOk('Hak tombol berhasil dihapus.');
    }

    public function sidebar(): ResponseInterface
    {
        $userIdentifier = $this->authenticatedApiUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized();
        }

        try {
            $sidebarTree = $this->menuAccessService->sidebarForUserIdentifier($userIdentifier);
        } catch (Throwable $exception) {
            log_message('error', 'Sidebar composition failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondError(500, 'Navigasi gagal dimuat.', null, 500);
        }

        return $this->respondSuccess(200, 'Navigasi berhasil dimuat.', ['menus' => $sidebarTree]);
    }

    private function findMenu(string $identifier): ?array
    {
        $normalizedIdentifier = $this->positiveIdentifier($identifier);
        if ($normalizedIdentifier === null) {
            return null;
        }
        $menuRow = $this->adminMenuModel->find($normalizedIdentifier);
        return is_array($menuRow) ? $menuRow : null;
    }

    private function positiveIdentifier(string $identifier): ?int
    {
        if (preg_match('/^[1-9][0-9]*$/', $identifier) !== 1) {
            return null;
        }
        return (int) $identifier;
    }

    private function jsonPayload(): array
    {
        $decodedPayload = $this->request->getJSON(true);
        if (is_array($decodedPayload)) {
            return $decodedPayload;
        }
        $decodedPayload = json_decode((string) $this->request->getBody(), true);
        return is_array($decodedPayload) ? $decodedPayload : [];
    }

    private function validatePayload(array $payload, array $rules): ?ResponseInterface
    {
        $validation = \Config\Services::validation();
        $validation->reset();
        $validation->setRules($rules);
        return $validation->run($payload) ? null : $this->respondValidationError($validation->getErrors());
    }

    private function presentMenuAction(array $permissionRow): array
    {
        return [
            'id' => (int) ($permissionRow['id'] ?? 0),
            'action_name' => (string) ($permissionRow['action_name'] ?? ''),
            'description' => $permissionRow['description'] ?? null,
            'code' => (string) ($permissionRow['permission_key'] ?? ''),
            'is_active' => (bool) ($permissionRow['is_active'] ?? false),
        ];
    }

    private function menuModuleName(string $url, string $name): string
    {
        $candidate = trim(explode('/', trim($url, '/'))[0] ?? '');
        if ($candidate === '' || $candidate === '#') {
            $candidate = $name;
        }

        $candidate = mb_strtolower($candidate, 'UTF-8');
        $candidate = preg_replace('/[^a-z0-9]+/', '_', $candidate) ?? '';

        return trim($candidate, '_');
    }

    private function permissionActionName(string $label): string
    {
        $label = mb_strtolower(trim($label), 'UTF-8');
        $label = preg_replace('/[^a-z0-9]+/', '_', $label) ?? '';

        return trim($label, '_');
    }

    private function presentMenu(array $menuRow): array
    {
        return [
            'id' => (int) ($menuRow['id'] ?? 0),
            'id_parent' => isset($menuRow['id_parent']) ? (int) $menuRow['id_parent'] : null,
            'name' => (string) ($menuRow['name'] ?? ''),
            'url' => $menuRow['url'] ?? null,
            'position' => isset($menuRow['position']) ? (int) $menuRow['position'] : null,
            'icon' => $menuRow['icon'] ?? null,
            'is_active' => (bool) ($menuRow['is_active'] ?? false),
            'target_blank' => (bool) ($menuRow['target_blank'] ?? false),
        ];
    }
}
