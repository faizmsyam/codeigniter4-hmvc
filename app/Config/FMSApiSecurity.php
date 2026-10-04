<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

final class FMSApiSecurity extends BaseConfig
{
    public int $maximumJsonBodyBytes = 1048576;
    public int $maximumUploadBodyBytes = 8388608;
    public bool $requireJsonContentType = true;
    public bool $allowCrossOriginRequests = false;
    public bool $refreshCookieEnabled = true;
    public string $refreshCookieName = 'fms_refresh_token';
    public int $refreshCookieTtlSeconds = 1209600;
    public bool $refreshCookieSecure = true;
    public bool $refreshCookieHttpOnly = true;
    public string $refreshCookieSameSite = 'Strict';
    public string $csrfHeaderName = 'FMS-CSRF-TOKEN';
    public string $csrfCookieName = 'fms_csrf';
}
