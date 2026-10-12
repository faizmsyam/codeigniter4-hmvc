<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Config\FMSApi;
use App\Config\FMSJwt;

/**
 * Memastikan seluruh key kriptografi Ed25519 tersedia sebelum aplikasi berjalan.
 * Aplikasi diblokir total (exception di pre_system) jika key tidak ada atau kosong,
 * dengan pesan instruksional cara generate via spark.
 */
final class FMSKeysGuard
{
    public function verify(): void
    {
        $missing = $this->collectMissingKeys();

        if (empty($missing)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            "Konfigurasi Kunci / Salt FMS tidak lengkap:\n  - %s\n"
            . "Pastikan FMS_HASHIDS_SALT diaktifkan di .env dan jalankan generate keys:\n"
            . "  php spark fms:keys:generate --scope=response\n"
            . "  php spark fms:keys:generate --scope=jwt\n"
            . "  php spark fms:encryption:generate",
            implode("\n  - ", $missing)
        ));
    }

    /**
     * @return list<string>
     */
    private function collectMissingKeys(): array
    {
        $apiConfig = config(FMSApi::class);
        $jwtConfig = config(FMSJwt::class);
        $idConfig  = config(\App\Config\FMSId::class);

        $requiredKeys = [
            'Response signing private key (fms-response-private.key)' => $apiConfig->responseSigningPrivateKeyPath,
            'Response signing public key (fms-response-public.key)' => $apiConfig->responseSigningPublicKeyPath,
            'JWT private key (fms-jwt-active.key)' => $jwtConfig->privateKeyPath,
            'JWT public key (fms-jwt-active.pub)' => array_values($jwtConfig->publicKeyPaths)[0] ?? '',
        ];

        $missing = [];

        if (strlen($idConfig->hashidsSalt) < 32) {
            $missing[] = 'FMS_HASHIDS_SALT di .env belum diatur atau kurang dari 32 karakter (Wajib diaktifkan)';
        }

        foreach ($requiredKeys as $label => $path) {
            if ($path === '' || ! is_file($path)) {
                $missing[] = sprintf('%s tidak ada di %s', $label, $path === '' ? '(path kosong di config)' : $path);

                continue;
            }

            if (trim((string) @file_get_contents($path)) === '') {
                $missing[] = sprintf('%s kosong di %s', $label, $path);
            }
        }

        return $missing;
    }
}
