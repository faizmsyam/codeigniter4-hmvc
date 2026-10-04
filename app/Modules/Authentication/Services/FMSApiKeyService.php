<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Contracts\FMSApiKeyRepositoryInterface;

/**
 * @phpstan-type GeneratedKey array{key_identifier: string, presented: string, secret_hash: string}
 * @phpstan-type VerificationResult array{status: string, record?: array<string, mixed>|null}
 */
final class FMSApiKeyService
{
    public const STATUS_VERIFIED         = 'verified';
    public const STATUS_MALFORMED        = 'malformed';
    public const STATUS_UNKNOWN          = 'unknown';
    public const STATUS_REVOKED          = 'revoked';
    public const STATUS_EXPIRED          = 'expired';
    public const STATUS_SCOPE_DENIED     = 'scope_denied';
    public const STATUS_ENVIRONMENT_DENIED = 'environment_denied';

    private const PRESENTATION_PATTERN = '/^fms_([A-Za-z0-9]{8,64})_([A-Za-z0-9_-]{43,128})$/';

    private string $serverPepper;

    /** @var callable|null */
    private $secretGenerator;

    /** @var callable|null */
    private $keyIdentifierGenerator;

    public function __construct(
        private readonly FMSApiKeyRepositoryInterface $apiKeyRepository,
        string $serverPepper = '',
        ?callable $secretGenerator = null,
        ?callable $keyIdentifierGenerator = null,
    ) {
        $this->serverPepper           = $serverPepper;
        $this->secretGenerator        = $secretGenerator;
        $this->keyIdentifierGenerator = $keyIdentifierGenerator;
    }

    /**
     * @return GeneratedKey
     */
    public function generateKey(): array
    {
        $secretGenerator        = $this->secretGenerator ?? static fn (): string => random_bytes(32);
        $keyIdentifierGenerator = $this->keyIdentifierGenerator ?? static fn (): string => bin2hex(random_bytes(8));

        $keyIdentifier  = substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $keyIdentifierGenerator()) ?? '', 0, 32);
        if ($keyIdentifier === '') {
            $keyIdentifier = bin2hex(random_bytes(8));
        }

        $secretBinary   = (string) $secretGenerator();
        $secretEncoded  = rtrim(strtr(base64_encode($secretBinary), '+/', '-_'), '=');
        $presentedKey   = 'fms_' . $keyIdentifier . '_' . $secretEncoded;

        return [
            'key_identifier' => $keyIdentifier,
            'presented'      => $presentedKey,
            'secret_hash'    => $this->hashSecret($secretEncoded),
        ];
    }

    public function hashSecret(string $secretEncoded): string
    {
        return hash_hmac('sha256', $secretEncoded, $this->serverPepper);
    }

    public function parsePresentedKey(string $presentedKey): ?array
    {
        if (strlen($presentedKey) > 256 || strlen($presentedKey) < 16) {
            return null;
        }

        if (substr_count($presentedKey, 'fms_') !== 1 || !str_starts_with($presentedKey, 'fms_')) {
            return null;
        }

        if (!preg_match(self::PRESENTATION_PATTERN, $presentedKey, $keyMatches)) {
            return null;
        }

        return ['key_identifier' => $keyMatches[1], 'secret' => $keyMatches[2]];
    }

    /**
     * @param array<int, string> $requiredScopes
     * @return VerificationResult
     */
    public function verifyKey(
        string $presentedKey,
        array $requiredScopes,
        string $environment,
        string $currentTimestamp,
    ): array {
        $parsedKey = $this->parsePresentedKey($presentedKey);
        if ($parsedKey === null) {
            return ['status' => self::STATUS_MALFORMED, 'record' => null];
        }

        $storedRecord = $this->apiKeyRepository->findByKeyIdentifier($parsedKey['key_identifier']);
        if ($storedRecord === null) {
            return ['status' => self::STATUS_UNKNOWN, 'record' => null];
        }

        if (!empty($storedRecord['revoked_at'])) {
            return ['status' => self::STATUS_REVOKED, 'record' => null];
        }

        $expiresTimestamp = (string) ($storedRecord['expires_at'] ?? '');
        if ($expiresTimestamp !== '' && $expiresTimestamp <= $currentTimestamp) {
            return ['status' => self::STATUS_EXPIRED, 'record' => null];
        }

        $recordEnvironment = (string) ($storedRecord['environment'] ?? '');
        if ($recordEnvironment !== '' && $recordEnvironment !== $environment) {
            return ['status' => self::STATUS_ENVIRONMENT_DENIED, 'record' => null];
        }

        if (!hash_equals((string) $storedRecord['secret_hash'], $this->hashSecret($parsedKey['secret']))) {
            return ['status' => self::STATUS_UNKNOWN, 'record' => null];
        }

        $grantedScopes = $this->decodeScopes((string) ($storedRecord['scopes_json'] ?? '[]'));
        foreach ($requiredScopes as $requiredScope) {
            if (!in_array($requiredScope, $grantedScopes, true)) {
                return ['status' => self::STATUS_SCOPE_DENIED, 'record' => null];
            }
        }

        return ['status' => self::STATUS_VERIFIED, 'record' => $storedRecord];
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
