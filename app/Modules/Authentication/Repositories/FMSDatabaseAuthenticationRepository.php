<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository;
use App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService;

use App\Modules\Authentication\Contracts\FMSAuthenticationRepositoryInterface;
use App\Modules\Authentication\Models\FMSAuthAttemptModel;
use App\Modules\Authentication\Models\FMSUserModel;

final class FMSDatabaseAuthenticationRepository implements FMSAuthenticationRepositoryInterface
{
    public function __construct(
        private readonly FMSUserModel $userModel = new FMSUserModel(),
        private readonly FMSAuthAttemptModel $attemptModel = new FMSAuthAttemptModel(),
    ) {
    }

    public function findUserByNormalizedIdentifier(string $normalizedIdentifier): ?array
    {
        $userRow = $this->userModel
            ->where('username_normalized', $normalizedIdentifier)
            ->first();

        if ($userRow === null) {
            $userRow = $this->userModel
                ->where('email_normalized', $normalizedIdentifier)
                ->first();
        }

        return is_array($userRow) ? $userRow : null;
    }

    public function updateUser(int $userIdentifier, array $attributes): bool
    {
        return (bool) $this->userModel->update($userIdentifier, $attributes);
    }

    public function recordAttempt(string $identifierHash, string $ipHash, string $resultCode, string $occurredAt): void
    {
        $this->attemptModel->insert([
            'identifier_hash' => $identifierHash,
            'ip_hash'         => $ipHash,
            'result_code'     => $resultCode,
            'created_at'      => $occurredAt,
        ]);
    }

    public function countRecentFailures(string $identifierHash, string $ipHash, string $since): int
    {
        return (int) $this->attemptModel
            ->where('identifier_hash', $identifierHash)
            ->where('ip_hash', $ipHash)
            ->where('created_at >=', $since)
            ->whereIn('result_code', ['invalid_credentials', 'rate_limited'])
            ->countAllResults();
    }

    public function isSuperAdministrator(int $userIdentifier): bool
    {
        if ($userIdentifier <= 0) {
            return false;
        }

        try {
            $effectivePermissionCodes = (new FMSPrivilegesEffectivePermissionService(
                new FMSDatabasePrivilegesPermissionRepository(),
            ))->resolveEffectivePermissionCodesForUser($userIdentifier);
        } catch (\Throwable) {
            return false;
        }

        return in_array('*', $effectivePermissionCodes, true);
    }
}
