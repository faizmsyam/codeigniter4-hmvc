<?php

namespace App\Filters;

interface FMSAuthenticationRateLimiterInterface
{
    /**
     * @return array{limited: bool, remaining_attempts: int, retry_after_seconds: int}
     */
    public function recordAttempt(string $rateLimitIdentifier, string $rateLimitScope): array;
}
