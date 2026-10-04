<?php

namespace App\Filters;

interface FMSAuthenticationVerifierInterface
{
    /**
     * @return array{valid: bool, reason: string|null, subject: array<string, mixed>|null}
     */
    public function verify(string $presentedCredential): array;
}
