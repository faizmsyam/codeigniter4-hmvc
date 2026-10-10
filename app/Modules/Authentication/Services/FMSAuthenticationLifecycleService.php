<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Contracts\FMSAuthenticationLifecycleRepositoryInterface;
use InvalidArgumentException;
use Throwable;

/**
 * @phpstan-type RegistrationResult array{status: string, verification_token?: array{selector: string, validator: string, expires_at: string}}
 * @phpstan-type VerificationResult array{status: string}
 * @phpstan-type ResendResult array{status: string, verification_token?: array{selector: string, validator: string, expires_at: string}, user?: array<string, mixed>}
 */
final class FMSAuthenticationLifecycleService
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DISABLED = 'registration_disabled';
    public const STATUS_INVALID_TOKEN = 'invalid_token';
    public const STATUS_VERIFIED = 'verified';

    /** @var callable(int): string */
    private $randomBytesGenerator;

    /**
     * @param callable(int): string|null $randomBytesGenerator
     */
    public function __construct(
        private readonly FMSAuthenticationLifecycleRepositoryInterface $lifecycleRepository,
        ?callable $randomBytesGenerator = null,
    ) {
        $this->randomBytesGenerator = $randomBytesGenerator ?? static fn (int $byteLength): string => random_bytes($byteLength);
    }

    public function normalizeIdentifier(string $rawIdentifier): string
    {
        return mb_strtolower(trim($rawIdentifier));
    }

    /**
     * Register a public self-service account without revealing whether the
     * identity already exists to unauthenticated callers.
     *
     * @param array<string, mixed> $registrationPayload
     * @return RegistrationResult
     */
    public function register(array $registrationPayload, string $currentTimestamp): array
    {
        $settings = $this->lifecycleRepository->authenticationSettings();
        if ((bool) ($settings['public_registration_enabled'] ?? false) !== true) {
            return ['status' => self::STATUS_DISABLED];
        }

        $username = trim((string) ($registrationPayload['username'] ?? ''));
        $email = trim((string) ($registrationPayload['email'] ?? ''));
        $password = (string) ($registrationPayload['password'] ?? '');
        $fullName = trim((string) ($registrationPayload['full_name'] ?? ''));
        $validationResult = $this->validateRegistrationPayload($username, $email, $password, $fullName);
        if ($validationResult !== null) {
            throw new InvalidArgumentException($validationResult);
        }

        $normalizedUsername = $this->normalizeIdentifier($username);
        $normalizedEmail = $this->normalizeIdentifier($email);
        $existingUser = $this->lifecycleRepository->findUserByIdentity($normalizedUsername, $normalizedEmail);
        if ($existingUser !== null) {
            return ['status' => self::STATUS_ACCEPTED];
        }

        $verificationTokenTtlMinutes = max(1, (int) ($settings['verification_ttl_minutes'] ?? 1440));
        $verificationExpiresAt = date('Y-m-d H:i:s', strtotime($currentTimestamp) + ($verificationTokenTtlMinutes * 60));
        $verificationToken = $this->generateVerificationToken();
        $createdUserIdentifier = $this->lifecycleRepository->createUser([
            'uuid' => $this->uuidV4(),
            'username' => $username,
            'username_normalized' => $normalizedUsername,
            'email' => $email,
            'email_normalized' => $normalizedEmail,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'status' => 'pending',
            'registration_source' => 'public',
            'email_verified_at' => null,
            'created_at' => $currentTimestamp,
            'updated_at' => $currentTimestamp,
        ]);

        try {
            $this->lifecycleRepository->createVerificationToken([
                'user_id' => $createdUserIdentifier,
                'selector' => $verificationToken['selector'],
                'validator_hash' => hash('sha256', $verificationToken['validator']),
                'purpose' => 'verify',
                'expires_at' => $verificationExpiresAt,
                'used_at' => null,
                'revoked_at' => null,
                'created_at' => $currentTimestamp,
            ]);
        } catch (Throwable $tokenFailure) {
            throw $tokenFailure;
        }

        return [
            'status' => self::STATUS_ACCEPTED,
            'verification_token' => [
                'selector' => $verificationToken['selector'],
                'validator' => $verificationToken['validator'],
                'expires_at' => $verificationExpiresAt,
            ],
        ];
    }

    /**
     * @return VerificationResult
     */
    public function verifyEmail(string $selector, string $validator, string $currentTimestamp): array
    {
        $selector = trim($selector);
        if ($selector === '' || $validator === '' || strlen($selector) > 64) {
            return ['status' => self::STATUS_INVALID_TOKEN];
        }

        $this->lifecycleRepository->beginTransaction();
        try {
            $verificationToken = $this->lifecycleRepository->findVerificationTokenForUpdate($selector);
            if (! $this->isUsableVerificationToken($verificationToken, $validator, $currentTimestamp)) {
                $this->lifecycleRepository->rollbackTransaction();

                return ['status' => self::STATUS_INVALID_TOKEN];
            }

            $userRecord = $this->lifecycleRepository->findUserById((int) $verificationToken['user_id']);
            if (! is_array($userRecord)) {
                $this->lifecycleRepository->rollbackTransaction();

                return ['status' => self::STATUS_INVALID_TOKEN];
            }

            $this->lifecycleRepository->updateVerificationToken((int) $verificationToken['id'], ['used_at' => $currentTimestamp]);
            $this->lifecycleRepository->revokeUnusedVerificationTokens((int) $verificationToken['user_id'], $currentTimestamp);

            $userUpdate = ['email_verified_at' => $currentTimestamp];
            if ((string) ($userRecord['status'] ?? '') === 'pending') {
                $userUpdate['status'] = 'active';
            }
            $this->lifecycleRepository->updateUser((int) $verificationToken['user_id'], $userUpdate);
            $this->lifecycleRepository->commitTransaction();

            return ['status' => self::STATUS_VERIFIED];
        } catch (Throwable $verificationFailure) {
            $this->lifecycleRepository->rollbackTransaction();

            throw $verificationFailure;
        }
    }

    /**
     * Issue a verification link for a known user after an authenticated
     * administrative create flow. The caller remains responsible for sending
     * the returned link through the configured email service.
     *
     * @return ResendResult
     */
    public function issueVerificationForUser(int $userIdentifier, string $currentTimestamp): array
    {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        $userRecord = $this->lifecycleRepository->findUserById($userIdentifier);
        if (! is_array($userRecord) || ! empty($userRecord['email_verified_at'])) {
            return ['status' => self::STATUS_ACCEPTED];
        }

        $settings = $this->lifecycleRepository->authenticationSettings();
        $verificationTokenTtlMinutes = max(1, (int) ($settings['verification_ttl_minutes'] ?? 1440));
        $verificationExpiresAt = date('Y-m-d H:i:s', strtotime($currentTimestamp) + ($verificationTokenTtlMinutes * 60));
        $verificationToken = $this->generateVerificationToken();

        $this->lifecycleRepository->revokeUnusedVerificationTokens($userIdentifier, $currentTimestamp);
        $this->lifecycleRepository->createVerificationToken([
            'user_id' => $userIdentifier,
            'selector' => $verificationToken['selector'],
            'validator_hash' => hash('sha256', $verificationToken['validator']),
            'purpose' => 'verify',
            'expires_at' => $verificationExpiresAt,
            'used_at' => null,
            'revoked_at' => null,
            'created_at' => $currentTimestamp,
        ]);

        return [
            'status' => self::STATUS_ACCEPTED,
            'verification_token' => [
                'selector' => $verificationToken['selector'],
                'validator' => $verificationToken['validator'],
                'expires_at' => $verificationExpiresAt,
            ],
            'user' => $userRecord,
        ];
    }

    /**
     * @return ResendResult
     */
    public function resendVerification(string $rawIdentity, string $currentTimestamp, ?string $clientIpAddress = null): array
    {
        $normalizedIdentifier = $this->normalizeIdentifier($rawIdentity);
        if ($normalizedIdentifier === '') {
            return ['status' => self::STATUS_ACCEPTED];
        }

        $userRecord = $this->lifecycleRepository->findUserByNormalizedIdentifier($normalizedIdentifier);
        if (! is_array($userRecord) || ! empty($userRecord['email_verified_at'])) {
            return ['status' => self::STATUS_ACCEPTED];
        }

        $settings = $this->lifecycleRepository->authenticationSettings();
        $cooldownSeconds = max(0, (int) ($settings['resend_cooldown_seconds'] ?? 120));
        $latestCreatedAt = $this->lifecycleRepository->latestVerificationTokenCreatedAt((int) $userRecord['id']);
        if ($latestCreatedAt !== null && (strtotime($currentTimestamp) - strtotime($latestCreatedAt)) < $cooldownSeconds) {
            return ['status' => self::STATUS_ACCEPTED];
        }

        $verificationTokenTtlMinutes = max(1, (int) ($settings['verification_ttl_minutes'] ?? 1440));
        $verificationExpiresAt = date('Y-m-d H:i:s', strtotime($currentTimestamp) + ($verificationTokenTtlMinutes * 60));
        $verificationToken = $this->generateVerificationToken();

        $this->lifecycleRepository->revokeUnusedVerificationTokens((int) $userRecord['id'], $currentTimestamp);
        $this->lifecycleRepository->createVerificationToken([
            'user_id' => (int) $userRecord['id'],
            'selector' => $verificationToken['selector'],
            'validator_hash' => hash('sha256', $verificationToken['validator']),
            'purpose' => 'verify',
            'expires_at' => $verificationExpiresAt,
            'used_at' => null,
            'revoked_at' => null,
            'created_at' => $currentTimestamp,
        ]);

        return [
            'status' => self::STATUS_ACCEPTED,
            'verification_token' => [
                'selector' => $verificationToken['selector'],
                'validator' => $verificationToken['validator'],
                'expires_at' => $verificationExpiresAt,
            ],
            'user' => $userRecord,
        ];
    }

    /**
     * @param array<string, mixed>|null $verificationToken
     */
    private function isUsableVerificationToken(?array $verificationToken, string $validator, string $currentTimestamp): bool
    {
        if (! is_array($verificationToken)) {
            return false;
        }

        if (($verificationToken['purpose'] ?? 'verify') !== 'verify') {
            return false;
        }

        if (! empty($verificationToken['used_at']) || ! empty($verificationToken['revoked_at'])) {
            return false;
        }

        if ((string) ($verificationToken['expires_at'] ?? '') <= $currentTimestamp) {
            return false;
        }

        return hash_equals((string) ($verificationToken['validator_hash'] ?? ''), hash('sha256', $validator));
    }

    /**
     * @return array{selector: string, validator: string}
     */
    private function generateVerificationToken(): array
    {
        $randomBytesGenerator = $this->randomBytesGenerator;

        return [
            'selector' => $this->base64UrlEncode($randomBytesGenerator(16)),
            'validator' => $this->base64UrlEncode($randomBytesGenerator(32)),
        ];
    }

    private function base64UrlEncode(string $binaryValue): string
    {
        return rtrim(strtr(base64_encode($binaryValue), '+/', '-_'), '=');
    }

    private function validateRegistrationPayload(string $username, string $email, string $password, string $fullName): ?string
    {
        if ($username === '' || $email === '' || $password === '' || $fullName === '') {
            return 'Username, email, password, and full name are required.';
        }

        if (mb_strlen($username) < 3 || mb_strlen($username) > 50 || preg_match('/^[A-Za-z0-9._-]+$/', $username) !== 1) {
            return 'Username must be 3 to 50 characters and use letters, numbers, dots, underscores, or dashes.';
        }

        if (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Email address is not valid.';
        }

        if (mb_strlen($fullName) > 150) {
            return 'Full name must not exceed 150 characters.';
        }

        if (mb_strlen($password) < 12 || mb_strlen($password) > 128) {
            return 'Password must be between 12 and 128 characters.';
        }

        $characterClasses = 0;
        $characterClasses += preg_match('/[a-z]/', $password) === 1 ? 1 : 0;
        $characterClasses += preg_match('/[A-Z]/', $password) === 1 ? 1 : 0;
        $characterClasses += preg_match('/[0-9]/', $password) === 1 ? 1 : 0;
        $characterClasses += preg_match('/[^A-Za-z0-9]/', $password) === 1 ? 1 : 0;

        if ($characterClasses < 3) {
            return 'Password must mix at least three character classes.';
        }

        return null;
    }

    private function uuidV4(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0f) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3f) | 0x80);
        $hexadecimalValue = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimalValue, 0, 8),
            substr($hexadecimalValue, 8, 4),
            substr($hexadecimalValue, 12, 4),
            substr($hexadecimalValue, 16, 4),
            substr($hexadecimalValue, 20),
        );
    }
}
