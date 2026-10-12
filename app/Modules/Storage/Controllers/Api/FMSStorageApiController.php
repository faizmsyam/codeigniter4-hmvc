<?php
namespace App\Modules\Storage\Controllers\Api;

use App\Core\FMSController;
use App\Modules\Storage\Config\FMSStorage;
use App\Modules\Storage\Models\FMSStorageUserModel;
use App\Modules\Storage\Services\FMSStorageAuthorizationService;
use App\Modules\Storage\Services\FMSStorageAuditService;
use App\Modules\Storage\Services\FMSStorageObjectService;
use App\Modules\Storage\Services\FMSStorageSessionService;
use App\Modules\Storage\Services\FMSStorageUserAdminService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSStorageApiController extends FMSController
{
    private function user(): ?array
    {
        return (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
    }

    private function allowed(string $permission): bool
    {
        return (new FMSStorageAuthorizationService())->allows($this->user(), $permission);
    }

    private function deny(): ResponseInterface
    {
        return $this->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
    }

    public function login(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $user = (new FMSStorageUserModel())->findByIdentifier(trim((string) ($data['identifier'] ?? '')));
        if (! is_array($user) || (string) ($user['status'] ?? '') !== 'active' || password_get_info((string) ($user['password_hash'] ?? ''))['algo'] === 0 || ! password_verify((string) ($data['password'] ?? ''), (string) ($user['password_hash'] ?? ''))) {
            return $this->json(['status' => false, 'message' => 'Username atau password salah.'], 401);
        }

        $model = new FMSStorageUserModel();
        $model->update((int) $user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip_hash' => hash('sha256', (string) $this->request->getIPAddress()),
        ]);
        $token = (new FMSStorageSessionService())->create(
            (int) $user['id'],
            (string) $this->request->getIPAddress(),
            (string) $this->request->getUserAgent()->getAgentString(),
        );

        $response = $this->json(['status' => true, 'data' => [
            'username' => $user['username'],
            'is_super_admin' => (int) $user['is_super_admin'] === 1,
        ]]);
        return (new FMSStorageSessionService())->attachCookie($response, $token);
    }

    public function logout(): ResponseInterface
    {
        $session = new FMSStorageSessionService();
        $session->revoke((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        return $session->clearCookie($this->json(['status' => true]));
    }

    public function objects(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::READ)) return $this->deny();
        try {
            $result = (new FMSStorageObjectService())->list(
                (string) $this->request->getGet('prefix'),
                (string) $this->request->getGet('search'),
                (string) $this->request->getGet('sort'),
                (string) $this->request->getGet('direction'),
                (int) $this->request->getGet('page'),
                (int) $this->request->getGet('per_page'),
            );
            return $this->json(['status' => true, 'data' => $result]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function upload(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::UPLOAD)) return $this->deny();
        $file = $this->request->getFile('file');
        if (! $file) return $this->json(['status' => false, 'message' => 'File wajib diisi.'], 422);
        try {
            $result = (new FMSStorageObjectService())->upload($file, (string) $this->request->getPost('prefix'));
            (new FMSStorageAuditService())->log($this->user(), 'object.upload', (string) ($result['key'] ?? ''), ['size' => $result['size'] ?? 0, 'mime' => $result['mime'] ?? ''], (string) $this->request->getIPAddress());
            return $this->json(['status' => true, 'data' => $result], 201);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function delete(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::DELETE)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            $deleted = (new FMSStorageObjectService())->delete((string) ($data['key'] ?? ''));
            if ($deleted) {
                (new FMSStorageAuditService())->log($this->user(), 'object.delete', (string) ($data['key'] ?? ''), [], (string) $this->request->getIPAddress());
            }
            return $this->json(['status' => $deleted]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function presign(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::SHARE)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            $url = (new FMSStorageObjectService())->presigned((string) ($data['key'] ?? ''), (int) ($data['ttl'] ?? 3600));
            return $this->json(['status' => true, 'data' => ['url' => $url]]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createShare(): ResponseInterface
    {
        $user = $this->user();
        if (! $this->allowed(FMSStorageAuthorizationService::SHARE)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $key = trim((string) ($data['key'] ?? ''));
        $maximumTtl = (int) config(FMSStorage::class)->shareMaximumTtl;
        $ttl = max(60, min($maximumTtl, (int) ($data['ttl'] ?? 3600)));
        try {
            (new FMSStorageObjectService())->download($key);
            $token = bin2hex(random_bytes(32));
            db_connect()->table('storage_shares')->insert([
                'token_hash' => hash('sha256', $token),
                'object_key' => $key,
                'created_by' => (int) ($user['id'] ?? 0),
                'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->json(['status' => true, 'data' => [
                'url' => site_url('fms-storage/share/' . $token),
                'expires_at' => date(DATE_ATOM, time() + $ttl),
            ]], 201);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function settings(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $service = new \App\Modules\Storage\Services\FMSStorageSettingsService();
        return $this->json(['status' => true, 'data' => [
            'available_mime_types' => array_values($service->availableMimeTypes()),
            'allowed_mime_types' => config(FMSStorage::class)->allowedMimeTypes(),
            'maximum_upload_bytes' => config(\App\Config\FMSUploads::class)->maximumInputBytes(),
        ]]);
    }

    public function saveSettings(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $mimeTypes = $data['allowed_mime_types'] ?? [];
        if (! is_array($mimeTypes)) $mimeTypes = [];
        try {
            $saved = (new \App\Modules\Storage\Services\FMSStorageSettingsService())->saveAllowedMimeTypes($mimeTypes);
            (new FMSStorageAuditService())->log($this->user(), 'settings.mime', null, ['allowed' => $saved], (string) $this->request->getIPAddress());
            return $this->json(['status' => true, 'data' => ['allowed_mime_types' => $saved]]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function profile(): ResponseInterface
    {
        $user = $this->user();
        if (! $user) return $this->json(['status' => false], 401);
        unset($user['password_hash']);
        $storageConfig = config(FMSStorage::class);
        $uploadsConfig = config(\App\Config\FMSUploads::class);
        return $this->json(['status' => true, 'data' => [
            'user' => $user,
            'allowed_mime_types' => $storageConfig->allowedMimeTypes(),
            'maximum_upload_bytes' => $uploadsConfig->maximumInputBytes(),
        ]]);
    }

    public function metadata(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::READ)) return $this->deny();
        $key = trim((string) $this->request->getGet('key'));
        if ($key === '') return $this->json(['status' => false, 'message' => 'Key wajib diisi.'], 422);
        try {
            $metadata = (new FMSStorageObjectService())->metadata($key);
            $metadata['download_url'] = site_url('api/v1/storage/download?key=' . rawurlencode($metadata['key']));
            return $this->json(['status' => true, 'data' => $metadata]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function download(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::READ)) return $this->deny();
        $key = trim((string) $this->request->getGet('key'));
        if ($key === '') return $this->response->setStatusCode(400);
        try {
            $data = (new FMSStorageObjectService())->download($key);
            return $this->response
                ->setStatusCode(200)
                ->setContentType($data['content_type'])
                ->setHeader('Content-Disposition', 'inline; filename="' . basename($key) . '"')
                ->setHeader('Content-Length', (string) $data['byte_size'])
                ->setHeader('Cache-Control', 'private, max-age=3600')
                ->setBody($data['body']);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(404);
        }
    }

    public function changePassword(): ResponseInterface
    {
        $user = $this->user();
        if (! $user) return $this->json(['status' => false, 'message' => 'Sesi tidak valid.'], 401);
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        if (! password_verify((string) ($data['current_password'] ?? ''), (string) ($user['password_hash'] ?? ''))) {
            return $this->json(['status' => false, 'message' => 'Password saat ini salah.'], 422);
        }

        $password = (string) ($data['new_password'] ?? '');
        if (strlen($password) < 12) {
            return $this->json(['status' => false, 'message' => 'Password minimal 12 karakter.'], 422);
        }

        (new FMSStorageUserModel())->update((int) $user['id'], [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json(['status' => true, 'message' => 'Password berhasil diubah.']);
    }

    public function preview(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::READ)) return $this->deny();
        $key = trim((string) $this->request->getGet('key'));
        if ($key === '') return $this->response->setStatusCode(400);
        try {
            $data = (new FMSStorageObjectService())->preview($key);
            if (! $data['previewable']) {
                return $this->json(['status' => false, 'message' => 'Tipe object tidak mendukung pratinjau.'], 415);
            }
            $response = $this->response
                ->setStatusCode(200)
                ->setContentType($data['mime'])
                ->setHeader('Content-Disposition', 'inline; filename="' . basename((string) $data['key']) . '"')
                ->setHeader('Cache-Control', 'private, max-age=60')
                ->setHeader('X-Content-Type-Options', 'nosniff');

            // SVG inline bisa mengeksekusi <script>. Netralkan dengan CSP ketat
            // tanpa mematikan render gambar (style inline tetap diizinkan).
            if (strtolower((string) $data['mime']) === 'image/svg+xml') {
                $response->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src data:");
            }

            return $response->setBody($data['body']);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(404);
        }
    }

    public function bulkDelete(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::DELETE)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $keys = is_array($data['keys'] ?? null) ? $data['keys'] : [];
        if ($keys === []) return $this->json(['status' => false, 'message' => 'Tidak ada object yang dipilih.'], 422);
        try {
            $deleted = (new FMSStorageObjectService())->bulkDelete($keys);
            (new FMSStorageAuditService())->log($this->user(), 'object.bulk_delete', null, ['count' => $deleted], (string) $this->request->getIPAddress());
            return $this->json(['status' => true, 'data' => ['deleted' => $deleted]]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rename(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::EDIT)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            $service = new FMSStorageObjectService();
            if ((string) ($data['type'] ?? '') === 'folder') {
                $result = $service->renameFolder((string) ($data['key'] ?? ''), (string) ($data['name'] ?? ''));
                (new FMSStorageAuditService())->log($this->user(), 'folder.rename', (string) ($result['prefix'] ?? ''), ['old_prefix' => $result['old_prefix'] ?? ''], (string) $this->request->getIPAddress());
            } else {
                $result = $service->rename((string) ($data['key'] ?? ''), (string) ($data['name'] ?? ''));
                (new FMSStorageAuditService())->log($this->user(), 'object.rename', (string) ($result['key'] ?? ''), ['old_key' => $result['old_key'] ?? ''], (string) $this->request->getIPAddress());
            }
            return $this->json(['status' => true, 'data' => $result]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createFolder(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::UPLOAD)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            $result = (new FMSStorageObjectService())->createFolder(
                (string) ($data['prefix'] ?? ''),
                (string) ($data['name'] ?? '')
            );
            (new FMSStorageAuditService())->log($this->user(), 'folder.create', (string) ($result['prefix'] ?? ''), [], (string) $this->request->getIPAddress());
            return $this->json(['status' => true, 'data' => $result], 201);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function copy(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::UPLOAD)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            $result = (new FMSStorageObjectService())->copy((string) ($data['key'] ?? ''), (string) ($data['prefix'] ?? ''));
            (new FMSStorageAuditService())->log($this->user(), 'object.copy', (string) ($result['key'] ?? ''), ['source_key' => $result['source_key'] ?? ''], (string) $this->request->getIPAddress());
            return $this->json(['status' => true, 'data' => $result], 201);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function audit(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $limit = (int) $this->request->getGet('limit');
        $offset = (int) $this->request->getGet('offset');
        return $this->json(['status' => true, 'data' => [
            'items' => (new FMSStorageAuditService())->list($limit > 0 ? $limit : 100, max(0, $offset)),
        ]]);
    }

    public function users(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $service = new FMSStorageUserAdminService();
        return $this->json(['status' => true, 'data' => [
            'users' => $service->users(),
            'roles' => $service->roles(),
            'permissions' => $service->permissions(),
        ]]);
    }

    public function createUser(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            return $this->json(['status' => true, 'data' => (new FMSStorageUserAdminService())->createUser($data)], 201);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateUser(int $userId): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            return $this->json(['status' => true, 'data' => (new FMSStorageUserAdminService())->updateUser($userId, $data)]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteUser(int $userId): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        try {
            (new FMSStorageUserAdminService())->deleteUser($userId);
            return $this->json(['status' => true]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function roles(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $service = new FMSStorageUserAdminService();
        return $this->json(['status' => true, 'data' => [
            'roles' => $service->roles(),
            'permissions' => $service->permissions(),
        ]]);
    }

    public function saveRole(): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        try {
            return $this->json(['status' => true, 'data' => (new FMSStorageUserAdminService())->saveRole($data)], 201);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteRole(int $roleId): ResponseInterface
    {
        if (! $this->allowed(FMSStorageAuthorizationService::USERS)) return $this->deny();
        try {
            (new FMSStorageUserAdminService())->deleteRole($roleId);
            return $this->json(['status' => true]);
        } catch (Throwable $e) {
            return $this->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
