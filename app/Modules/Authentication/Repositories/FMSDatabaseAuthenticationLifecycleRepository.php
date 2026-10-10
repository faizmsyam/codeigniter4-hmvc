<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Authentication\Contracts\FMSAuthenticationLifecycleRepositoryInterface;
use App\Modules\Authentication\Models\FMSEmailVerificationTokenModel;
use App\Modules\Authentication\Models\FMSUserModel;
use App\Modules\Settings\Models\FMSAuthSettingModel;

final class FMSDatabaseAuthenticationLifecycleRepository implements FMSAuthenticationLifecycleRepositoryInterface
{
    public function __construct(
        private readonly FMSUserModel $userModel = new FMSUserModel(),
        private readonly FMSEmailVerificationTokenModel $tokenModel = new FMSEmailVerificationTokenModel(),
        private readonly FMSAuthSettingModel $settingModel = new FMSAuthSettingModel(),
    ) {
    }

    public function authenticationSettings(): array
    {
        return $this->settingModel->currentSettings();
    }

    public function findUserByIdentity(string $normalizedUsername, string $normalizedEmail): ?array
    {
        $row = $this->userModel
            ->groupStart()
            ->where('username_normalized', $normalizedUsername)
            ->orWhere('email_normalized', $normalizedEmail)
            ->groupEnd()
            ->first();

        return is_array($row) ? $row : null;
    }

    public function findUserByNormalizedIdentifier(string $normalizedIdentifier): ?array
    {
        $row = $this->userModel
            ->groupStart()
            ->where('username_normalized', $normalizedIdentifier)
            ->orWhere('email_normalized', $normalizedIdentifier)
            ->groupEnd()
            ->first();

        return is_array($row) ? $row : null;
    }

    public function createUser(array $attributes): int
    {
        return (int) $this->userModel->insert($attributes, true);
    }

    public function findVerificationTokenForUpdate(string $selector): ?array
    {
        $row = db_connect()->table('t_email_verification_tokens')
            ->where('selector', $selector)
            ->get(1)
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function findUserById(int $userIdentifier): ?array
    {
        $row = $this->userModel->find($userIdentifier);

        return is_array($row) ? $row : null;
    }

    public function createVerificationToken(array $attributes): int
    {
        return (int) $this->tokenModel->insert($attributes, true);
    }

    public function updateVerificationToken(int $tokenIdentifier, array $attributes): bool
    {
        return (bool) $this->tokenModel->update($tokenIdentifier, $attributes);
    }

    public function revokeUnusedVerificationTokens(int $userIdentifier, string $revokedAt): void
    {
        db_connect()->table('t_email_verification_tokens')
            ->where('user_id', $userIdentifier)
            ->where('purpose', 'verify')
            ->where('used_at', null)
            ->where('revoked_at', null)
            ->update(['revoked_at' => $revokedAt]);
    }

    public function latestVerificationTokenCreatedAt(int $userIdentifier): ?string
    {
        $row = $this->tokenModel
            ->select('created_at')
            ->where('user_id', $userIdentifier)
            ->where('purpose', 'verify')
            ->orderBy('id', 'DESC')
            ->first();

        return is_array($row) && ! empty($row['created_at']) ? (string) $row['created_at'] : null;
    }

    public function updateUser(int $userIdentifier, array $attributes): bool
    {
        return (bool) $this->userModel->update($userIdentifier, $attributes);
    }

    public function beginTransaction(): void
    {
        db_connect()->transBegin();
    }

    public function commitTransaction(): void
    {
        db_connect()->transCommit();
    }

    public function rollbackTransaction(): void
    {
        db_connect()->transRollback();
    }
}
