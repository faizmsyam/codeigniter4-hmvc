<?php

namespace App\Filters;

use CodeIgniter\Cache\CacheInterface;

/**
 * Fixed-window rate limiter backed by CodeIgniter's configured cache handler.
 */
final class FMSCacheRateLimiter implements FMSAuthenticationRateLimiterInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly FMSAuthenticationClockInterface $systemClock = new FMSSystemClock(),
        private readonly int $maximumAttempts = 5,
        private readonly int $windowSeconds = 60,
    ) {
    }

    public function recordAttempt(string $rateLimitIdentifier, string $rateLimitScope): array
    {
        $windowStart = intdiv($this->systemClock->currentTimestamp(), $this->windowSeconds) * $this->windowSeconds;
        $retryAfterSeconds = max(1, ($windowStart + $this->windowSeconds) - $this->systemClock->currentTimestamp());
        $cacheKey = 'fms_auth_rate_' . hash('sha256', $rateLimitScope . "\0" . $rateLimitIdentifier . "\0" . $windowStart);
        $attemptCount = $this->cache->get($cacheKey);

        if (! is_int($attemptCount)) {
            $attemptCount = 1;
            $this->cache->save($cacheKey, $attemptCount, $retryAfterSeconds);
        } else {
            $attemptCount++;
            $this->cache->save($cacheKey, $attemptCount, $retryAfterSeconds);
        }

        return [
            'limited'             => $attemptCount > $this->maximumAttempts,
            'remaining_attempts'  => max(0, $this->maximumAttempts - $attemptCount),
            'retry_after_seconds' => $retryAfterSeconds,
        ];
    }
}
