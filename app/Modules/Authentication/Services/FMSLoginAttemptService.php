<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Contracts\FMSAuthenticationRepositoryInterface;

/**
 * @phpstan-type LoginPolicy array{admin_created_email_verification_required: bool, public_email_verification_required: bool, login_rate_limit_enabled?: bool}
 * @phpstan-type LoginAttemptResult array{status: string, user?: array<string, mixed>|null, reason?: string|null}
 */
final class FMSLoginAttemptService
{
    public const STATUS_AUTHENTICATED       = 'authenticated';
    public const STATUS_INVALID_CREDENTIALS = 'invalid_credentials';
    public const STATUS_ACCOUNT_LOCKED      = 'account_locked';
    public const STATUS_ACCOUNT_DISABLED    = 'account_disabled';
    public const STATUS_EMAIL_UNVERIFIED    = 'email_unverified';
    public const STATUS_RATE_LIMITED        = 'rate_limited';

    private int $maximumFailures;
    private int $failureWindowSeconds;
    private int $lockoutSeconds;

    public function __construct(
        private readonly FMSAuthenticationRepositoryInterface $authenticationRepository,
        int $maximumFailures = 5,
        int $failureWindowSeconds = 900,
        int $lockoutSeconds = 900,
    ) {
        $this->maximumFailures      = $maximumFailures;
        $this->failureWindowSeconds = $failureWindowSeconds;
        $this->lockoutSeconds       = $lockoutSeconds;
    }

    public function normalizeIdentifier(string $rawIdentifier): string
    {
        return strtolower(trim($rawIdentifier));
    }

    public function hashIdentifier(string $normalizedIdentifier, string $clientIpAddress): string
    {
        return hash('sha256', $normalizedIdentifier . '|' . $clientIpAddress);
    }

    public function hashIpAddress(string $clientIpAddress): string
    {
        return hash('sha256', $clientIpAddress);
    }

    /**
     * @param LoginPolicy $loginPolicy
     * @return LoginAttemptResult
     */
    public function attemptLogin(
        string $rawIdentifier,
        string $rawPassword,
        string $clientIpAddress,
        array $loginPolicy,
        string $currentTimestamp,
    ): array {
        $normalizedIdentifier = $this->normalizeIdentifier($rawIdentifier);
        $identifierHash       = $this->hashIdentifier($normalizedIdentifier, $clientIpAddress);
        $ipAddressHash        = $this->hashIpAddress($clientIpAddress);

        $userRecord = $this->authenticationRepository->findUserByNormalizedIdentifier($normalizedIdentifier);
        $isSuperAdministrator = is_array($userRecord)
            && $this->authenticationRepository->isSuperAdministrator((int) ($userRecord['id'] ?? 0));
        $rateLimitEnabled = (bool) ($loginPolicy['login_rate_limit_enabled'] ?? true);

        // Super administrator selalu bebas; akun lain mengikuti toggle Auth Settings.
        if ($rateLimitEnabled && ! $isSuperAdministrator && $this->isRateLimited($identifierHash, $ipAddressHash, $currentTimestamp)) {
            $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_RATE_LIMITED, $currentTimestamp);

            return ['status' => self::STATUS_RATE_LIMITED, 'user' => null, 'reason' => null];
        }

        if ($userRecord === null) {
            $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_INVALID_CREDENTIALS, $currentTimestamp);

            return ['status' => self::STATUS_INVALID_CREDENTIALS, 'user' => null, 'reason' => null];
        }

        $accountStatus = (string) ($userRecord['status'] ?? '');
        if ($accountStatus !== 'active') {
            if ($isSuperAdministrator) {
                $this->authenticationRepository->updateUser((int) $userRecord['id'], [
                    'status' => 'active',
                    'locked_until' => null,
                    'failed_login_count' => 0,
                ]);
            } else {
                $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_ACCOUNT_DISABLED, $currentTimestamp);

                return ['status' => self::STATUS_ACCOUNT_DISABLED, 'user' => null, 'reason' => $accountStatus];
            }
        }

        $lockedUntilTimestamp = (string) ($userRecord['locked_until'] ?? '');
        if (! $isSuperAdministrator && $lockedUntilTimestamp !== '' && $lockedUntilTimestamp > $currentTimestamp) {
            $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_ACCOUNT_LOCKED, $currentTimestamp);

            return ['status' => self::STATUS_ACCOUNT_LOCKED, 'user' => null, 'reason' => $lockedUntilTimestamp];
        }

        $passwordHash = (string) ($userRecord['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($rawPassword, $passwordHash)) {
            if ($rateLimitEnabled && ! $isSuperAdministrator) {
                $failedLoginCount = (int) ($userRecord['failed_login_count'] ?? 0) + 1;
                $updateAttributes = ['failed_login_count' => $failedLoginCount];

                if ($failedLoginCount >= $this->maximumFailures) {
                    $updateAttributes['locked_until'] = date(
                        'Y-m-d H:i:s',
                        strtotime($currentTimestamp) + $this->lockoutSeconds,
                    );
                }

                $this->authenticationRepository->updateUser((int) $userRecord['id'], $updateAttributes);
            }
            $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_INVALID_CREDENTIALS, $currentTimestamp);

            return ['status' => self::STATUS_INVALID_CREDENTIALS, 'user' => null, 'reason' => null];
        }

        if ($this->requiresVerifiedEmail($userRecord, $loginPolicy) && empty($userRecord['email_verified_at'])) {
            $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_EMAIL_UNVERIFIED, $currentTimestamp);

            return ['status' => self::STATUS_EMAIL_UNVERIFIED, 'user' => null, 'reason' => null];
        }

        $this->authenticationRepository->updateUser((int) $userRecord['id'], [
            'failed_login_count' => 0,
            'locked_until'       => null,
            'last_login_at'      => $currentTimestamp,
            'last_login_ip_hash' => $ipAddressHash,
        ]);
        $this->authenticationRepository->recordAttempt($identifierHash, $ipAddressHash, self::STATUS_AUTHENTICATED, $currentTimestamp);

        return ['status' => self::STATUS_AUTHENTICATED, 'user' => $userRecord, 'reason' => null];
    }

    public function needsPasswordRehash(string $passwordHash): bool
    {
        return password_needs_rehash($passwordHash, PASSWORD_DEFAULT);
    }

    public function isRateLimited(string $identifierHash, string $ipAddressHash, string $currentTimestamp): bool
    {
        $windowStartTimestamp = date(
            'Y-m-d H:i:s',
            strtotime($currentTimestamp) - $this->failureWindowSeconds,
        );

        return $this->authenticationRepository->countRecentFailures($identifierHash, $ipAddressHash, $windowStartTimestamp) >= $this->maximumFailures;
    }

    /**
     * @param array<string, mixed> $userRecord
     * @param LoginPolicy $loginPolicy
     */
    private function requiresVerifiedEmail(array $userRecord, array $loginPolicy): bool
    {
        if ($this->authenticationRepository->isSuperAdministrator((int) ($userRecord['id'] ?? 0))) {
            return false;
        }

        $registrationSource = (string) ($userRecord['registration_source'] ?? 'admin');
        if ($registrationSource === 'public') {
            return (bool) ($loginPolicy['public_email_verification_required'] ?? true);
        }

        return (bool) ($loginPolicy['admin_created_email_verification_required'] ?? false);
    }
}
