<?php

namespace App\Modules\Users\Controllers\Api;

use App\Core\FMSApiController;
use App\Filters\FMSRequestContext;
use App\Modules\Authentication\Repositories\FMSDatabaseAuthenticationLifecycleRepository;
use App\Modules\Authentication\Services\FMSAuthenticationLifecycleService;
use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesManagementRepository;
use App\Modules\Settings\Models\FMSAuthSettingModel;
use App\Modules\Settings\Services\FMSEmailService;
use App\Modules\Users\Models\FMSUserModel;
use App\Modules\Users\Repositories\FMSDatabaseUserRepository;
use App\Modules\Users\Services\FMSUserAuthorizationService;
use App\Modules\Users\Services\FMSUserService;
use App\Modules\Users\Validation\FMSUserValidation;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

class FMSUsersApiController extends FMSApiController
{
    private FMSUserService $userService;
    private FMSUserAuthorizationService $userAuthorizationService;

    public function __construct(
        ?FMSUserService $userService = null,
        ?FMSUserAuthorizationService $authorizationService = null,
    ) {
        parent::__construct();
        $this->userService = $userService ?? new FMSUserService(new FMSDatabaseUserRepository());
        $this->userAuthorizationService = $authorizationService ?? new FMSUserAuthorizationService();
    }

    public function index(): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }

        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.read')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $requestedStatus = trim((string) $this->request->getGet('status'));
        $listingFilters = [
            'page'            => $this->request->getGet('page'),
            'per_page'        => $this->request->getGet('per_page') ?? $this->request->getGet('perPage'),
            'search'          => $this->request->getGet('search'),
            'status'          => $requestedStatus === 'deleted' ? '' : $requestedStatus,
            'include_deleted' => $requestedStatus === 'deleted',
            'deleted_only'    => $requestedStatus === 'deleted',
            'exclude_programmer_super_admin' => ! $this->userAuthorizationService->isSuperAdministrator($authenticatedSubject),
        ];

        try {
            $userListing = $this->userService->list($listingFilters);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondValidationError(['filter' => [$invalidArgumentException->getMessage()]]);
        }

        $perPage = max(1, (int) ($listingFilters['per_page'] ?? 10));
        $total   = (int) ($userListing['total'] ?? 0);
        $page    = max(1, (int) ($listingFilters['page'] ?? 1));

        return $this->respondSuccess(200, 'Daftar user berhasil dimuat.', [
            'items'      => array_map(fn (array $user): array => $this->presentUserRow($user), $userListing['users'] ?? []),
            'summary'    => $this->summaryCounters(),
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            ],
        ], 200);
    }

    public function show(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.read', $userUuid, true)) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $userDetail = $this->userService->detail($userUuid, false);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        if (! is_array($userDetail)) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Detail user berhasil dimuat.', [
            'user' => $this->presentUserRow($userDetail),
        ], 200);
    }

    public function create(): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.create')) {
            \App\Libraries\FMSAuditLogger::record(
                event: 'access.denied',
                module: 'users',
                actorId: $this->actorIdentifierFromSubject($authenticatedSubject),
                entityType: 'menu',
                entityId: 'users',
                description: 'Akses ditolak (403) saat membuat pengguna baru tanpa permission users.create.',
                httpMethod: 'POST',
                statusCode: 403,
            );

            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $actorIdentifier = $this->actorIdentifierFromSubject($authenticatedSubject);
        $requestPayload  = $this->requestPayload();

        try {
            $createdUserIdentifier = $this->userService->create($requestPayload, $actorIdentifier);
        } catch (InvalidArgumentException $invalidArgumentException) {
            if (str_contains($invalidArgumentException->getMessage(), 'already in use')
                || str_contains($invalidArgumentException->getMessage(), 'already used')
            ) {
                return $this->respondError(409, 'Username atau email sudah digunakan.', null, 409);
            }

            $validationErrors = FMSUserValidation::validateCreatePayload($requestPayload);
            if ($validationErrors === []) {
                $validationErrors = ['payload' => [$invalidArgumentException->getMessage()]];
            }

            return $this->respondValidationError($validationErrors);
        }

        $verificationEmailSent = false;
        try {
            $registrationSource = (string) ($requestPayload['registration_source'] ?? 'admin');
            $authSettings = (new FMSAuthSettingModel())->currentSettings();
            $verificationRequired = $registrationSource === 'public'
                ? (bool) ($authSettings['public_email_verification_required'] ?? true)
                : (bool) ($authSettings['admin_created_email_verification_required'] ?? false);

            if ($verificationRequired) {
                $verificationResult = (new FMSAuthenticationLifecycleService(new FMSDatabaseAuthenticationLifecycleRepository()))
                    ->issueVerificationForUser($createdUserIdentifier, date('Y-m-d H:i:s'));
                $verificationToken = $verificationResult['verification_token'] ?? null;
                $verificationUser = $verificationResult['user'] ?? null;

                if (is_array($verificationToken) && is_array($verificationUser)) {
                    $verificationUrl = site_url('fms-auth/verify-email') . '?' . http_build_query([
                        'selector' => $verificationToken['selector'],
                        'validator' => $verificationToken['validator'],
                    ]);
                    $verificationEmailSent = (new FMSEmailService())->sendVerification(
                        (string) $verificationUser['email'],
                        (string) ($verificationUser['full_name'] ?: $verificationUser['username']),
                        $verificationUrl,
                        (string) $verificationToken['expires_at'],
                    );
                }
            }
        } catch (\Throwable $verificationException) {
            log_message('error', 'Email verification for created user {id} failed: {message}', [
                'id' => $createdUserIdentifier,
                'message' => $verificationException->getMessage(),
            ]);
        }

        \App\Libraries\FMSAuditLogger::record(
            event: 'users.created',
            module: 'users',
            actorId: $actorIdentifier,
            entityType: 'user',
            entityId: (string) $createdUserIdentifier,
            description: 'Pengguna baru dibuat: ' . ($requestPayload['username'] ?? $requestPayload['email'] ?? $createdUserIdentifier),
            after: $requestPayload,
            httpMethod: 'POST',
            statusCode: 201,
        );

        return $this->respondSuccess(200,
            'User berhasil dibuat.',
            [
                'id' => $createdUserIdentifier,
                'verification_email_sent' => $verificationEmailSent,
            ],
            201,
        );
    }

    public function update(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.update')) {
            \App\Libraries\FMSAuditLogger::record(
                event: 'access.denied',
                module: 'users',
                actorId: $this->actorIdentifierFromSubject($authenticatedSubject),
                entityType: 'user',
                entityId: $userUuid,
                description: 'Akses ditolak (403) saat mengubah pengguna ' . $userUuid . ' tanpa permission users.update.',
                httpMethod: 'POST',
                statusCode: 403,
            );

            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $actorIdentifier = $this->actorIdentifierFromSubject($authenticatedSubject);
        $requestPayload = $this->requestPayload();

        try {
            $updated = $this->userService->updateByUuid($userUuid, $requestPayload, $actorIdentifier);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $message = $invalidArgumentException->getMessage();
            if ($message === 'User was not found.' || $message === 'User UUID is invalid.') {
                return $this->respondError(404, 'User tidak ditemukan.', null, 404);
            }

            if (str_contains($message, 'already in use') || str_contains($message, 'already used')) {
                return $this->respondError(409, 'Username atau email sudah digunakan.', null, 409);
            }

            $validationErrors = FMSUserValidation::validateUpdate($requestPayload);
            if ($validationErrors === []) {
                /* Map pesan error ke field yang sesuai agar muncul di form. */
                if (str_contains($message, 'email')) {
                    $validationErrors = ['email' => [$message]];
                } elseif (str_contains($message, 'username')) {
                    $validationErrors = ['username' => [$message]];
                } elseif (str_contains($message, 'password')) {
                    $validationErrors = ['password' => [$message]];
                } elseif (str_contains($message, 'status')) {
                    $validationErrors = ['status' => [$message]];
                } else {
                    $validationErrors = ['payload' => [$message]];
                }
            }

            return $this->respondValidationError($validationErrors);
        }

        if (! $updated) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        \App\Libraries\FMSAuditLogger::record(
            event: 'users.updated',
            module: 'users',
            actorId: $actorIdentifier,
            entityType: 'user',
            entityId: $userUuid,
            description: 'Pengguna diperbarui: ' . ($requestPayload['username'] ?? $requestPayload['email'] ?? $userUuid),
            after: $requestPayload,
            httpMethod: 'POST',
            statusCode: 200,
        );

        return $this->respondSuccess(200, 'User berhasil diperbarui.', ['uuid' => $userUuid], 200);
    }

    public function destroy(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.delete')) {
            \App\Libraries\FMSAuditLogger::record(
                event: 'access.denied',
                module: 'users',
                actorId: $this->actorIdentifierFromSubject($authenticatedSubject),
                entityType: 'user',
                entityId: $userUuid,
                description: 'Akses ditolak (403) saat menghapus pengguna ' . $userUuid . ' tanpa permission users.delete.',
                httpMethod: 'DELETE',
                statusCode: 403,
            );

            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $deleted = $this->userService->deleteByUuid($userUuid, $this->actorIdentifierFromSubject($authenticatedSubject));
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        if (! $deleted) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        \App\Libraries\FMSAuditLogger::record(
            event: 'users.deleted',
            module: 'users',
            actorId: $this->actorIdentifierFromSubject($authenticatedSubject),
            entityType: 'user',
            entityId: $userUuid,
            description: 'Pengguna dihapus: ' . $userUuid,
            httpMethod: 'DELETE',
            statusCode: 200,
        );

        return $this->respondSuccess(200, 'User berhasil dihapus.', ['uuid' => $userUuid], 200);
    }

    public function restore(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.restore')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $restored = $this->userService->restoreByUuid($userUuid, $this->actorIdentifierFromSubject($authenticatedSubject));
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        if (! $restored) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        \App\Libraries\FMSAuditLogger::record(
            event: 'users.restored',
            module: 'users',
            actorId: $this->actorIdentifierFromSubject($authenticatedSubject),
            entityType: 'user',
            entityId: $userUuid,
            description: 'Pengguna dipulihkan: ' . $userUuid,
            httpMethod: 'POST',
            statusCode: 200,
        );

        return $this->respondSuccess(200, 'User berhasil dipulihkan.', ['uuid' => $userUuid], 200);
    }

    public function resetPassword(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.reset_password')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $requestPayload = $this->requestPayload();
        $newPassword    = (string) ($requestPayload['password'] ?? $requestPayload['new_password'] ?? '');

        try {
            $reset = $this->userService->resetPasswordByUuid(
                $userUuid,
                $newPassword,
                $this->actorIdentifierFromSubject($authenticatedSubject),
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            if ($invalidArgumentException->getMessage() === 'User was not found.') {
                return $this->respondError(404, 'User tidak ditemukan.', null, 404);
            }

            return $this->respondValidationError(['password' => [$invalidArgumentException->getMessage()]]);
        }

        if (! $reset) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Password user berhasil direset.', ['uuid' => $userUuid], 200);
    }

    public function verifyEmail(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.verify_email')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $verified = $this->userService->verifyEmailByUuid($userUuid, $this->actorIdentifierFromSubject($authenticatedSubject));
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        if (! $verified) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Email user berhasil diverifikasi.', ['uuid' => $userUuid], 200);
    }

    public function unverifyEmail(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.unverify_email')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $unverified = $this->userService->unverifyEmailByUuid($userUuid, $this->actorIdentifierFromSubject($authenticatedSubject));
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        if (! $unverified) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Verifikasi email user berhasil dibatalkan.', ['uuid' => $userUuid], 200);
    }

    public function unlock(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.unlock')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $unlocked = $this->userService->unlockByUuid($userUuid, $this->actorIdentifierFromSubject($authenticatedSubject));
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        if (! $unlocked) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'User berhasil dibuka kuncinya.', ['uuid' => $userUuid], 200);
    }

    public function changeStatus(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.change_status')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $requestPayload = $this->requestPayload();
        $status         = (string) ($requestPayload['status'] ?? '');

        try {
            $changed = $this->userService->changeStatusByUuid(
                $userUuid,
                $status,
                $this->actorIdentifierFromSubject($authenticatedSubject),
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            if ($invalidArgumentException->getMessage() === 'User was not found.') {
                return $this->respondError(404, 'User tidak ditemukan.', null, 404);
            }

            return $this->respondValidationError(['status' => [$invalidArgumentException->getMessage()]]);
        }

        if (! $changed) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Status user berhasil diubah.', ['uuid' => $userUuid, 'status' => $status], 200);
    }

    public function sessions(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.read_sessions', $userUuid, true)) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $userSessions = $this->userService->sessionsByUuid($userUuid);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Session user berhasil dimuat.', [
            'sessions' => array_values($userSessions),
        ], 200);
    }

    public function revokeSessions(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.revoke_sessions', $userUuid, true)) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $requestPayload = $this->requestPayload();
        $sessionUuid    = isset($requestPayload['session_uuid']) ? (string) $requestPayload['session_uuid'] : null;

        try {
            $revokedCount = $this->userService->revokeSessionsByUuid(
                $userUuid,
                $this->actorIdentifierFromSubject($authenticatedSubject),
                $sessionUuid !== null && $sessionUuid !== '' ? $sessionUuid : null,
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200,
            'Session user berhasil dicabut.',
            ['uuid' => $userUuid, 'revoked' => $revokedCount],
            200,
        );
    }

    public function groups(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.read_groups')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        try {
            $groupIdentifiers = $this->userService->groupsByUuid($userUuid);
            $isSuperAdministrator = $this->userAuthorizationService->isSuperAdministrator($authenticatedSubject);
            $groupItems = (new FMSDatabasePrivilegesManagementRepository())->paginateGroups(1, 100, [
                'exclude_super_administrator' => ! $isSuperAdministrator,
            ])['items'] ?? [];
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondError(404, 'User tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Grup user berhasil dimuat.', [
            'assigned_group_ids' => array_values(array_map('intval', $groupIdentifiers)),
            'groups'             => $groupItems,
        ], 200);
    }

    public function replaceGroups(string $userUuid = ''): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }
        if (! $this->userAuthorizationService->allows($authenticatedSubject, 'users.assign_groups')) {
            return $this->respondError(403, 'Akses ditolak.', null, 403);
        }

        $requestPayload     = $this->requestPayload();
        $groupIdentifiers   = $requestPayload['group_ids'] ?? $requestPayload['groups'] ?? [];
        $groupIdentifierSet = is_array($groupIdentifiers) ? array_values($groupIdentifiers) : [];

        if (! $this->userAuthorizationService->isSuperAdministrator($authenticatedSubject)) {
            $groupIdentifierSet = array_values(array_filter(
                $groupIdentifierSet,
                static fn ($groupIdentifier): bool => (int) $groupIdentifier !== 1,
            ));
        }

        try {
            $this->userService->replaceGroupsByUuid(
                $userUuid,
                $groupIdentifierSet,
                $this->actorIdentifierFromSubject($authenticatedSubject),
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            if ($invalidArgumentException->getMessage() === 'User was not found.') {
                return $this->respondError(404, 'User tidak ditemukan.', null, 404);
            }

            return $this->respondValidationError(['group_ids' => [$invalidArgumentException->getMessage()]]);
        }

        return $this->respondSuccess(200, 'Grup user berhasil diperbarui.', ['uuid' => $userUuid], 200);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function presentUserRow(array $user): array
    {
        return [
            'id'              => (int) ($user['id'] ?? 0),
            'uuid'            => (string) ($user['uuid'] ?? ''),
            'username'        => (string) ($user['username'] ?? ''),
            'email'           => (string) ($user['email'] ?? ''),
            'full_name'       => (string) ($user['full_name'] ?? ''),
            'status'          => trim((string) ($user['deleted_at'] ?? '')) !== ''
                ? 'deleted'
                : (string) ($user['status'] ?? 'active'),
            'is_email_verified' => trim((string) ($user['email_verified_at'] ?? '')) !== '',
            'locked_until'    => (string) ($user['locked_until'] ?? ''),
            'avatar'          => (string) ($user['avatar'] ?? ''),
            'created_at'      => (string) ($user['created_at'] ?? ''),
        ];
    }

    /**
     * @return array{total: int, active: int, inactive: int, banned: int}
     */
    private function summaryCounters(): array
    {
        $userModel = new FMSUserModel();

        return [
            'total'    => (int) $userModel->builder()->countAllResults(),
            'active'   => (int) $userModel->builder()->where('status', 'active')->countAllResults(),
            'inactive' => (int) $userModel->builder()->where('status', 'inactive')->countAllResults(),
            'banned'   => (int) $userModel->builder()->where('status', 'banned')->countAllResults(),
        ];
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    private function actorIdentifierFromSubject(array $authenticatedSubject): int
    {
        $actorIdentifier = $authenticatedSubject['user_id'] ?? null;
        if (is_int($actorIdentifier) || (is_string($actorIdentifier) && ctype_digit($actorIdentifier))) {
            $actorIdentifier = (int) $actorIdentifier;
            if ($actorIdentifier > 0) {
                return $actorIdentifier;
            }
        }

        $subjectUuid = trim((string) ($authenticatedSubject['sub'] ?? ''));
        if (FMSUserValidation::isValidUuid($subjectUuid)) {
            $subjectUser = $this->userService->detail($subjectUuid, false);
            if (is_array($subjectUser) && isset($subjectUser['id'])) {
                return (int) $subjectUser['id'];
            }
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(): array
    {
        $requestPayload = $this->request->getJSON(true);
        if (is_array($requestPayload)) {
            return $requestPayload;
        }

        $postPayload = $this->request->getPost();
        if (is_array($postPayload)) {
            return $postPayload;
        }

        return [];
    }
}
