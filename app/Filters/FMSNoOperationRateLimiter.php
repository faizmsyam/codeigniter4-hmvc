<?php

namespace App\Filters;

/**
 * Rate limiter used when no persistence backend is configured.
 *
 * It never limits, which keeps authentication filters usable in isolation
 * while a real limiter is wired in at the route level.
 */
final class FMSNoOperationRateLimiter implements FMSAuthenticationRateLimiterInterface
{
    public function recordAttempt(string $rateLimitIdentifier, string $rateLimitScope): array
    {
        return [
            'limited'               => false,
            'remaining_attempts'    => PHP_INT_MAX,
            'retry_after_seconds'   => 0,
        ];
    }
}
