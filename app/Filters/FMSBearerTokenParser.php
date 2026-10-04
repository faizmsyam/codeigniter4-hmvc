<?php

namespace App\Filters;

final class FMSBearerTokenParser
{
    public const REASON_MISSING = 'AUTHORIZATION_HEADER_MISSING';
    public const REASON_DUPLICATE = 'AUTHORIZATION_HEADER_DUPLICATE';
    public const REASON_OVERSIZED = 'AUTHORIZATION_HEADER_OVERSIZED';
    public const REASON_MALFORMED = 'BEARER_TOKEN_MALFORMED';

    public function __construct(private readonly int $maximumHeaderLength = 8192)
    {
    }

    /**
     * @param list<string> $authorizationHeaderValues
     * @return array{valid: bool, reason: string|null, token: string|null}
     */
    public function parse(array $authorizationHeaderValues): array
    {
        if ($authorizationHeaderValues === []) {
            return $this->failure(self::REASON_MISSING);
        }

        $individualValues = [];
        foreach ($authorizationHeaderValues as $headerValue) {
            foreach (explode(',', $headerValue) as $individualValue) {
                $individualValues[] = trim($individualValue);
            }
        }

        if (count($individualValues) !== 1) {
            return $this->failure(self::REASON_DUPLICATE);
        }

        $authorizationValue = $individualValues[0];
        if (strlen($authorizationValue) > $this->maximumHeaderLength) {
            return $this->failure(self::REASON_OVERSIZED);
        }

        if (!preg_match('/\ABearer ([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)\z/iD', $authorizationValue, $matches)) {
            return $this->failure(self::REASON_MALFORMED);
        }

        return ['valid' => true, 'reason' => null, 'token' => $matches[1]];
    }

    /**
     * @return array{valid: false, reason: string, token: null}
     */
    private function failure(string $reason): array
    {
        return ['valid' => false, 'reason' => $reason, 'token' => null];
    }
}
