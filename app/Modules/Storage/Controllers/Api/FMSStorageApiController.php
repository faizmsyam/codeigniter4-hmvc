<?php
namespace App\Modules\Storage\Controllers\Api;

use App\Core\FMSController;
use App\Modules\Storage\Config\FMSStorage;
use App\Modules\Storage\Models\FMSStorageUserModel;
use App\Modules\Storage\Services\FMSStorageAuthorizationService;
use App\Modules\Storage\Services\FMSStorageObjectService;
use App\Modules\Storage\Services\FMSStorageSessionService;
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
        if (! is_array($user) || ! password_verify((string) ($data['password'] ?? ''), (string) ($user['password_hash'] ?? ''))) {
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
            $result = (new FMSStorageObjectService())->list((string) $this->request->getGet('prefix'));
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
            return $this->json(['status' => (new FMSStorageObjectService())->delete((string) ($data['key'] ?? ''))]);
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
            (new FMSStorageObjectService())->readLocal($key);
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

    public function changePassword(): ResponseInterface
    {
        $user = $this->user();
        if (! $user) return $this->json(['status' => false], 401);
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        if (! password_verify((string) ($data['current_password'] ?? ''), (string) $user['password_hash'])) {
            return $this->json(['status' => false, 'message' => 'Password saat ini salah.'], 422);
        }
        $password = (string) ($data['new_password'] ?? '');
        if (strlen($password) < 12) return $this->json(['status' => false, 'message' => 'Password minimal 12 karakter.'], 422);
        (new FMSStorageUserModel())->update((int) $user['id'], [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->json(['status' => true]);
    }
}
