<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

final class FMSJwt extends BaseConfig
{
    /**
     * Algoritma yang diizinkan untuk access token. Allowlist ini bersifat server-side
     * dan tidak pernah diambil dari header token.
     *
     * @var list<string>
     */
    public array $allowedAlgorithms = ['EdDSA', 'RS256'];

    public string $activeAlgorithm = 'EdDSA';
    public string $activeKeyId = 'fms-jwt-active-2026-01';
    public string $issuer = 'fms-api';
    public string $audience = 'fms-client';

    /**
     * Access token hanya berumur pendek.
     */
    public int $accessTokenTtlSeconds = 600;

    /**
     * Refresh token opaque dengan umur terbatas.
     */
    public int $refreshTokenTtlSeconds = 1209600;

    /**
     * Batas toleransi clock skew dalam detik.
     */
    public int $clockSkewToleranceSeconds = 30;

    /**
     * Public key aktif dan key lama untuk window rotasi, dipetakan per key id.
     *
     * @var array<string, string>
     */
    public array $publicKeyPaths = [
        'fms-jwt-active-2026-01' => WRITEPATH . 'keys/fms-jwt-active.pub',
    ];

    /**
     * Private signing key. Default default mendukung EdDSA (Ed25519).
     */
    public string $privateKeyPath = WRITEPATH . 'keys/fms-jwt-active.key';

    /**
     * Khusus mode HS256 eksplisit. Tidak boleh dipakai sebagai fallback otomatis.
     */
    public bool $allowSymmetricMode = false;
    public string $symmetricSecret = '';

    /**
     * @return list<string>
     */
    public function resolveAllowedAlgorithms(): array
    {
        return array_values(array_unique($this->allowedAlgorithms));
    }

    public function hasSymmetricSecret(): bool
    {
        return $this->allowSymmetricMode && strlen($this->symmetricSecret) >= 32;
    }
}
