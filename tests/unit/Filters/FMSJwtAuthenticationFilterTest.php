<?php

namespace Tests\Unit\Filters;

use App\Filters\FMSAuthenticationClockInterface;
use App\Filters\FMSAuthenticationRateLimiterInterface;
use App\Filters\FMSAuthenticationVerifierInterface;
use App\Filters\FMSBearerTokenParser;
use App\Filters\FMSCacheRateLimiter;
use App\Filters\FMSNoOperationRateLimiter;
use App\Filters\FMSRequestContext;
use App\Filters\FMSJwtAuthenticationFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use CodeIgniter\Test\Mock\MockIncomingRequest;
use Config\App;
use Config\Services;

final class FMSJwtAuthenticationFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        FMSRequestContext::forgetAll();
        parent::tearDown();
    }

    public function testValidTokenStoresSubjectAndPasses(): void
    {
        $filter = new FMSJwtAuthenticationFilter(
            new StubAuthenticationVerifier(['valid' => true, 'reason' => null, 'subject' => ['sub' => 'user-1']]),
        );

        $request = $this->requestWithHeaders(['Authorization' => 'Bearer header.payload.signature']);

        $this->assertNull($filter->before($request));
        $this->assertSame(['sub' => 'user-1'], FMSRequestContext::authenticatedSubject($request));
    }

    public function testMissingAuthorizationHeaderReturnsUnauthorizedWithChallenge(): void
    {
        $filter = new FMSJwtAuthenticationFilter(new StubAuthenticationVerifier([
            'valid'   => false,
            'reason'  => 'JWT_TOKEN_MALFORMED',
            'subject' => null,
        ]));

        $response = $filter->before($this->requestWithHeaders([]));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer error="invalid_request"', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertStringContainsString('"code":401', (string) $response->getBody());
    }

    public function testDuplicateHeaderValuesReturnBadRequest(): void
    {
        $filter = new FMSJwtAuthenticationFilter(new StubAuthenticationVerifier([
            'valid'   => false,
            'reason'  => 'JWT_TOKEN_MALFORMED',
            'subject' => null,
        ]));

        $request = $this->requestWithHeaders(['Authorization' => 'Bearer header.payload.signature']);
        $request->addHeader('Authorization', 'Bearer other.payload.signature');

        $response = $filter->before($request);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('"code":401', (string) $response->getBody());
    }

    public function testInvalidTokenReturnsUnauthorizedWithoutLeakingToken(): void
    {
        $verifier = new StubAuthenticationVerifier(['valid' => false, 'reason' => 'JWT_SIGNATURE_INVALID', 'subject' => null]);
        $filter   = new FMSJwtAuthenticationFilter($verifier);

        $response = $filter->before($this->requestWithHeaders(['Authorization' => 'Bearer header.payload.signature']));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringNotContainsString('header.payload.signature', (string) $response->getBody());
        $this->assertStringContainsString('"code":401', (string) $response->getBody());
    }

    public function testLimitedBucketReturnsTooManyRequestsWithRetryAfter(): void
    {
        $filter = new FMSJwtAuthenticationFilter(
            new StubAuthenticationVerifier(['valid' => false, 'reason' => 'JWT_TOKEN_MALFORMED', 'subject' => null]),
            new StubRateLimiter(['limited' => true, 'remaining_attempts' => 0, 'retry_after_seconds' => 42]),
        );

        $response = $filter->before($this->requestWithHeaders([]));

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('42', $response->getHeaderLine('Retry-After'));
        $this->assertStringContainsString('"code":429', (string) $response->getBody());
    }

    public function testOversizedBearerValueReturnsPayloadTooLarge(): void
    {
        $parser = new FMSBearerTokenParser(16);
        $filter = new FMSJwtAuthenticationFilter(
            new StubAuthenticationVerifier(['valid' => false, 'reason' => 'JWT_TOKEN_MALFORMED', 'subject' => null]),
            new FMSNoOperationRateLimiter(),
            new FixedClock(),
            $parser,
        );

        $response = $filter->before($this->requestWithHeaders([
            'Authorization' => 'Bearer ' . str_repeat('a', 8) . '.' . str_repeat('b', 8) . '.' . str_repeat('c', 8),
        ]));

        $this->assertSame(413, $response->getStatusCode());
        $this->assertStringContainsString('"code":413', (string) $response->getBody());
    }

    public function testCacheRateLimiterLimitsOnlyAfterConfiguredAttempts(): void
    {
        $cache   = new MockCache();
        $clock   = new FixedClock(1_700_000_000);
        $limiter = new FMSCacheRateLimiter($cache, $clock, 2, 60);

        $first  = $limiter->recordAttempt('user-1', FMSJwtAuthenticationFilter::SCOPE);
        $second = $limiter->recordAttempt('user-1', FMSJwtAuthenticationFilter::SCOPE);
        $third  = $limiter->recordAttempt('user-1', FMSJwtAuthenticationFilter::SCOPE);

        $this->assertFalse($first['limited']);
        $this->assertFalse($second['limited']);
        $this->assertTrue($third['limited']);
        $this->assertGreaterThan(0, $third['retry_after_seconds']);
    }

    /**
     * @param array<string, string> $headers
     */
    private function requestWithHeaders(array $headers): IncomingRequest
    {
        $app     = new App();
        $request = new MockIncomingRequest($app, Services::uri(null, false), 'php://input', new UserAgent());

        foreach ($headers as $headerName => $headerValue) {
            $request->setHeader($headerName, $headerValue);
        }

        return $request;
    }
}

final class StubAuthenticationVerifier implements FMSAuthenticationVerifierInterface
{
    /**
     * @param array{valid: bool, reason: string|null, subject: array<string, mixed>|null} $result
     */
    public function __construct(private array $result)
    {
    }

    public function verify(string $presentedCredential): array
    {
        return $this->result;
    }
}

final class StubRateLimiter implements FMSAuthenticationRateLimiterInterface
{
    /**
     * @param array{limited: bool, remaining_attempts: int, retry_after_seconds: int} $result
     */
    public function __construct(private array $result)
    {
    }

    public function recordAttempt(string $rateLimitIdentifier, string $rateLimitScope): array
    {
        return $this->result;
    }
}

final class FixedClock implements FMSAuthenticationClockInterface
{
    public function __construct(private int $timestamp = 1_700_000_000)
    {
    }

    public function currentTimestamp(): int
    {
        return $this->timestamp;
    }
}
