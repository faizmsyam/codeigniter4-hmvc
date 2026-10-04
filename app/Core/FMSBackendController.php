<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Core;

use App\Models\FMSMenuModel;
use App\Modules\AdminMenus\Services\FMSAdminMenuAccessService;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository;
use App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class FMSBackendController extends FMSController
{
    protected mixed $menus;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $userIdentifier = (int) $this->session->get('fms_backend_user_id');
        $menuRows = [];
        if ($userIdentifier > 0) {
            try {
                $sessionPermissions = $this->refreshActiveGroupPermissions($userIdentifier);
                $menuRows = (new FMSAdminMenuAccessService())->sidebarForPermissionCodes($sessionPermissions);
            } catch (Throwable $unexpectedException) {
                log_message('error', 'Backend menu composition failed: {message}', [
                    'message' => $unexpectedException->getMessage(),
                ]);
            }
        }

        $this->menus = $this->normalizeMenuRowsForBackend($menuRows);
        $breadcrumbs = $this->buildBreadcrumb();
        $lastBreadcrumb = $breadcrumbs === [] ? null : end($breadcrumbs);
        $pageTitle = is_object($lastBreadcrumb) ? ($lastBreadcrumb->name ?? 'App') : $this->fallbackPageTitle();
        if (($this->meta['title'] ?? '') === '') {
            $this->fmsMeta(['title' => $pageTitle]);
        }
        $backendUser = [
            'id' => $userIdentifier,
            'uuid' => (string) $this->session->get('fms_backend_user_uuid'),
            'username' => (string) $this->session->get('fms_backend_username'),
            'email' => (string) $this->session->get('fms_backend_email'),
            'full_name' => (string) $this->session->get('fms_backend_full_name'),
            'avatar' => (string) $this->session->get('fms_backend_avatar'),
            'avatar_url' => $this->resolveSessionAvatarUrl(),
            'permissions' => $sessionPermissions,
        ];

        $renderer = \Config\Services::renderer();
        $renderer->setVar('menus', $this->menus, 'raw');
        $renderer->setVar('backendUser', $backendUser, 'raw');
        $renderer->setVar('backendPermissions', $backendUser['permissions'], 'raw');
        $renderer->setVar('currentUri', service('uri')->getPath(), 'raw');
        $renderer->setVar('pageTitle', $pageTitle, 'raw');
        $renderer->setVar('breadcrumbs', $breadcrumbs, 'raw');
    }

    protected function hasBackendPermission(string $requiredPermission): bool
    {
        $permissions = $this->refreshActiveGroupPermissions((int) $this->session->get('fms_backend_user_id'));

        return in_array('*', $permissions, true) || in_array($requiredPermission, $permissions, true);
    }

    /**
     * Hitung ulang permission efektif dari grup aktif (DB), bukan array session
     * beku. Dengan begitu perubahan matrix Privileges langsung berlaku pada
     * request backend berikutnya tanpa menunggu login ulang.
     *
     * @return list<string>
     */
    protected function refreshActiveGroupPermissions(int $userIdentifier): array
    {
        if ($userIdentifier <= 0) {
            return [];
        }

        $activeGroupIdentifier = (int) $this->session->get('fms_backend_active_group_id');

        try {
            $effectivePermissionService = new FMSPrivilegesEffectivePermissionService(
                new FMSDatabasePrivilegesPermissionRepository(),
            );

            if ($activeGroupIdentifier <= 0) {
                /* Sesi lama bisa belum punya grup aktif: ambil grup pertama user. */
                $activeGroupIdentifier = $this->firstMembershipGroupIdentifier($userIdentifier);
                if ($activeGroupIdentifier > 0) {
                    $this->session->set('fms_backend_active_group_id', $activeGroupIdentifier);
                }
            }

            $permissionCodes = $activeGroupIdentifier > 0
                ? $effectivePermissionService->resolveEffectivePermissionCodesForGroup($activeGroupIdentifier)
                : $effectivePermissionService->resolveEffectivePermissionCodesForUser($userIdentifier);
        } catch (Throwable $unexpectedException) {
            log_message('error', 'Backend permission refresh failed: {message}', [
                'message' => $unexpectedException->getMessage(),
            ]);

            /* Fallback ke cache session supaya permintaan tetap terlayani. */
            return array_map('strval', (array) $this->session->get('fms_backend_permissions'));
        }

        $permissionCodes = array_values(array_unique(array_map('strval', $permissionCodes)));
        $this->session->set('fms_backend_permissions', $permissionCodes);

        return $permissionCodes;
    }

    /**
     * Grup membership pertama milik user, dipakai sebagai fallback ketika
     * session belum menyimpan grup aktif (mis. sesi login versi lama).
     */
    private function firstMembershipGroupIdentifier(int $userIdentifier): int
    {
        if ($userIdentifier <= 0) {
            return 0;
        }

        try {
            $membershipRow = db_connect()
                ->table('t_user_groups')
                ->select('group_id')
                ->where('user_id', $userIdentifier)
                ->orderBy('id', 'ASC')
                ->get(1)
                ->getRowArray();
        } catch (Throwable $lookupException) {
            log_message('error', 'Backend group lookup failed: {message}', [
                'message' => $lookupException->getMessage(),
            ]);

            return 0;
        }

        return is_array($membershipRow) ? (int) ($membershipRow['group_id'] ?? 0) : 0;
    }

    protected function requireBackendPermission(string $requiredPermission): void
    {
        if (! $this->hasBackendPermission($requiredPermission)) {
            $userId = session()->get('fms_backend_user_id');
            try {
                \App\Libraries\FMSAuditLogger::record(
                    event: 'access.denied',
                    module: 'backend',
                    actorId: is_numeric($userId) ? (int) $userId : null,
                    entityType: 'permission',
                    entityId: $requiredPermission,
                    description: 'Akses halaman backend ditolak (403): Membutuhkan hak akses ' . $requiredPermission,
                    httpMethod: $this->request->getMethod(),
                    statusCode: 403,
                );
            } catch (Throwable $auditException) {
                log_message('error', 'Audit log failed on access.denied: {msg}', ['msg' => $auditException->getMessage()]);
            }

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    protected function buildBreadcrumb(): array
    {
        $uri = trim(service('uri')->getPath(), '/');
        $uri = preg_replace('#^' . ROUTE_ADMIN . '/#', '', $uri) ?? '';

        $menuModel = new FMSMenuModel();
        $menu = $menuModel->getMenuByUrl($uri);
        if (! $menu) {
            return [];
        }

        return $menuModel->getMenuParents((int) $menu->id);
    }

    /**
     * Human-readable title when the current URL has no menu row in c_menus.
     * Keeps <title> and the breadcrumb <h1> meaningful on profile pages or
     * action URLs, while every registered menu title comes from the DB.
     */
    protected function fallbackPageTitle(): string
    {
        $segments = array_values(array_filter(
            explode('/', trim(service('uri')->getPath(), '/')),
            static fn (string $segment): bool => $segment !== '',
        ));

        if (($segments[0] ?? '') === ROUTE_ADMIN) {
            array_shift($segments);
        }

        $actionSegment = end($segments);
        if ($actionSegment === false || ctype_digit((string) $actionSegment)) {
            $actionSegment = prev($segments) ?: '';
        }

        $prettyAction = ucwords(str_replace(['-', '_'], ' ', (string) $actionSegment));

        return $prettyAction === '' ? 'Dashboard' : $prettyAction;
    }

    /**
     * @param array<int, array<string, mixed>> $menuRows
     * @return array<int, object>
     */
    private function normalizeMenuRowsForBackend(array $menuRows): array
    {
        $normalizedRows = [];
        foreach ($menuRows as $menuRow) {
            $menuObject = (object) $menuRow;
            $menuUrl = trim((string) ($menuObject->url ?? ''));
            if ($menuUrl !== '' && $menuUrl !== '#' && ! str_starts_with($menuUrl, 'http')) {
                $menuObject->url = trim(ROUTE_ADMIN . '/' . trim($menuUrl, '/'), '/');
            }

            if (isset($menuRow['children']) && is_array($menuRow['children'])) {
                $menuObject->children = $this->normalizeMenuRowsForBackend($menuRow['children']);
            }

            $normalizedRows[] = $menuObject;
        }

        return $normalizedRows;
    }

    /**
     * Generate fresh presigned URL for header avatar display (TTL 600s).
     */
    private function resolveSessionAvatarUrl(): string
    {
        $rawAvatar = trim((string) $this->session->get('fms_backend_avatar'));
        if ($rawAvatar === '') {
            return '';
        }

        /* If already a full URL (legacy or external), use as-is */
        if (str_starts_with($rawAvatar, 'http://') || str_starts_with($rawAvatar, 'https://')) {
            return $rawAvatar;
        }

        try {
            $config  = config(\App\Config\FMSUploads::class);
            $storage = \App\Modules\Uploads\Services\FMSObjectStorageFactory::create($config);
            $service = new \App\Modules\Uploads\Services\FMSPrivateUploadService(
                $storage,
                $config->presignedUrlTtlSeconds,
                $config->presignedUrlMaximumTtlSeconds,
            );

            return $service->signedReadUrl($rawAvatar, $config->presignedUrlTtlSeconds);
        } catch (\Throwable $e) {
            log_message('error', 'resolveSessionAvatarUrl error: {msg}', ['msg' => $e->getMessage()]);

            return '';
        }
    }
}
