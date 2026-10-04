<?php

namespace App\Filters;

/**
 * Validates HTTP Basic credentials according to RFC 7617.
 *
 * Rejects missing, duplicated, oversized, non-base64, colon-less, and
 * header-injection-shaped credentials before any password comparison happens.
 */
final class FMSBasicAuthenticationCredentialParser
{
    public const REASON_MISSING     = 'BASIC_CREDENTIAL_MISSING';
    public const REASON_DUPLICATE   = 'BASIC_CREDENTIAL_DUPLICATE';
    public const REASON_OVERSIZED   = 'BASIC_CREDENTIAL_OVERSIZED';
    public const REASON_MALFORMED   = 'BASIC_CREDENTIAL_MALFORMED';

    private const AUTHORIZATION_SCHEME = 'Basic';

    public function __construct(
        private readonly int $maximumCredentialLength = 4096,
        private readonly int $maximumUsernameLength = 100,
    ) {
    }

    /**
     * @param list<string> $authorizationHeaderValues
     *
     * @return array{valid: bool, reason: string|null, username: string|null, password: string|null}
     */
    public function parse(array $authorizationHeaderValues): array
    {
        $encodedCredentials = [];

        foreach ($authorizationHeaderValues as $authorizationHeaderValue) {
            foreach (explode(',', (string) $authorizationHeaderValue) as $individualValue) {
                $individualValue = trim($individualValue);

                if (stripos($individualValue, self::AUTHORIZATION_SCHEME . ' ') !== 0) {
                    continue;
                }

                $encodedCredentials[] = trim(substr($individualValue, strlen(self::AUTHORIZATION_SCHEME)));
            }
        }

        if ($encodedCredentials === []) {
            return $this->failure(self::REASON_MISSING);
        }

        if (count($encodedCredentials) !== 1) {
            return $this->failure(self::REASON_DUPLICATE);
        }

        $encodedCredential = $encodedCredentials[0];

        if ($encodedCredential === '') {
            return $this->failure(self::REASON_MISSING);
        }

        if (strlen($encodedCredential) > $this->maximumCredentialLength) {
            return $this->failure(self::REASON_OVERSIZED);
        }

        if (preg_match('/\A[A-Za-z0-9+\/]+={0,2}\z/', $encodedCredential) !== 1) {
            return $this->failure(self::REASON_MALFORMED);
        }

        $decodedCredential = base64_decode($encodedCredential, true);
        if ($decodedCredential === false || $decodedCredential === '') {
            return $this->failure(self::REASON_MALFORMED);
        }

        $separatorPosition = strpos($decodedCredential, ':');
        if ($separatorPosition === false) {
            return $this->failure(self::REASON_MALFORMED);
        }

        $username = substr($decodedCredential, 0, $separatorPosition);
        $password = substr($decodedCredential, $separatorPosition + 1);

        if ($username === '' || strlen($username) > $this->maximumUsernameLength) {
            return $this->failure(self::REASON_MALFORMED);
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $username . $password) === 1) {
            return $this->failure(self::REASON_MALFORMED);
        }

        return [
            'valid'    => true,
            'reason'   => null,
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @return array{valid: false, reason: string, username: null, password: null}
     */
    private function failure(string $reason): array
    {
        return ['valid' => false, 'reason' => $reason, 'username' => null, 'password' => null];
    }
}
