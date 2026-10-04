<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

/**
 * Verifies HTTP Basic credentials without logging raw passwords: the rate
 * limiter is keyed by a non-reversible hash of the username scope.
 */
final class FMSBasicAuthenticationFilter extends FMSBaseAuthenticationFilter
{
    public const SCOPE = 'fms-basic-authentication';

    public function __construct(
        ?FMSAuthenticationVerifierInterface $authenticationVerifier = null,
        ?FMSAuthenticationRateLimiterInterface $rateLimiter = null,
        ?FMSAuthenticationClockInterface $systemClock = null,
        private readonly FMSBasicAuthenticationCredentialParser $credentialParser = new FMSBasicAuthenticationCredentialParser(),
    ) {
        parent::__construct($authenticationVerifier, $rateLimiter, $systemClock);
    }

    /**
     * @param list<string>|null $filterArguments
     */
    public function before(RequestInterface $request, $filterArguments = null)
    {
        $parseResult = $this->credentialParser->parse(
            $this->extractHeaderValues($request, 'Authorization'),
        );

        $authenticationChallenge = 'Basic realm="fms-api", charset="UTF-8"';

        if ($parseResult['valid'] === false) {
            $statusByReason = match ($parseResult['reason']) {
                FMSBasicAuthenticationCredentialParser::REASON_OVERSIZED => 413,
                FMSBasicAuthenticationCredentialParser::REASON_DUPLICATE => 400,
                default                                                 => 401,
            };

            return $this->failureResponseForAttempt(
                'basic-anonymous',
                self::SCOPE,
                [
                    'status'  => false,
                    'message' => 'Kredensial dasar tidak valid.',
                ],
                $statusByReason,
                $authenticationChallenge,
            );
        }

        $username = (string) $parseResult['username'];
        $password = (string) $parseResult['password'];

        $verificationResult = $this->authenticationVerifier()->verify($username . "\0" . $password);

        unset($password);
        $presentedCredential = null;

        if ($verificationResult['valid'] === true) {
            unset($presentedCredential);
            FMSRequestContext::setAuthenticatedSubject($request, (array) ($verificationResult['subject'] ?? []));

            return null;
        }

        return $this->failureResponseForAttempt(
            $this->hashedIdentifierFor($username, 'basic'),
            self::SCOPE,
            [
                'status'  => false,
                'message' => 'Kredensial dasar tidak valid.',
            ],
            401,
            $authenticationChallenge,
        );
    }

    protected function createAuthenticationVerifier(): FMSAuthenticationVerifierInterface
    {
        return new FMSDatabaseBasicAuthenticationVerifier();
    }
}
