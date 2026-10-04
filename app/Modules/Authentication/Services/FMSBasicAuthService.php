<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Contracts\FMSBasicAuthClientRepositoryInterface;

/**
 * @phpstan-type BasicVerificationResult array{status: string, record?: array<string, mixed>|null, needs_rehash?: bool}
 */
final class FMSBasicAuthService
{
    public const STATUS_AUTHENTICATED      = 'authenticated';
    public const STATUS_MALFORMED          = 'malformed';
    public const STATUS_UNKNOWN            = 'unknown';
    public const STATUS_INVALID_CREDENTIALS = 'invalid_credentials';
    public const STATUS_LOCKED             = 'locked';
    public const STATUS_DISABLED           = 'disabled';
    public const STATUS_EXPIRED            = 'expired';
    public const STATUS_SCOPE_DENIED       = 'scope_denied';
    public const STATUS_ENVIRONMENT_DENIED = 'environment_denied';

    private int $maximumFailures;
    private int $lockoutSeconds;

    public function __construct(
        private readonly FMSBasicAuthClientRepositoryInterface $clientRepository,
        int $maximumFailures = 5,
        int $lockoutSeconds = 900,
    ) {
        $this->maximumFailures = $maximumFailures;
        $this->lockoutSeconds  = $lockoutSeconds;
    }

    public function normalizeUsername(string $rawUsername): string
    {
        return strtolower(trim($rawUsername));
    }

    /**
     * @return array{username: string, password: string}|null
     */
    public function parseAuthorizationHeader(string $authorizationHeader): ?array
    {
        if (strlen($authorizationHeader) > 1024) {
            return null;
        }

        if (substr_count($authorizationHeader, 'Basic ') !== 1 || !str_starts_with($authorizationHeader, 'Basic ')) {
            return null;
        }

        $encodedCredentials = trim(substr($authorizationHeader, 6));
        if ($encodedCredentials === '' || preg_match('/[^A-Za-z0-9+\/=]/', $encodedCredentials) === 1) {
            return null;
        }

        $decodedCredentials = base64_decode($encodedCredentials, true);
        if ($decodedCredentials === false || strlen($decodedCredentials) > 512) {
            return null;
        }

        $separatorPosition = strpos($decodedCredentials, ':');
        if ($separatorPosition === false || $separatorPosition === 0) {
            return null;
        }

        $usernamePart = substr($decodedCredentials, 0, $separatorPosition);
        $passwordPart = substr($decodedCredentials, $separatorPosition + 1);

        if ($usernamePart === '' || $passwordPart === '') {
            return null;
        }

        return ['username' => $usernamePart, 'password' => $passwordPart];
    }

    /**
     * @param array<int, string> $requiredScopes
     * @return BasicVerificationResult
     */
    public function verifyCredentials(
        string $authorizationHeader,
        array $requiredScopes,
        string $environment,
        string $currentTimestamp,
    ): array {
        $parsedCredentials = $this->parseAuthorizationHeader($authorizationHeader);
        if ($parsedCredentials === null) {
            return ['status' => self::STATUS_MALFORMED, 'record' => null];
        }

        $normalizedUsername = $this->normalizeUsername($parsedCredentials['username']);
        $storedClient       = $this->clientRepository->findByNormalizedUsername($normalizedUsername);

        if ($storedClient === null) {
            return ['status' => self::STATUS_UNKNOWN, 'record' => null];
        }

        if (!empty($storedClient['revoked_at'])) {
            return ['status' => self::STATUS_DISABLED, 'record' => null];
        }

        $lockedUntilTimestamp = (string) ($storedClient['locked_until'] ?? '');
        if ($lockedUntilTimestamp !== '' && $lockedUntilTimestamp > $currentTimestamp) {
            return ['status' => self::STATUS_LOCKED, 'record' => null];
        }

        $expiresTimestamp = (string) ($storedClient['expires_at'] ?? '');
        if ($expiresTimestamp !== '' && $expiresTimestamp <= $currentTimestamp) {
            return ['status' => self::STATUS_EXPIRED, 'record' => null];
        }

        $clientEnvironment = (string) ($storedClient['environment'] ?? '');
        if ($clientEnvironment !== '' && $clientEnvironment !== $environment) {
            return ['status' => self::STATUS_ENVIRONMENT_DENIED, 'record' => null];
        }

        $passwordHash = (string) ($storedClient['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($parsedCredentials['password'], $passwordHash)) {
            $failedAttempts = (int) ($storedClient['failed_attempts'] ?? 0) + 1;
            $updatePayload  = ['failed_attempts' => $failedAttempts];

            if ($failedAttempts >= $this->maximumFailures) {
                $updatePayload['locked_until'] = date(
                    'Y-m-d H:i:s',
                    strtotime($currentTimestamp) + $this->lockoutSeconds,
                );
            }

            $this->clientRepository->update((int) $storedClient['id'], $updatePayload);

            return ['status' => self::STATUS_INVALID_CREDENTIALS, 'record' => null];
        }

        $grantedScopes = $this->decodeScopes((string) ($storedClient['scopes_json'] ?? '[]'));
        foreach ($requiredScopes as $requiredScope) {
            if (!in_array($requiredScope, $grantedScopes, true)) {
                return ['status' => self::STATUS_SCOPE_DENIED, 'record' => null];
            }
        }

        $this->clientRepository->update((int) $storedClient['id'], [
            'failed_attempts' => 0,
            'locked_until'    => null,
            'last_used_at'    => $currentTimestamp,
        ]);

        return [
            'status'       => self::STATUS_AUTHENTICATED,
            'record'       => $storedClient,
            'needs_rehash' => password_needs_rehash($passwordHash, PASSWORD_DEFAULT),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function decodeScopes(string $scopesJson): array
    {
        $decodedScopes = json_decode($scopesJson, true);

        return is_array($decodedScopes) ? array_values(array_filter($decodedScopes, 'is_string')) : [];
    }
}
