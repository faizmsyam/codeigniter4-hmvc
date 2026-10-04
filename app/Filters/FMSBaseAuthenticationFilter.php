<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Shared behaviour for FMS authentication filters.
 *
 * The framework instantiates filters with no arguments, so every collaborator
 * is optional and resolved lazily through the factory methods. Tests inject
 * stubs through the constructor instead of touching the container.
 */
abstract class FMSBaseAuthenticationFilter implements FilterInterface
{
    public function __construct(
        protected readonly ?FMSAuthenticationVerifierInterface $authenticationVerifier = null,
        protected readonly ?FMSAuthenticationRateLimiterInterface $rateLimiter = null,
        protected readonly ?FMSAuthenticationClockInterface $systemClock = null,
    ) {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $filterArguments = null)
    {
        return null;
    }

    protected function authenticationVerifier(): FMSAuthenticationVerifierInterface
    {
        return $this->authenticationVerifier ?? $this->createAuthenticationVerifier();
    }

    protected function rateLimiter(): FMSAuthenticationRateLimiterInterface
    {
        return $this->rateLimiter ?? $this->createRateLimiter();
    }

    protected function systemClock(): FMSAuthenticationClockInterface
    {
        return $this->systemClock ?? new FMSSystemClock();
    }

    abstract protected function createAuthenticationVerifier(): FMSAuthenticationVerifierInterface;

    protected function createRateLimiter(): FMSAuthenticationRateLimiterInterface
    {
        return new FMSNoOperationRateLimiter();
    }

    /**
     * @param list<string>|null $filterArguments
     *
     * @return ResponseInterface|null
     */
    abstract public function before(RequestInterface $request, $filterArguments = null);

    /**
     * Returns every value configured for a header, flattened to strings.
     *
     * A header repeated across the request is returned as multiple entries so
     * callers can reject duplicates instead of silently trusting the first one.
     *
     * @return list<string>
     */
    protected function extractHeaderValues(RequestInterface $request, string $headerName): array
    {
        $headers = $request->header($headerName);

        if ($headers === null) {
            return [];
        }

        if (! is_array($headers)) {
            $headers = [$headers];
        }

        $headerValues = [];
        foreach ($headers as $header) {
            foreach ((array) $header->getValue() as $singleValue) {
                $headerValues[] = (string) $singleValue;
            }
        }

        return $headerValues;
    }

    /**
     * @param array<string, mixed> $responseBody
     */
    protected function authenticationFailureResponse(
        array $responseBody,
        int $httpStatusCode,
        ?int $retryAfterSeconds = null,
        ?string $authenticationChallenge = null,
    ): ResponseInterface {
        $response = $this->newResponseObject();

        $responseBody['code'] = $httpStatusCode;
        $responseBody['status'] = false;

        $response->setStatusCode($httpStatusCode);
        $response->setBody(json_encode(
            $responseBody,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            64,
        ));
        $response->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $response->setHeader('Cache-Control', 'no-store');
        $response->setHeader('Pragma', 'no-cache');

        if ($retryAfterSeconds !== null) {
            $response->setHeader('Retry-After', (string) max(1, $retryAfterSeconds));
        }

        if ($authenticationChallenge !== null) {
            $response->setHeader('WWW-Authenticate', $authenticationChallenge);
        }

        return $response;
    }

    protected function newResponseObject(): ResponseInterface
    {
        $response = service('response');

        if (! $response instanceof ResponseInterface) {
            throw new RuntimeException('The response service must implement ResponseInterface.');
        }

        return $response;
    }

    /**
     * Registers a failed authentication attempt, escalating to HTTP 429 when the
     * configured limiter refuses further attempts for this window.
     *
     * @param array<string, mixed> $failureBody
     */
    protected function failureResponseForAttempt(
        string $rateLimitIdentifier,
        string $rateLimitScope,
        array $failureBody,
        int $httpStatusCode,
        ?string $authenticationChallenge = null,
    ): ResponseInterface {
        $rateLimitResult = $this->rateLimiter()->recordAttempt($rateLimitIdentifier, $rateLimitScope);

        if ($rateLimitResult['limited'] === true) {
            return $this->authenticationFailureResponse(
                [
                    'status'  => false,
                    'message' => 'Terlalu banyak percobaan. Coba lagi nanti.',
                ],
                429,
                $rateLimitResult['retry_after_seconds'],
            );
        }

        return $this->authenticationFailureResponse(
            $failureBody,
            $httpStatusCode,
            null,
            $authenticationChallenge,
        );
    }

    /**
     * Builds a non-reversible rate limit identifier so credentials and tokens
     * never reach the rate limiter as raw values.
     */
    protected function hashedIdentifierFor(string $rawIdentifier, string $identifierScope): string
    {
        return $identifierScope . '-' . substr(hash('sha256', $rawIdentifier), 0, 32);
    }
}
