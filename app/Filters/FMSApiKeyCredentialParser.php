<?php

namespace App\Filters;

/**
 * Parses an API key credential of the form "<key_id>.<key_secret>".
 *
 * Every rejection path returns a distinct reason code so callers can log the
 * failure class without ever logging the credential itself.
 */
final class FMSApiKeyCredentialParser
{
    public const REASON_MISSING           = 'API_KEY_CREDENTIAL_MISSING';
    public const REASON_DUPLICATE         = 'API_KEY_CREDENTIAL_DUPLICATE';
    public const REASON_OVERSIZED         = 'API_KEY_CREDENTIAL_OVERSIZED';
    public const REASON_MALFORMED         = 'API_KEY_CREDENTIAL_MALFORMED';
    public const REASON_HEADER_CONFLICT   = 'API_KEY_HEADER_CONFLICT';

    private const AUTHORIZATION_SCHEME = 'ApiKey';

    public function __construct(
        private readonly int $maximumCredentialLength = 1024,
        private readonly int $minimumSecretLength = 16,
        private readonly int $maximumSecretLength = 256,
    ) {
    }

    /**
     * @param list<string> $apiKeyHeaderValues          values from the X-API-Key header
     * @param list<string> $authorizationHeaderValues   values from the Authorization header
     *
     * @return array{valid: bool, reason: string|null, key_id: string|null, key_secret: string|null}
     */
    public function parse(array $apiKeyHeaderValues, array $authorizationHeaderValues = []): array
    {
        $authorizationCredentials = $this->extractAuthorizationCredentials($authorizationHeaderValues);

        if ($apiKeyHeaderValues !== [] && $authorizationCredentials !== []) {
            return $this->failure(self::REASON_HEADER_CONFLICT);
        }

        $candidateCredentials = $apiKeyHeaderValues !== [] ? $apiKeyHeaderValues : $authorizationCredentials;

        $individualCredentials = [];
        foreach ($candidateCredentials as $candidateCredential) {
            foreach (explode(',', (string) $candidateCredential) as $individualCredential) {
                $individualCredentials[] = trim($individualCredential);
            }
        }

        if ($individualCredentials === []) {
            return $this->failure(self::REASON_MISSING);
        }

        if (count($individualCredentials) !== 1) {
            return $this->failure(self::REASON_DUPLICATE);
        }

        $credential = $individualCredentials[0];

        if ($credential === '') {
            return $this->failure(self::REASON_MISSING);
        }

        if (strlen($credential) > $this->maximumCredentialLength) {
            return $this->failure(self::REASON_OVERSIZED);
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $credential) === 1) {
            return $this->failure(self::REASON_MALFORMED);
        }

        if (substr_count($credential, '.') !== 1) {
            return $this->failure(self::REASON_MALFORMED);
        }

        [$keyIdentifier, $keySecret] = explode('.', $credential, 2);

        if (preg_match('/\A[A-Za-z0-9_-]{8,64}\z/', $keyIdentifier) !== 1) {
            return $this->failure(self::REASON_MALFORMED);
        }

        if (
            strlen($keySecret) < $this->minimumSecretLength
            || strlen($keySecret) > $this->maximumSecretLength
            || preg_match('/\A[A-Za-z0-9_-]+\z/', $keySecret) !== 1
        ) {
            return $this->failure(self::REASON_MALFORMED);
        }

        return [
            'valid'      => true,
            'reason'     => null,
            'key_id'     => $keyIdentifier,
            'key_secret' => $keySecret,
        ];
    }

    /**
     * Returns only the Authorization header values that use the ApiKey scheme,
     * so a credential from another scheme never collides with this filter.
     *
     * @param list<string> $authorizationHeaderValues
     *
     * @return list<string>
     */
    private function extractAuthorizationCredentials(array $authorizationHeaderValues): array
    {
        $credentials = [];

        foreach ($authorizationHeaderValues as $authorizationHeaderValue) {
            foreach (explode(',', (string) $authorizationHeaderValue) as $individualValue) {
                $individualValue = trim($individualValue);

                if (stripos($individualValue, self::AUTHORIZATION_SCHEME . ' ') !== 0) {
                    continue;
                }

                $credentials[] = trim(substr($individualValue, strlen(self::AUTHORIZATION_SCHEME)));
            }
        }

        return $credentials;
    }

    /**
     * @return array{valid: false, reason: string, key_id: null, key_secret: null}
     */
    private function failure(string $reason): array
    {
        return ['valid' => false, 'reason' => $reason, 'key_id' => null, 'key_secret' => null];
    }
}
