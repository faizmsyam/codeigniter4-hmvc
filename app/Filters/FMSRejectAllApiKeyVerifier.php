<?php

namespace App\Filters;

/**
 * Fail-closed default for API key verification. Production installs replace it
 * by injecting a verifier that compares the presented secret against a
 * hashed stored value (e.g. SHA-256 of "<key_id>.<secret>") with a
 * constant-time comparison.
 */
final class FMSRejectAllApiKeyVerifier implements FMSAuthenticationVerifierInterface
{
    public function verify(string $presentedCredential): array
    {
        return ['valid' => false, 'reason' => 'API_KEY_INVALID', 'subject' => null];
    }
}
