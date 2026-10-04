<?php

namespace App\Filters;

/**
 * Fail-closed default for HTTP Basic verification. Production installs inject
 * a verifier that compares passwords against stored hashes with a
 * constant-time comparison and lockout on repeated failures.
 */
final class FMSRejectAllBasicAuthenticationVerifier implements FMSAuthenticationVerifierInterface
{
    public function verify(string $presentedCredential): array
    {
        return ['valid' => false, 'reason' => 'BASIC_CREDENTIAL_INVALID', 'subject' => null];
    }
}
