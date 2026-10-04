<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

/**
 * Verifies a JWT bearer token presented in the Authorization header.
 *
 * Rejects missing, duplicated, oversized, and malformed bearer values before
 * delegating cryptographic verification to the injected verifier. Properties
 * are attached to the request object so downstream controllers can authorise
 * on the resolved subject without re-parsing the header.
 */
final class FMSJwtAuthenticationFilter extends FMSBaseAuthenticationFilter
{
    public const SCOPE = 'fms-jwt-authentication';

    public function __construct(
        ?FMSAuthenticationVerifierInterface $authenticationVerifier = null,
        ?FMSAuthenticationRateLimiterInterface $rateLimiter = null,
        ?FMSAuthenticationClockInterface $systemClock = null,
        private readonly FMSBearerTokenParser $bearerTokenParser = new FMSBearerTokenParser(),
    ) {
        parent::__construct($authenticationVerifier, $rateLimiter, $systemClock);
    }

    /**
     * @param list<string>|null $filterArguments
     */
    public function before(RequestInterface $request, $filterArguments = null)
    {
        $parseResult = $this->bearerTokenParser->parse(
            $this->extractHeaderValues($request, 'Authorization'),
        );

        if ($parseResult['valid'] === false) {
            return $this->failureResponseForAttempt(
                'jwt-anonymous',
                self::SCOPE,
                [
                    'status'  => false,
                    'message' => 'Token akses tidak valid.',
                ],
                $parseResult['reason'] === FMSBearerTokenParser::REASON_OVERSIZED ? 413 : 401,
                'Bearer error="invalid_request"',
            );
        }

        $accessToken = (string) $parseResult['token'];
        $verificationResult = $this->authenticationVerifier()->verify($accessToken);

        if ($verificationResult['valid'] === true) {
            FMSRequestContext::setAuthenticatedSubject($request, (array) ($verificationResult['subject'] ?? []));

            return null;
        }

        $verificationReason = (string) ($verificationResult['reason'] ?? FMSBearerTokenParser::REASON_MALFORMED);

        return $this->failureResponseForAttempt(
            $this->hashedIdentifierFor($accessToken, 'jwt'),
            self::SCOPE,
            [
                'status'  => false,
                'message' => 'Token akses tidak valid.',
            ],
            $verificationReason === FMSBearerTokenParser::REASON_OVERSIZED ? 413 : 401,
            'Bearer error="invalid_token"',
        );
    }

    protected function createAuthenticationVerifier(): FMSAuthenticationVerifierInterface
    {
        return new FMSJwtAuthenticationVerifier();
    }
}
