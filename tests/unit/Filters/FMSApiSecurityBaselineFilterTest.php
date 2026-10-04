<?php

namespace Tests\Unit\Filters;

use App\Config\FMSApiSecurity;
use App\Filters\FMSApiSecurityBaselineFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockIncomingRequest;
use Config\App;
use Config\Services;

final class FMSApiSecurityBaselineFilterTest extends CIUnitTestCase
{
    public function testRejectsCrossOriginRequestsByDefault(): void
    {
        $filter = new FMSApiSecurityBaselineFilter(new FMSApiSecurity());
        $request = $this->request('GET', ['Origin' => 'https://untrusted.example']);

        $response = $filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('"code":403', (string) $response->getBody());
        $this->assertStringNotContainsString('untrusted.example', (string) $response->getBody());
    }

    public function testRejectsJsonMutationWithoutJsonContentType(): void
    {
        $filter = new FMSApiSecurityBaselineFilter(new FMSApiSecurity());
        $request = $this->request('POST', ['Content-Type' => 'text/plain']);

        $response = $filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(415, $response->getStatusCode());
        $this->assertStringContainsString('"code":415', (string) $response->getBody());
    }

    public function testRejectsOversizedPayloadBeforeController(): void
    {
        $configuration = new FMSApiSecurity();
        $configuration->maximumJsonBodyBytes = 16;
        $filter = new FMSApiSecurityBaselineFilter($configuration);
        $request = $this->request('POST', [
            'Content-Type' => 'application/json',
            'Content-Length' => '17',
        ]);

        $response = $filter->before($request);

        $this->assertNotNull($response);
        $this->assertSame(413, $response->getStatusCode());
        $this->assertStringContainsString('"code":413', (string) $response->getBody());
    }

    public function testAllowsJsonMutationWithinConfiguredLimit(): void
    {
        $filter = new FMSApiSecurityBaselineFilter(new FMSApiSecurity());
        $request = $this->request('POST', [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Length' => '128',
        ]);

        $this->assertNull($filter->before($request));
    }

    public function testAddsNoStoreAndDefensiveHeaders(): void
    {
        $filter = new FMSApiSecurityBaselineFilter(new FMSApiSecurity());
        $request = $this->request('GET');
        $response = service('response');

        $result = $filter->after($request, $response);

        $this->assertSame('nosniff', $result->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('DENY', $result->getHeaderLine('X-Frame-Options'));
        $this->assertSame('no-referrer', $result->getHeaderLine('Referrer-Policy'));
        $this->assertStringContainsString('no-store', $result->getHeaderLine('Cache-Control'));
        $this->assertSame("default-src 'none'; frame-ancestors 'none'; base-uri 'none'", $result->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('', $result->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testRefreshCookieConfigurationIsHardened(): void
    {
        $configuration = new FMSApiSecurity();

        $this->assertTrue($configuration->refreshCookieEnabled);
        $this->assertTrue($configuration->refreshCookieSecure);
        $this->assertTrue($configuration->refreshCookieHttpOnly);
        $this->assertSame('Strict', $configuration->refreshCookieSameSite);
        $this->assertSame('FMS-CSRF-TOKEN', $configuration->csrfHeaderName);
        $this->assertSame('fms_csrf', $configuration->csrfCookieName);
    }

    /** @param array<string, string> $headers */
    private function request(string $method, array $headers = []): IncomingRequest
    {
        $applicationConfiguration = new App();
        $request = new MockIncomingRequest(
            $applicationConfiguration,
            Services::uri(null, false),
            'php://input',
            new UserAgent(),
        );
        $request->setMethod($method);

        foreach ($headers as $headerName => $headerValue) {
            $request->setHeader($headerName, $headerValue);
        }

        return $request;
    }
}
