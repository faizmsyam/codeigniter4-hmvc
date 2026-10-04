<?php

namespace Tests\Unit\Filters;

use App\Filters\FMSApiKeyAuthenticationFilter;
use App\Filters\FMSAuthenticationClockInterface;
use App\Filters\FMSAuthenticationRateLimiterInterface;
use App\Filters\FMSAuthenticationVerifierInterface;
use App\Filters\FMSBasicAuthenticationFilter;
use App\Filters\FMSRequestContext;
use App\Filters\FMSResponseSecurityHeadersFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockIncomingRequest;
use Config\App;
use Config\Services;

final class FMSAuthenticationFiltersTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        FMSRequestContext::forgetAll();
        parent::tearDown();
    }

    public function testApiKeyFilterAcceptsKeyFromHeaderAndStoresSubject(): void
    {
        $filter = new FMSApiKeyAuthenticationFilter(
            new RecordingVerifier(['valid' => true, 'reason' => null, 'subject' => ['key_id' => 'ABCDEF1234567890']]),
        );

        $request  = $this->requestWithHeaders(['X-API-Key' => 'ABCDEF1234567890.' . str_repeat('s', 32)]);
        $response = $filter->before($request);

        $this->assertNull($response);
        $this->assertSame(['key_id' => 'ABCDEF1234567890'], FMSRequestContext::authenticatedSubject($request));
    }

    public function testApiKeyFilterRejectsMissingHeaderWithUnauthorized(): void
    {
        $filter = new FMSApiKeyAuthenticationFilter(new RejectingVerifier());

        $response = $filter->before($this->requestWithHeaders([]));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('"code":401', (string) $response->getBody());
    }

    public function testApiKeyFilterRejectsMalformedCredentialWithBadRequestShape(): void
    {
        $filter = new FMSApiKeyAuthenticationFilter(new RejectingVerifier());

        $malformed = $filter->before($this->requestWithHeaders(['X-API-Key' => 'not-a-valid-credential']));
        $this->assertSame(401, $malformed->getStatusCode());

        $oversized = $filter->before($this->requestWithHeaders([
            'X-API-Key' => 'ABCDEF1234567890.' . str_repeat('s', 4096),
        ]));

        $this->assertSame(413, $oversized->getStatusCode());
    }

    public function testApiKeyFilterRejectsConflictBetweenApiKeyAndAuthorizationHeaders(): void
    {
        $filter = new FMSApiKeyAuthenticationFilter(new RejectingVerifier());
        $credential = 'ABCDEF1234567890.' . str_repeat('s', 32);

        $response = $filter->before($this->requestWithHeaders([
            'X-API-Key'     => $credential,
            'Authorization' => 'ApiKey ' . $credential,
        ]));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testApiKeyFilterNeverPassesRawSecretToVerifier(): void
    {
        $verifier = new RecordingVerifier(['valid' => false, 'reason' => 'API_KEY_INVALID', 'subject' => null]);
        $filter   = new FMSApiKeyAuthenticationFilter($verifier);
        $secret   = str_repeat('z', 32);

        $filter->before($this->requestWithHeaders(['X-API-Key' => 'ABCDEF1234567890.' . $secret]));

        $this->assertCount(1, $verifier->presentedCredentials);
        $this->assertSame('ABCDEF1234567890.' . $secret, $verifier->presentedCredentials[0]);
    }

    public function testApiKeyFilterEscalatesToRateLimitedResponse(): void
    {
        $filter = new FMSApiKeyAuthenticationFilter(
            new RejectingVerifier(),
            new AlwaysLimitedRateLimiter(17),
        );

        $response = $filter->before($this->requestWithHeaders([]));

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('17', $response->getHeaderLine('Retry-After'));
    }

    public function testBasicFilterAcceptsEncodedCredentialsAndStoresSubject(): void
    {
        $verifier = new RecordingVerifier(['valid' => true, 'reason' => null, 'subject' => ['username' => 'service-client']]);
        $filter   = new FMSBasicAuthenticationFilter($verifier);

        $response = $filter->before($this->requestWithHeaders([
            'Authorization' => 'Basic ' . base64_encode('service-client:correct-horse'),
        ]));

        $this->assertNull($response);
        $this->assertSame('service-client' . "\0" . 'correct-horse', $verifier->presentedCredentials[0]);
    }

    public function testBasicFilterRejectsMissingAndMalformedCredentialsWithChallenge(): void
    {
        $filter = new FMSBasicAuthenticationFilter(new RejectingVerifier());

        $missing  = $filter->before($this->requestWithHeaders([]));
        $invalid  = $filter->before($this->requestWithHeaders(['Authorization' => 'Basic not*base64']));
        $noColon  = $filter->before($this->requestWithHeaders(['Authorization' => 'Basic ' . base64_encode('usernameonly')]));

        foreach ([$missing, $invalid, $noColon] as $response) {
            $this->assertSame(401, $response->getStatusCode());
            $this->assertStringContainsString('Basic realm=', $response->getHeaderLine('WWW-Authenticate'));
            $this->assertStringContainsString('"code":401', (string) $response->getBody());
        }
    }

    public function testBasicFilterDoesNotLeakSubmittedPasswordOnFailure(): void
    {
        $submittedPassword = 'super-secret-password';
        $filter            = new FMSBasicAuthenticationFilter(new RejectingVerifier());

        $response = $filter->before($this->requestWithHeaders([
            'Authorization' => 'Basic ' . base64_encode('service-client:' . $submittedPassword),
        ]));

        $this->assertStringNotContainsString($submittedPassword, (string) $response->getBody());
        $this->assertStringNotContainsString(base64_encode('service-client:' . $submittedPassword), (string) $response->getBody());
    }

    public function testSecurityHeaderFilterSetsBaselineWithoutOverwritingExistingHeaders(): void
    {
        $filter   = new FMSResponseSecurityHeadersFilter();
        $response = new Response(new App());
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');

        $result = $filter->after($this->requestWithHeaders([]), $response);

        $this->assertSame('SAMEORIGIN', $result->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $result->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('no-referrer', $result->getHeaderLine('Referrer-Policy'));
        $this->assertStringContainsString('no-store', $result->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString("default-src 'none'", $result->getHeaderLine('Content-Security-Policy'));
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

final class RecordingVerifier implements FMSAuthenticationVerifierInterface
{
    /**
     * @var list<string>
     */
    public array $presentedCredentials = [];

    /**
     * @param array{valid: bool, reason: string|null, subject: array<string, mixed>|null} $result
     */
    public function __construct(private array $result)
    {
    }

    public function verify(string $presentedCredential): array
    {
        $this->presentedCredentials[] = $presentedCredential;

        return $this->result;
    }
}

final class RejectingVerifier implements FMSAuthenticationVerifierInterface
{
    public function verify(string $presentedCredential): array
    {
        return ['valid' => false, 'reason' => 'CREDENTIAL_INVALID', 'subject' => null];
    }
}

final class AlwaysLimitedRateLimiter implements FMSAuthenticationRateLimiterInterface
{
    public function __construct(private int $retryAfterSeconds)
    {
    }

    public function recordAttempt(string $rateLimitIdentifier, string $rateLimitScope): array
    {
        return [
            'limited'             => true,
            'remaining_attempts'  => 0,
            'retry_after_seconds' => $this->retryAfterSeconds,
        ];
    }
}
