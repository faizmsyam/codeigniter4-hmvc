<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

/**
 * Verifies an API key credential presented through X-API-Key (or the
 * Authorization header with the ApiKey scheme) without ever logging or
 * exposing the secret: rate limiting is keyed by the key identifier only.
 */
final class FMSApiKeyAuthenticationFilter extends FMSBaseAuthenticationFilter
{
    public const SCOPE = 'fms-api-key-authentication';

    public function __construct(
        ?FMSAuthenticationVerifierInterface $authenticationVerifier = null,
        ?FMSAuthenticationRateLimiterInterface $rateLimiter = null,
        ?FMSAuthenticationClockInterface $systemClock = null,
        private readonly FMSApiKeyCredentialParser $credentialParser = new FMSApiKeyCredentialParser(),
    ) {
        parent::__construct($authenticationVerifier, $rateLimiter, $systemClock);
    }

    /**
     * @param list<string>|null $filterArguments
     */
    public function before(RequestInterface $request, $filterArguments = null)
    {
        $parseResult = $this->credentialParser->parse(
            $this->extractHeaderValues($request, 'X-API-Key'),
            $this->extractHeaderValues($request, 'Authorization'),
        );

        if ($parseResult['valid'] === false) {
            $statusByReason = match ($parseResult['reason']) {
                FMSApiKeyCredentialParser::REASON_OVERSIZED             => 413,
                FMSApiKeyCredentialParser::REASON_HEADER_CONFLICT       => 400,
                FMSApiKeyCredentialParser::REASON_DUPLICATE             => 400,
                default                                                => 401,
            };

            return $this->failureResponseForAttempt(
                'api-key-anonymous',
                self::SCOPE,
                [
                    'status'  => false,
                    'message' => 'API key tidak valid.',
                ],
                $statusByReason,
            );
        }

        $keyIdentifier = (string) $parseResult['key_id'];
        $keySecret     = (string) $parseResult['key_secret'];

        $verificationResult = $this->authenticationVerifier()->verify($keyIdentifier . '.' . $keySecret);

        if ($verificationResult['valid'] === true) {
            FMSRequestContext::setAuthenticatedSubject($request, (array) ($verificationResult['subject'] ?? []));

            return null;
        }

        return $this->failureResponseForAttempt(
            $this->hashedIdentifierFor($keyIdentifier, 'api-key'),
            self::SCOPE,
            [
                'status'  => false,
                'message' => 'API key tidak valid.',
            ],
            401,
        );
    }

    protected function createAuthenticationVerifier(): FMSAuthenticationVerifierInterface
    {
        return new FMSDatabaseApiKeyVerifier();
    }
}
