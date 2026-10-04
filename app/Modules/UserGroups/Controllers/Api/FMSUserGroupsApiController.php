<?php

namespace App\Modules\UserGroups\Controllers\Api;

use App\Core\FMSApiController;
use App\Modules\UserGroups\Services\FMSUserGroupService;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use Throwable;

/** API-first boundary for User Groups administration. */
final class FMSUserGroupsApiController extends FMSApiController
{
    private ?FMSUserGroupService $groupService = null;

    public function index(): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.read')) !== null) {
            return $failure;
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? ''));

        return $this->respondSuccess(200, 'Daftar user group berhasil dimuat.',
            $this->service()->listGroups($page, $perPage, $search, $status));
    }

    public function show(string $hash = ''): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.read')) !== null) {
            return $failure;
        }

        $group = $this->findGroup($hash, false);
        if ($group === null) {
            return $this->respondNotFound('User group tidak ditemukan.');
        }

        return $this->respondSuccess(200, 'Detail user group berhasil dimuat.', ['group' => $group]);
    }

    public function create(): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.create')) !== null) {
            return $failure;
        }

        try {
            $group = $this->service()->createGroup($this->payload(), $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondValidationError(['name' => [$exception->getMessage()]]);
        } catch (Throwable $exception) {
            log_message('error', 'User group create failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Gagal membuat user group.');
        }

        return $this->respondCreated('User group berhasil ditambahkan.', ['group' => $group]);
    }

    public function update(string $hash = ''): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.update')) !== null) {
            return $failure;
        }

        $id = $this->decodeIdentifier($hash);
        if ($id <= 0) {
            return $this->respondNotFound('User group tidak ditemukan.');
        }

        try {
            $group = $this->service()->updateGroup($id, $this->payload(), $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondValidationError(['name' => [$exception->getMessage()]]);
        } catch (Throwable $exception) {
            log_message('error', 'User group update failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Gagal mengubah user group.');
        }

        return $this->respondOk('User group berhasil diubah.', ['group' => $group]);
    }

    public function destroy(string $hash = ''): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.delete')) !== null) {
            return $failure;
        }

        $id = $this->decodeIdentifier($hash);
        if ($id <= 0) {
            return $this->respondNotFound('User group tidak ditemukan.');
        }

        try {
            $this->service()->deleteGroup($id, $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondUnprocessableEntity($exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'User group delete failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Gagal menghapus user group.');
        }

        return $this->respondOk('User group berhasil dihapus.');
    }

    public function restore(string $hash = ''): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.restore')) !== null) {
            return $failure;
        }

        $id = $this->decodeIdentifier($hash);
        if ($id <= 0) {
            return $this->respondNotFound('User group tidak ditemukan.');
        }

        try {
            $this->service()->restoreGroup($id, $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondUnprocessableEntity($exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'User group restore failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Gagal memulihkan user group.');
        }

        return $this->respondOk('User group berhasil dipulihkan.');
    }

    public function changeStatus(string $hash = ''): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.update')) !== null) {
            return $failure;
        }

        $id = $this->decodeIdentifier($hash);
        if ($id <= 0) {
            return $this->respondNotFound('User group tidak ditemukan.');
        }

        $payload = $this->payload();
        try {
            $group = $this->service()->changeStatus($id, (int) ($payload['is_active'] ?? 1), $this->authenticatedApiUserIdentifier());
        } catch (InvalidArgumentException $exception) {
            return $this->respondUnprocessableEntity($exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'User group status failed: {message}', ['message' => $exception->getMessage()]);
            return $this->respondServerError('Gagal mengubah status user group.');
        }

        return $this->respondOk('Status user group berhasil diubah.', ['group' => $group]);
    }

    public function members(string $hash = ''): ResponseInterface
    {
        if (($failure = $this->requireApiPermission('user_groups.read')) !== null) {
            return $failure;
        }

        $group = $this->findGroup($hash, false);
        if ($group === null) {
            return $this->respondNotFound('User group tidak ditemukan.');
        }

        return $this->respondOk('Daftar anggota user group berhasil dimuat.', [
            'group' => $group,
            'members' => $this->service()->listGroupMembers((int) $group['id']),
        ]);
    }

    private function service(): FMSUserGroupService
    {
        return $this->groupService ??= new FMSUserGroupService();
    }

    private function decodeIdentifier(string $hash): int
    {
        $decoded = fmsDecodeId($hash);
        return is_numeric($decoded) ? (int) $decoded : 0;
    }

    /** @return array<string, mixed>|null */
    private function findGroup(string $hash, bool $withDeleted): ?array
    {
        $id = $this->decodeIdentifier($hash);
        return $id > 0 ? $this->service()->findById($id, $withDeleted) : null;
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $postPayload = $this->request->getPost();
        if (is_array($postPayload) && $postPayload !== []) {
            return $postPayload;
        }

        try {
            $jsonPayload = $this->request->getJSON(true);
            return is_array($jsonPayload) ? $jsonPayload : [];
        } catch (Throwable) {
            return [];
        }
    }
}
