<?php

namespace App\Filters;

/**
 * Registry documenting the FMS API filter aliases without editing the shared
 * parent configuration: copy these entries into the effective Filters config
 * when wiring routes.
 */
final class FMSFiltersRegistry
{
    /**
     * @return array<string, class-string>
     */
    public static function aliases(): array
    {
        return [
            'fms-api-authentication'    => FMSApiAuthenticationFilter::class,
            'fms-jwt-authentication'   => FMSJwtAuthenticationFilter::class,
            'fms-api-key-authentication' => FMSApiKeyAuthenticationFilter::class,
            'fms-basic-authentication' => FMSBasicAuthenticationFilter::class,
            'fms-security-headers'     => FMSResponseSecurityHeadersFilter::class,
        ];
    }
}
