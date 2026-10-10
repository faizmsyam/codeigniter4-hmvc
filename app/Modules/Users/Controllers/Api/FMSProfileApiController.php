<?php

namespace App\Modules\Users\Controllers\Api;

use App\Config\FMSUploads;
use App\Core\FMSApiController;
use App\Libraries\FMSAuditLogger;
use App\Modules\ActivityLogs\Models\FMSActivityLogModel;
use App\Modules\ActivityLogs\Services\FMSActivityLogRedactionService;
use App\Modules\ActivityLogs\Services\FMSActivityLogService;
use App\Modules\ActivityLogs\Validation\FMSActivityLogValidation;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesManagementRepository;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository;
use App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService;
use App\Modules\Uploads\Exceptions\FMSUploadException;
use App\Modules\Uploads\Services\FMSObjectStorageFactory;
use App\Modules\Uploads\Services\FMSPrivateUploadService;
use App\Modules\Uploads\Services\FMSWebPUploadService;
use App\Modules\Users\Models\FMSUserModel;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use Throwable;

final class FMSProfileApiController extends FMSApiController
{
    private const SWITCHABLE_GROUP_LIMIT = 100;

    private FMSActivityLogService $activityLogService;

    private FMSActivityLogValidation $activityLogValidation;

    public function __construct(
        ?FMSActivityLogService $activityLogService = null,
        ?FMSActivityLogValidation $activityLogValidation = null,
    ) {
        parent::__construct();

        $this->activityLogService = $activityLogService
            ?? new FMSActivityLogService(new FMSActivityLogModel(), new FMSActivityLogRedactionService());
        $this->activityLogValidation = $activityLogValidation ?? new FMSActivityLogValidation();
    }

    /**
     * Read model lengkap halaman Profile. Semua query/read storage berada di API.
     */
    public function show(): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.read')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Identitas pengguna tidak tersedia atau sesi pengguna tidak sesuai.');
        }

        $userRow = (new FMSUserModel())->find($userIdentifier);
        if (! is_array($userRow)) {
            return $this->respondNotFound('Data pengguna tidak ditemukan.');
        }

        $assignedGroupIds = $this->assignedGroupIdentifiers($userIdentifier);
        $currentGroupIdentifier = (int) (session()->get('fms_backend_active_group_id') ?? 0);
        if ($currentGroupIdentifier <= 0 && $assignedGroupIds !== []) {
            $currentGroupIdentifier = (int) $assignedGroupIds[0];
            session()->set('fms_backend_active_group_id', $currentGroupIdentifier);
        }

        $assignedGroups = $this->assignedGroups($assignedGroupIds);
        $activeGroupName = '';
        foreach ($assignedGroups as $assignedGroup) {
            if ((int) ($assignedGroup['id'] ?? 0) === $currentGroupIdentifier) {
                $activeGroupName = (string) ($assignedGroup['name'] ?? '');
                break;
            }
        }

        $switchableGroups = array_values(array_filter(
            $assignedGroups,
            static fn (array $groupRow): bool => (int) ($groupRow['id'] ?? 0) !== $currentGroupIdentifier,
        ));

        $activePermissions = [];
        $storedPermissions = session()->get('fms_backend_permissions');
        if (is_array($storedPermissions)) {
            $activePermissions = array_values(array_filter(
                array_map(static fn ($permissionCode): string => trim((string) $permissionCode), $storedPermissions),
                static fn (string $permissionCode): bool => $permissionCode !== '',
            ));
        }

        return $this->respondOk('Profil pengguna berhasil dimuat.', [
            'user' => [
                'id'            => (int) ($userRow['id'] ?? 0),
                'username'      => (string) ($userRow['username'] ?? ''),
                'email'         => (string) ($userRow['email'] ?? ''),
                'email_verified_at' => trim((string) ($userRow['email_verified_at'] ?? '')),
                'is_email_verified' => trim((string) ($userRow['email_verified_at'] ?? '')) !== '',
                'full_name'     => (string) ($userRow['full_name'] ?? ''),
                'phone'         => (string) ($userRow['phone'] ?? ''),
                'status'        => (string) ($userRow['status'] ?? 'active'),
                'created_at'    => (string) ($userRow['created_at'] ?? ''),
                'last_login_at' => (string) ($userRow['last_login_at'] ?? ''),
            ],
            'avatar_url'         => $this->resolveAvatarUrl((string) ($userRow['avatar'] ?? '')),
            'groups'             => $assignedGroups,
            'switchable_groups'  => $switchableGroups,
            'current_group_id'   => $currentGroupIdentifier,
            'active_group_name'  => $activeGroupName,
            'active_permissions' => $activePermissions,
            'can' => [
                'update'          => $this->profileAllows('profile.update'),
                'update_avatar'   => $this->profileAllows('profile.update_avatar'),
                'change_password' => $this->profileAllows('profile.change_password'),
            ],
        ]);
    }

    public function activityLogs(): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.read')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Identitas pengguna tidak tersedia atau sesi pengguna tidak sesuai.');
        }

        $searchFilters = [
            'actor_user_id'  => $userIdentifier,
            'event'          => $this->request->getGet('event'),
            'module'         => $this->request->getGet('module'),
            'entity_type'    => $this->request->getGet('entity_type'),
            'entity_id'      => $this->request->getGet('entity_id'),
            'http_method'    => $this->request->getGet('http_method'),
            'status_code'    => $this->request->getGet('status_code'),
            'request_id'     => $this->request->getGet('request_id'),
            'date_from'      => $this->request->getGet('date_from'),
            'date_to'        => $this->request->getGet('date_to'),
            'search_keyword' => $this->request->getGet('search_keyword'),
            'page'           => $this->request->getGet('page'),
            'per_page'       => $this->request->getGet('per_page'),
        ];

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->activityLogValidation->getListRules());

        if (! $validationService->run($searchFilters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $pageNumber = (int) ($searchFilters['page'] ?? 1);
        $pageSize = (int) ($searchFilters['per_page'] ?? FMSActivityLogService::DEFAULT_PAGE_SIZE);
        $searchResult = $this->activityLogService->searchActivity($searchFilters, $pageNumber, $pageSize);

        return $this->respondOk('Aktivitas pengguna berhasil dimuat.', $searchResult);
    }

    public function activityLogDetail(string $activityLogUuid = ''): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.read')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0 || trim($activityLogUuid) === '') {
            return $this->respondUnauthorized('Detail aktivitas tidak dapat diakses.');
        }

        $activityLogEntry = $this->activityLogService->findActivity($activityLogUuid);
        if ($activityLogEntry === null || (int) ($activityLogEntry['actor_user_id'] ?? 0) !== $userIdentifier) {
            return $this->respondNotFound('Detail aktivitas tidak ditemukan.');
        }

        return $this->respondOk('Detail aktivitas berhasil dimuat.', $activityLogEntry);
    }

    public function switchGroup(): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.read')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Identitas pengguna tidak tersedia atau sesi pengguna tidak sesuai.');
        }

        $payload = $this->profilePayload();
        $groupIdentifier = (int) ($payload['group_id'] ?? 0);

        if ($groupIdentifier <= 0) {
            return $this->respondUnprocessableEntity('ID kelompok tidak valid.');
        }

        try {
            $membershipRow = db_connect()
                ->table('t_user_groups')
                ->where('user_id', $userIdentifier)
                ->where('group_id', $groupIdentifier)
                ->get(1)
                ->getRowArray();
        } catch (Throwable $exception) {
            log_message('error', 'Profile switchGroup DB: {msg}', ['msg' => $exception->getMessage()]);

            return $this->respondServerError('Gagal memeriksa kelompok.');
        }

        if (! is_array($membershipRow)) {
            return $this->respondForbidden('Anda bukan anggota kelompok tersebut.');
        }

        /* Hak akses diambil dari kelompok terpilih saja agar ganti role konsisten */
        try {
            $permissionCodes = (new FMSPrivilegesEffectivePermissionService(
                new FMSDatabasePrivilegesPermissionRepository(),
            ))->resolveEffectivePermissionCodesForGroup($groupIdentifier);
        } catch (Throwable $exception) {
            log_message('error', 'Profile switchGroup perm: {msg}', ['msg' => $exception->getMessage()]);

            return $this->respondServerError('Gagal memuat hak akses.');
        }

        session()->set('fms_backend_active_group_id', $groupIdentifier);
        session()->set('fms_backend_permissions', $permissionCodes);

        FMSAuditLogger::record(
            event: 'auth.switch_group',
            module: 'profile',
            actorId: $userIdentifier,
            entityType: 'group',
            entityId: (string) $groupIdentifier,
            description: 'Pengguna berpindah kelompok aktif ke group ID ' . $groupIdentifier,
            httpMethod: 'POST',
            statusCode: 200,
        );

        return $this->respondOk('Kelompok aktif berhasil diubah.', [
            'group_id' => $groupIdentifier,
        ]);
    }

    public function updateProfile(): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.update')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Identitas pengguna tidak tersedia atau sesi pengguna tidak sesuai.');
        }

        $payload = $this->profilePayload();
        $profileData = [];

        foreach (['full_name', 'phone'] as $fieldName) {
            if (isset($payload[$fieldName])) {
                $profileData[$fieldName] = trim((string) $payload[$fieldName]);
            }
        }

        if ($profileData === []) {
            return $this->respondUnprocessableEntity('Tidak ada data yang diubah.');
        }

        $beforeData = [];
        try {
            $existingUser = (new FMSUserModel())->find($userIdentifier) ?? [];
            foreach ($profileData as $fieldName => $_unusedValue) {
                $beforeData[$fieldName] = $existingUser[$fieldName] ?? null;
            }

            $profileData['updated_by'] = $userIdentifier;
            (new FMSUserModel())->update($userIdentifier, $profileData);
        } catch (Throwable $exception) {
            log_message('error', 'Profile update failed: {msg}', ['msg' => $exception->getMessage()]);

            return $this->respondServerError('Gagal memperbarui profil.');
        }

        if (isset($profileData['full_name'])) {
            session()->set('fms_backend_full_name', $profileData['full_name']);
        }

        FMSAuditLogger::record(
            event: 'profile.updated',
            module: 'profile',
            actorId: $userIdentifier,
            entityType: 'user',
            entityId: (string) $userIdentifier,
            description: 'Profil diperbarui.',
            before: $beforeData,
            after: $profileData,
            httpMethod: 'PATCH',
            statusCode: 200,
        );

        return $this->respondOk('Profil berhasil diperbarui.');
    }

    public function updateAvatar(): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.update_avatar')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Identitas pengguna tidak tersedia atau sesi pengguna tidak sesuai.');
        }

        $uploadedFiles = $this->request->getFiles();
        $uploadedFile = $uploadedFiles['avatar'] ?? null;

        if (is_array($uploadedFile)) {
            $uploadedFile = reset($uploadedFile);
        }

        if ($uploadedFile === null || $uploadedFile->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->respondUnprocessableEntity('File avatar tidak ditemukan dalam permintaan.');
        }

        try {
            $uploadService = new FMSWebPUploadService();
            $storedUpload = $uploadService->storeUploadedFile($uploadedFile, 'fms-avatars');

            /* Hapus avatar lama dari storage jika ada */
            $oldUser = (new FMSUserModel())->find($userIdentifier);
            $oldObjectKey = (string) ($oldUser['avatar'] ?? '');
            if ($oldObjectKey !== '') {
                try {
                    $uploadService->deleteStoredFile($oldObjectKey);
                } catch (Throwable $cleanupException) {
                    log_message('error', 'Profile avatar cleanup: {msg}', ['msg' => $cleanupException->getMessage()]);
                }
            }

            /* Simpan object_key (bukan presigned URL) ke DB */
            (new FMSUserModel())->update($userIdentifier, [
                'avatar'     => $storedUpload['object_key'],
                'updated_by' => $userIdentifier,
            ]);

            /* Presigned URL segar untuk dikembalikan ke JS */
            $freshAvatarUrl = $this->resolveAvatarUrl($storedUpload['object_key']);

            /* Sync session */
            session()->set('fms_backend_avatar', $storedUpload['object_key']);
        } catch (InvalidArgumentException $exception) {
            return $this->respondUnprocessableEntity($exception->getMessage());
        } catch (FMSUploadException $exception) {
            return $this->respondUnprocessableEntity($exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Profile avatar upload: {msg}', ['msg' => $exception->getMessage()]);

            return $this->respondServerError('Gagal mengunggah avatar.');
        }

        FMSAuditLogger::record(
            event: 'profile.avatar_updated',
            module: 'profile',
            actorId: $userIdentifier,
            entityType: 'user',
            entityId: (string) $userIdentifier,
            description: 'Avatar profil diperbarui.',
            httpMethod: 'POST',
            statusCode: 200,
        );

        return $this->respondOk('Avatar berhasil diperbarui.', [
            'avatar_url' => $freshAvatarUrl,
        ]);
    }

    public function changePassword(): ResponseInterface
    {
        if (($authorizationFailure = $this->requireApiPermission('profile.change_password')) !== null) {
            return $authorizationFailure;
        }

        $userIdentifier = $this->profileUserIdentifier();
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Identitas pengguna tidak tersedia atau sesi pengguna tidak sesuai.');
        }

        $payload = $this->profilePayload();
        $currentPassword = (string) ($payload['current_password'] ?? '');
        $newPassword = (string) ($payload['new_password'] ?? '');
        $confirmPassword = (string) ($payload['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return $this->respondUnprocessableEntity('Semua kolom password wajib diisi.');
        }

        if ($newPassword !== $confirmPassword) {
            return $this->respondUnprocessableEntity('Konfirmasi password baru tidak cocok.');
        }

        /* Aturan kekuatan password: minimal 8 karakter, 3 kelas karakter */
        $characterClassCount = 0;
        $characterClassCount += preg_match('/[a-z]/', $newPassword) === 1 ? 1 : 0;
        $characterClassCount += preg_match('/[A-Z]/', $newPassword) === 1 ? 1 : 0;
        $characterClassCount += preg_match('/[0-9]/', $newPassword) === 1 ? 1 : 0;
        $characterClassCount += preg_match('/[^A-Za-z0-9]/', $newPassword) === 1 ? 1 : 0;

        if (mb_strlen($newPassword) < 8 || $characterClassCount < 3) {
            return $this->respondUnprocessableEntity('Password baru minimal 8 karakter dan mengandung 3 jenis karakter (huruf besar, huruf kecil, angka, simbol).');
        }

        try {
            $userRow = (new FMSUserModel())->find($userIdentifier);
            if (! is_array($userRow)) {
                return $this->respondNotFound('Data pengguna tidak ditemukan.');
            }

            $storedPasswordHash = (string) ($userRow['password_hash'] ?? '');
            if ($storedPasswordHash === '' || ! password_verify($currentPassword, $storedPasswordHash)) {
                return $this->respondUnprocessableEntity('Password saat ini tidak sesuai.');
            }

            (new FMSUserModel())->update($userIdentifier, [
                'password_hash'       => password_hash($newPassword, PASSWORD_DEFAULT),
                'password_changed_at' => date('Y-m-d H:i:s'),
                'updated_by'          => $userIdentifier,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Profile changePassword failed: {msg}', ['msg' => $exception->getMessage()]);

            return $this->respondServerError('Gagal memperbarui password.');
        }

        FMSAuditLogger::record(
            event: 'profile.password_changed',
            module: 'profile',
            actorId: $userIdentifier,
            entityType: 'user',
            entityId: (string) $userIdentifier,
            description: 'Password profil diperbarui sendiri oleh pengguna.',
            httpMethod: 'POST',
            statusCode: 200,
        );

        return $this->respondOk('Password berhasil diperbarui. Silakan login ulang jika diperlukan.');
    }

    /**
     * Mandatory password change — no current-password required.
     * Triggered after login when user.must_change_password or admin policy forces it.
     */
    public function forceChangePassword(): ResponseInterface
    {
        // Authenticate via session (no JWT required for this specific flow)
        $userIdentifier = (int) session()->get('fms_backend_user_id');
        if ($userIdentifier <= 0) {
            return $this->respondUnauthorized('Sesi tidak valid. Silakan login ulang.');
        }

        // Must be in must-change-password state
        if (session()->get('fms_backend_must_change_password') !== true) {
            return $this->respondForbidden('Tidak ada instruksi penggantian password untuk sesi ini.');
        }

        $payload = $this->profilePayload();
        $newPassword = (string) ($payload['new_password'] ?? '');
        $confirmPassword = (string) ($payload['confirm_password'] ?? '');

        if ($newPassword === '' || $confirmPassword === '') {
            return $this->respondUnprocessableEntity('Kolom password baru wajib diisi.');
        }

        if ($newPassword !== $confirmPassword) {
            return $this->respondUnprocessableEntity('Konfirmasi password baru tidak cocok.');
        }

        // Password strength: min 8 chars, 3 of 4 classes
        $charClassCount = 0;
        $charClassCount += preg_match('/[a-z]/', $newPassword) === 1 ? 1 : 0;
        $charClassCount += preg_match('/[A-Z]/', $newPassword) === 1 ? 1 : 0;
        $charClassCount += preg_match('/[0-9]/', $newPassword) === 1 ? 1 : 0;
        $charClassCount += preg_match('/[^A-Za-z0-9]/', $newPassword) === 1 ? 1 : 0;

        if (mb_strlen($newPassword) < 8 || $charClassCount < 3) {
            return $this->respondUnprocessableEntity(
                'Password baru minimal 8 karakter dan mengandung minimal 3 dari 4 jenis: huruf besar, huruf kecil, angka, dan simbol.'
            );
        }

        try {
            $userRow = (new FMSUserModel())->find($userIdentifier);
            if (! is_array($userRow)) {
                return $this->respondNotFound('Data pengguna tidak ditemukan.');
            }

            $backendSession = session();
            $tokenFamilyId = trim((string) $backendSession->get('fms_backend_token_family_id'));

            // Increment session_version to invalidate any other active tokens
            $newSessionVersion = ((int) ($userRow['session_version'] ?? 1)) + 1;

            (new FMSUserModel())->update($userIdentifier, [
                'password_hash'       => password_hash($newPassword, PASSWORD_DEFAULT),
                'password_changed_at' => date('Y-m-d H:i:s'),
                'must_change_password' => 0,
                'session_version'     => $newSessionVersion,
                'updated_by'          => $userIdentifier,
            ]);

            // Clear the must-change-password session flag
            $backendSession->remove('fms_backend_must_change_password');
            // Regenerate session ID after password change (security best practice)
            $backendSession->regenerate(false);

            FMSAuditLogger::record(
                event: 'profile.password_forced_changed',
                module: 'profile',
                actorId: $userIdentifier,
                entityType: 'user',
                entityId: (string) $userIdentifier,
                description: 'Password mandatory change berhasil dilakukan setelah login.',
                httpMethod: 'POST',
                statusCode: 200,
            );

            return $this->respondOk('Password berhasil diperbarui. Mengalihkan ke dashboard...', [
                'redirect_url' => site_url(ROUTE_ADMIN . '/dashboard'),
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Profile forceChangePassword failed: {msg}', ['msg' => $exception->getMessage()]);

            return $this->respondServerError('Gagal memperbarui password.');
        }
    }

    /**
     * Parse request body — JSON preferred, fallback ke POST form data.
     *
     * getJSON(true) throws RuntimeException bila Content-Type bukan JSON,
     * jadi kita tangkap exception-nya supaya POST fallback tetap jalan.
     *
     * @return array<string, mixed>
     */
    private function profilePayload(): array
    {
        // 1. Coba JSON body
        try {
            $jsonPayload = $this->request->getJSON(true);
            if (is_array($jsonPayload) && $jsonPayload !== []) {
                return $jsonPayload;
            }
        } catch (Throwable) {
            // Bukan JSON atau parsing gagal — lanjut ke fallback POST.
        }

        // 2. Fallback: application/x-www-form-urlencoded atau raw POST
        $post = (array) ($this->request->getPost() ?? []);
        if ($post !== []) {
            return $post;
        }

        // 3. Fallback: raw body string yang belum ter-parse (edge case)
        $raw = trim((string) $this->request->getBody());
        if ($raw !== '' && str_starts_with($raw, '{')) {
            try {
                $decoded = json_decode($raw, true, 4, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (Throwable) {
                // bukan JSON yang valid — abaikan
            }
        }

        return [];
    }

    private function profileUserIdentifier(): int
    {
        $authenticatedUserIdentifier = $this->authenticatedApiUserIdentifier();
        $sessionUserIdentifier = (int) session()->get('fms_backend_user_id');

        if ($authenticatedUserIdentifier <= 0) {
            return 0;
        }

        if ($sessionUserIdentifier > 0 && $sessionUserIdentifier !== $authenticatedUserIdentifier) {
            return 0;
        }

        return $authenticatedUserIdentifier;
    }

    private function profileAllows(string $permissionCode): bool
    {
        $authenticatedSubject = \App\Filters\FMSRequestContext::authenticatedSubject($this->request);

        return is_array($authenticatedSubject)
            && $this->authorizationService->allows($authenticatedSubject, $permissionCode);
    }

    /** @return list<int> */
    private function assignedGroupIdentifiers(int $userIdentifier): array
    {
        try {
            $rawGroupIds = db_connect()
                ->table('t_user_groups')
                ->select('group_id')
                ->where('user_id', $userIdentifier)
                ->get()
                ->getResultArray();
        } catch (Throwable $exception) {
            log_message('error', 'Profile: load user groups failed: {msg}', ['msg' => $exception->getMessage()]);

            return [];
        }

        return array_values(array_map(
            static fn (array $groupRow): int => (int) $groupRow['group_id'],
            $rawGroupIds,
        ));
    }

    /**
     * Kelompok yang ditugaskan ke pengguna. Super Administrator hanya muncul
     * bila subject login memiliki wildcard permission (*).
     *
     * @param list<int> $assignedGroupIds
     * @return list<array<string, mixed>>
     */
    private function assignedGroups(array $assignedGroupIds): array
    {
        if ($assignedGroupIds === []) {
            return [];
        }

        try {
            $authenticatedSubject = \App\Filters\FMSRequestContext::authenticatedSubject($this->request);
            $isSuperAdministrator = is_array($authenticatedSubject)
                && $this->authorizationService->isSuperAdministrator($authenticatedSubject);
            $allGroups = (new FMSDatabasePrivilegesManagementRepository())
                ->paginateGroups(1, self::SWITCHABLE_GROUP_LIMIT, [
                    'exclude_super_administrator' => ! $isSuperAdministrator,
                ])['items'] ?? [];
        } catch (Throwable $exception) {
            log_message('error', 'Profile: load all groups failed: {msg}', ['msg' => $exception->getMessage()]);

            return [];
        }

        return array_values(array_filter(
            $allGroups,
            static fn (array $groupRow): bool => in_array((int) ($groupRow['id'] ?? 0), $assignedGroupIds, true),
        ));
    }

    /* ── Helper: fresh presigned URL avatar ─────────────────── */

    private function resolveAvatarUrl(string $objectKey): string
    {
        if ($objectKey === '') {
            return '';
        }

        try {
            $uploadConfiguration = config(FMSUploads::class);
            $objectStorage = FMSObjectStorageFactory::create($uploadConfiguration);
            $privateUploadService = new FMSPrivateUploadService(
                $objectStorage,
                $uploadConfiguration->presignedUrlTtlSeconds,
                $uploadConfiguration->presignedUrlMaximumTtlSeconds,
            );

            return $privateUploadService->signedReadUrl($objectKey, $uploadConfiguration->presignedUrlTtlSeconds);
        } catch (Throwable $exception) {
            log_message('error', 'Profile resolveAvatarUrl: {msg}', ['msg' => $exception->getMessage()]);

            return '';
        }
    }
}
