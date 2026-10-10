<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Controllers\Api;

use App\Core\FMSApiController;
use App\Filters\FMSRequestContext;
use App\Libraries\FMSAuditLogger;
use App\Modules\Settings\Services\FMSApiKeyManagementService;
use App\Modules\Settings\Services\FMSBasicAuthManagementService;
use App\Modules\Settings\Services\FMSEmailService;
use App\Modules\Settings\Services\FMSSettingsService;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use Throwable;

final class FMSSettingsApiController extends FMSApiController
{
    private FMSSettingsService $settingsService;
    private FMSApiKeyManagementService $apiKeyService;
    private FMSBasicAuthManagementService $basicAuthService;
    private FMSEmailService $emailService;

    public function __construct(
        ?FMSSettingsService $settingsService = null,
        ?FMSApiKeyManagementService $apiKeyService = null,
        ?FMSBasicAuthManagementService $basicAuthService = null,
        ?FMSEmailService $emailService = null,
    ) {
        parent::__construct();
        $this->settingsService  = $settingsService ?? new FMSSettingsService();
        $this->apiKeyService    = $apiKeyService ?? new FMSApiKeyManagementService();
        $this->basicAuthService = $basicAuthService ?? new FMSBasicAuthManagementService();
        $this->emailService     = $emailService ?? new FMSEmailService();
    }

    /* ── Akses Kontrol Super Administrator ────────────────────────── */

    private function guardSuperAdministrator(): ?ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondUnauthorized('Sesi autentikasi tidak valid.');
        }

        if (! $this->authorizationService->isSuperAdministrator($authenticatedSubject)) {
            $actorId = (int) ($authenticatedSubject['user_id'] ?? 0);
            try {
                FMSAuditLogger::record(
                    event: 'access.denied',
                    module: 'settings',
                    actorId: $actorId > 0 ? $actorId : null,
                    entityType: 'module',
                    entityId: 'settings',
                    description: 'Akses API konfigurasi ditolak: Hanya Super Administrator yang diizinkan.',
                    httpMethod: $this->request->getMethod(),
                    statusCode: 403,
                );
            } catch (Throwable) {
            }

            return $this->respondForbidden('Hanya Super Administrator yang berhak mengakses pengaturan sistem.');
        }

        return null;
    }

    private function currentActorId(): int
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);

        return is_array($authenticatedSubject) ? (int) ($authenticatedSubject['user_id'] ?? 0) : 0;
    }

    private function parsePayload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $rawInput = $this->request->getRawInput();
        if (is_array($rawInput) && $rawInput !== []) {
            return $rawInput;
        }

        return $this->request->getPost() ?: [];
    }

    /* ── Overview ─────────────────────────────────────────────────── */

    public function overview(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        try {
            $overview = $this->settingsService->getSystemOverview();

            return $this->respondOk('Ringkasan sistem berhasil dimuat.', $overview);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memuat ringkasan sistem: ' . $e->getMessage());
        }
    }

    /* ── Auth Settings ────────────────────────────────────────────── */

    public function getAuthSettings(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        try {
            $settings = $this->settingsService->getAuthSettings();

            return $this->respondOk('Pengaturan autentikasi berhasil dimuat.', ['auth_settings' => $settings]);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memuat pengaturan autentikasi: ' . $e->getMessage());
        }
    }

    public function updateAuthSettings(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();
        $payload = $this->parsePayload();

        try {
            $updated = $this->settingsService->updateAuthSettings($payload, $actorId);

            FMSAuditLogger::record(
                event: 'settings.auth_updated',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'auth_settings',
                entityId: (string) ($updated['id'] ?? '1'),
                description: 'Pengaturan autentikasi sistem diperbarui.',
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Pengaturan autentikasi berhasil diperbarui.', ['auth_settings' => $updated]);
        } catch (InvalidArgumentException $validationException) {
            $decoded = json_decode($validationException->getMessage(), true);
            $errors  = is_array($decoded) ? $decoded : ['auth_settings' => [$validationException->getMessage()]];

            return $this->respondValidationError($errors);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memperbarui pengaturan autentikasi: ' . $e->getMessage());
        }
    }

    /* ── Email Settings ───────────────────────────────────────────── */

    public function getEmailSettings(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        try {
            return $this->respondOk('Konfigurasi email berhasil dimuat.', [
                'email_settings' => $this->emailService->publicSettings(),
            ]);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memuat konfigurasi email: ' . $e->getMessage());
        }
    }

    public function updateEmailSettings(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        try {
            $updated = $this->emailService->updateSettings($this->parsePayload(), $this->currentActorId());
            FMSAuditLogger::record(
                event: 'settings.email_updated',
                module: 'settings',
                actorId: $this->currentActorId(),
                entityType: 'email_settings',
                entityId: (string) ($updated['id'] ?? '1'),
                description: 'Konfigurasi pengiriman email diperbarui.',
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Konfigurasi email berhasil diperbarui.', ['email_settings' => $updated]);
        } catch (InvalidArgumentException $e) {
            return $this->respondValidationError(['email_settings' => [$e->getMessage()]]);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal menyimpan konfigurasi email: ' . $e->getMessage());
        }
    }

    public function testEmailSettings(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $payload = $this->parsePayload();
        $recipient = trim((string) ($payload['recipient_email'] ?? ''));
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return $this->respondValidationError(['recipient_email' => ['Email tujuan tes tidak valid.']]);
        }

        try {
            $this->emailService->testConnection($recipient);

            return $this->respondOk('Email tes berhasil dikirim.');
        } catch (Throwable $e) {
            return $this->respondServerError('Email tes gagal dikirim: ' . $e->getMessage());
        }
    }

    /* ── API Keys ─────────────────────────────────────────────────── */

    public function listApiKeys(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $filters = [
            'search'      => (string) $this->request->getGet('search'),
            'status'      => (string) $this->request->getGet('status'),
            'environment' => (string) $this->request->getGet('environment'),
            'page'        => (int) ($this->request->getGet('page') ?? 1),
            'per_page'    => (int) ($this->request->getGet('per_page') ?? 10),
        ];

        try {
            $result = $this->apiKeyService->listApiKeys($filters);

            return $this->respondOk('Daftar API Key berhasil dimuat.', $result);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memuat API Key: ' . $e->getMessage());
        }
    }

    public function createApiKey(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();
        $payload = $this->parsePayload();

        try {
            $created = $this->apiKeyService->createApiKey($payload, $actorId);

            FMSAuditLogger::record(
                event: 'api_key.created',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'api_key',
                entityId: (string) ($created['key']['key_id'] ?? ''),
                description: 'API Key baru dibuat: ' . ($created['key']['label'] ?? ''),
                httpMethod: $this->request->getMethod(),
                statusCode: 201,
            );

            return $this->respondCreated('API Key berhasil dibuat. Simpan secret key ini sekarang karena tidak akan ditampilkan lagi.', $created);
        } catch (InvalidArgumentException $e) {
            $decoded = json_decode($e->getMessage(), true);
            $errors  = is_array($decoded) ? $decoded : ['api_key' => [$e->getMessage()]];

            return $this->respondValidationError($errors);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal membuat API Key: ' . $e->getMessage());
        }
    }

    public function showApiKey(string $keyId): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $key = $this->apiKeyService->getApiKey($keyId);
        if ($key === null) {
            return $this->respondNotFound('API Key tidak ditemukan.');
        }

        return $this->respondOk('Detail API Key berhasil dimuat.', ['key' => $key]);
    }

    public function updateApiKey(string $keyId): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();
        $payload = $this->parsePayload();

        try {
            $updated = $this->apiKeyService->updateApiKey($keyId, $payload, $actorId);

            FMSAuditLogger::record(
                event: 'api_key.updated',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'api_key',
                entityId: $keyId,
                description: 'API Key diperbarui: ' . $keyId,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('API Key berhasil diperbarui.', ['key' => $updated]);
        } catch (InvalidArgumentException $e) {
            $decoded = json_decode($e->getMessage(), true);
            $errors  = is_array($decoded) ? $decoded : ['api_key' => [$e->getMessage()]];

            return $this->respondValidationError($errors);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memperbarui API Key: ' . $e->getMessage());
        }
    }

    public function revokeApiKey(string $keyId): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $revoked = $this->apiKeyService->revokeApiKey($keyId, $actorId);

            FMSAuditLogger::record(
                event: 'api_key.revoked',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'api_key',
                entityId: $keyId,
                description: 'API Key dicabut/dinonaktifkan: ' . $keyId,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('API Key berhasil dinonaktifkan (revoked).', ['key' => $revoked]);
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal mencabut API Key: ' . $e->getMessage());
        }
    }

    public function activateApiKey(string $keyId): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $activated = $this->apiKeyService->activateApiKey($keyId, $actorId);

            FMSAuditLogger::record(
                event: 'api_key.activated',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'api_key',
                entityId: $keyId,
                description: 'API Key diaktifkan kembali: ' . $keyId,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('API Key berhasil diaktifkan kembali.', ['key' => $activated]);
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal mengaktifkan API Key: ' . $e->getMessage());
        }
    }

    public function rollApiKey(string $keyId): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $rolled = $this->apiKeyService->rollApiKey($keyId, $actorId);

            FMSAuditLogger::record(
                event: 'api_key.rolled',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'api_key',
                entityId: $keyId,
                description: 'Secret API Key di-regenerate (rolled): ' . $keyId,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Secret API Key baru berhasil dibuat. Segera salin secret baru ini.', $rolled);
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal me-roll API Key: ' . $e->getMessage());
        }
    }

    public function deleteApiKey(string $keyId): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $this->apiKeyService->deleteApiKey($keyId, $actorId);

            FMSAuditLogger::record(
                event: 'api_key.deleted',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'api_key',
                entityId: $keyId,
                description: 'API Key dihapus permanen: ' . $keyId,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('API Key berhasil dihapus.');
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal menghapus API Key: ' . $e->getMessage());
        }
    }

    /* ── Basic Auth Clients ───────────────────────────────────────── */

    public function listBasicAuthClients(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $filters = [
            'search'      => (string) $this->request->getGet('search'),
            'status'      => (string) $this->request->getGet('status'),
            'environment' => (string) $this->request->getGet('environment'),
            'page'        => (int) ($this->request->getGet('page') ?? 1),
            'per_page'    => (int) ($this->request->getGet('per_page') ?? 10),
        ];

        try {
            $result = $this->basicAuthService->listClients($filters);

            return $this->respondOk('Daftar Basic Auth Client berhasil dimuat.', $result);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memuat Basic Auth Client: ' . $e->getMessage());
        }
    }

    public function createBasicAuthClient(): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();
        $payload = $this->parsePayload();

        try {
            $created = $this->basicAuthService->createClient($payload, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.created',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: (string) ($created['client']['username'] ?? ''),
                description: 'Basic Auth Client baru dibuat: ' . ($created['client']['username'] ?? ''),
                httpMethod: $this->request->getMethod(),
                statusCode: 201,
            );

            return $this->respondCreated('Basic Auth Client berhasil dibuat. Simpan password ini sekarang.', $created);
        } catch (InvalidArgumentException $e) {
            $decoded = json_decode($e->getMessage(), true);
            $errors  = is_array($decoded) ? $decoded : ['basic_auth' => [$e->getMessage()]];

            return $this->respondValidationError($errors);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal membuat Basic Auth Client: ' . $e->getMessage());
        }
    }

    public function showBasicAuthClient(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $client = $this->basicAuthService->getClient($username);
        if ($client === null) {
            return $this->respondNotFound('Basic Auth Client tidak ditemukan.');
        }

        return $this->respondOk('Detail Basic Auth Client berhasil dimuat.', ['client' => $client]);
    }

    public function updateBasicAuthClient(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();
        $payload = $this->parsePayload();

        try {
            $updated = $this->basicAuthService->updateClient($username, $payload, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.updated',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: $username,
                description: 'Basic Auth Client diperbarui: ' . $username,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Basic Auth Client berhasil diperbarui.', ['client' => $updated]);
        } catch (InvalidArgumentException $e) {
            $decoded = json_decode($e->getMessage(), true);
            $errors  = is_array($decoded) ? $decoded : ['basic_auth' => [$e->getMessage()]];

            return $this->respondValidationError($errors);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal memperbarui Basic Auth Client: ' . $e->getMessage());
        }
    }

    public function resetBasicAuthPassword(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();
        $payload = $this->parsePayload();
        $customPassword = (string) ($payload['password'] ?? '');

        try {
            $reset = $this->basicAuthService->resetPassword($username, $customPassword, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.password_reset',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: $username,
                description: 'Password Basic Auth Client di-reset: ' . $username,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Password berhasil di-reset. Simpan password baru ini.', $reset);
        } catch (InvalidArgumentException $e) {
            $decoded = json_decode($e->getMessage(), true);
            $errors  = is_array($decoded) ? $decoded : ['basic_auth' => [$e->getMessage()]];

            return $this->respondValidationError($errors);
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal mereset password: ' . $e->getMessage());
        }
    }

    public function unlockBasicAuthClient(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $unlocked = $this->basicAuthService->unlockClient($username, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.unlocked',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: $username,
                description: 'Basic Auth Client dibuka kuncinya (unlocked): ' . $username,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Akun Basic Auth Client berhasil dibuka (unlocked).', ['client' => $unlocked]);
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal membuka akun Basic Auth: ' . $e->getMessage());
        }
    }

    public function revokeBasicAuthClient(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $revoked = $this->basicAuthService->revokeClient($username, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.revoked',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: $username,
                description: 'Basic Auth Client dinonaktifkan: ' . $username,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Basic Auth Client berhasil dinonaktifkan.', ['client' => $revoked]);
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal menonaktifkan Basic Auth: ' . $e->getMessage());
        }
    }

    public function activateBasicAuthClient(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $activated = $this->basicAuthService->activateClient($username, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.activated',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: $username,
                description: 'Basic Auth Client diaktifkan kembali: ' . $username,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Basic Auth Client berhasil diaktifkan kembali.', ['client' => $activated]);
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal mengaktifkan Basic Auth: ' . $e->getMessage());
        }
    }

    public function deleteBasicAuthClient(string $username): ResponseInterface
    {
        if ($guard = $this->guardSuperAdministrator()) {
            return $guard;
        }

        $actorId = $this->currentActorId();

        try {
            $this->basicAuthService->deleteClient($username, $actorId);

            FMSAuditLogger::record(
                event: 'basic_auth.deleted',
                module: 'settings',
                actorId: $actorId > 0 ? $actorId : null,
                entityType: 'basic_auth_client',
                entityId: $username,
                description: 'Basic Auth Client dihapus: ' . $username,
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Basic Auth Client berhasil dihapus.');
        } catch (InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->respondServerError('Gagal menghapus Basic Auth: ' . $e->getMessage());
        }
    }
}
