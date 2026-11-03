<?php

namespace App\Modules\Settings\Services;

use App\Libraries\FMSKeyLoader;
use RuntimeException;
use Throwable;

/**
 * Operasi server-side dari panel Konfigurasi: generate keys + migrate.
 * Khusus Super Administrator tanpa akses terminal.
 */
final class FMSOpsConsoleService
{
    /** @return array{ok: bool, wrote: string[], skipped: string[]} */
    public function generateResponseKeys(bool $force = false): array
    {
        return $this->generateSigningKeys('response', $force);
    }

    /** @return array{ok: bool, wrote: string[], skipped: string[]} */
    public function generateJwtKeys(bool $force = false): array
    {
        return $this->generateSigningKeys('jwt', $force);
    }

    /** @return array{ok: bool, wrote: string[], skipped: string[]} */
    private function generateSigningKeys(string $scope, bool $force): array
    {
        $loader = new FMSKeyLoader();
        $pair = $loader->generateKeyPair();

        $paths = $scope === 'jwt'
            ? ['private' => WRITEPATH . 'keys/fms-jwt-active.key', 'public' => WRITEPATH . 'keys/fms-jwt-active.pub']
            : ['private' => WRITEPATH . 'keys/fms-response-private.key', 'public' => WRITEPATH . 'keys/fms-response-public.key'];

        $wrote = [];
        $skipped = [];

        foreach ($paths as $type => $path) {
            if (is_file($path) && ! $force) {
                $skipped[] = basename($path);
                continue;
            }
            $loader->writeEncodedKey($path, $type === 'private' ? $pair['private_key'] : $pair['public_key']);
            $wrote[] = basename($path);
        }

        return ['ok' => true, 'wrote' => $wrote, 'skipped' => $skipped];
    }

    /** @return array{ok: bool, wrote: string[], skipped: string[]} */
    public function generateEncryptionKeys(bool $force = false): array
    {
        $dir = WRITEPATH . 'keys/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $priv = $dir . 'encryption.key';
        $pub = $dir . 'encryption.pub.pem';

        if (is_file($priv) && ! $force) {
            return ['ok' => true, 'wrote' => [], 'skipped' => ['encryption.key', 'encryption.pub.pem']];
        }

        $res = openssl_pkey_new(['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha256']);
        if ($res === false) {
            throw new RuntimeException('Gagal generate RSA key: ' . (openssl_error_string() ?: 'unknown error'));
        }

        $out = '';
        if (! openssl_pkey_export($res, $out)) {
            throw new RuntimeException('Gagal export private key: ' . (openssl_error_string() ?: 'unknown error'));
        }
        if (file_put_contents($priv, $out, LOCK_EX) === false) {
            throw new RuntimeException('Gagal menyimpan ' . $priv);
        }
        chmod($priv, 0600);

        $details = openssl_pkey_get_details($res);
        if ($details === false || ! isset($details['key'])) {
            throw new RuntimeException('Gagal mengekstrak public key.');
        }
        if (file_put_contents($pub, $details['key'], LOCK_EX) === false) {
            throw new RuntimeException('Gagal menyimpan ' . $pub);
        }
        chmod($pub, 0644);

        return ['ok' => true, 'wrote' => ['encryption.key', 'encryption.pub.pem'], 'skipped' => []];
    }

    /** @return array{ok: bool, output: string} */
    public function runMigrateAll(): array
    {
        $runner = \Config\Services::migrations();
        $runner->setNamespace(null)->latest();
        $history = $runner->getHistory();
        $last = end($history);

        return ['ok' => true, 'output' => is_array($last) ? (($last['version'] ?? '') . ' ' . ($last['class'] ?? '')) : 'migrate selesai'];
    }

    /** @return array<string, array{exists: bool, size: int, mtime: int|null}> */
    public function keyStatus(): array
    {
        $files = [
            'response_private' => WRITEPATH . 'keys/fms-response-private.key',
            'response_public' => WRITEPATH . 'keys/fms-response-public.key',
            'jwt_private' => WRITEPATH . 'keys/fms-jwt-active.key',
            'jwt_public' => WRITEPATH . 'keys/fms-jwt-active.pub',
            'encryption_private' => WRITEPATH . 'keys/encryption.key',
            'encryption_public' => WRITEPATH . 'keys/encryption.pub.pem',
        ];

        $out = [];
        foreach ($files as $key => $path) {
            $out[$key] = [
                'exists' => is_file($path),
                'size' => is_file($path) ? (int) filesize($path) : 0,
                'mtime' => is_file($path) ? (int) filemtime($path) : null,
            ];
        }

        return $out;
    }
}
