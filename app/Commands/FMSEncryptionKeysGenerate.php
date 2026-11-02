<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Command untuk generate RSA 4096-bit encryption key pair di writable/keys/.
 */
class FMSEncryptionKeysGenerate extends BaseCommand
{
    protected $group = 'FMS';
    protected $name = 'fms:encryption:generate';
    protected $description = 'Membuat RSA 4096-bit encryption key pair (encryption.key & encryption.pub.pem) di writable/keys/.';
    protected $usage = 'fms:encryption:generate [--force]';
    protected $options = [
        '--force' => 'Timpa file key existing.',
    ];

    /**
     * @param array<int, string> $requestParameters
     */
    public function run(array $requestParameters): int
    {
        $outputDir = WRITEPATH . 'keys/';

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $privateKeyPath = $outputDir . 'encryption.key';
        $publicKeyPath = $outputDir . 'encryption.pub.pem';
        $forceOverwrite = CLI::getOption('force') !== null;

        if (is_file($privateKeyPath) && ! $forceOverwrite) {
            CLI::write('File private key sudah ada: ' . $privateKeyPath, 'yellow');
            CLI::write('Gunakan --force untuk menimpa file yang sudah ada.', 'yellow');

            return EXIT_SUCCESS;
        }

        try {
            $config = [
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'digest_alg'      => 'sha256',
            ];

            // Windows (Laragon/XAMPP): openssl_pkey_new gagal error:80000003
            // kalau openssl.cnf tidak ketemu. Cari eksplisit, jangan andalkan default.
            $opensslCnf = $this->resolveOpenSSLConfig();
            if ($opensslCnf !== null) {
                $config['config'] = $opensslCnf;
            }

            CLI::write('Generate RSA 4096-bit (bisa 10-60 dtk di Windows, jangan di-cancel)...', 'yellow');
            $resource = openssl_pkey_new($config);
            if ($resource === false && ($config['private_key_bits'] ?? 4096) === 4096) {
                // Fallback 1: provider default Windows sering gagal alokasi 4096-bit
                // (RAND / I/O error). Coba 2048-bit — tetap aman untuk dev/local.
                CLI::write('4096-bit gagal, fallback ke 2048-bit...', 'yellow');
                $this->drainOpenSSLErrors();
                $config['private_key_bits'] = 2048;
                $resource                   = openssl_pkey_new($config);
            }
            if ($resource === false) {
                CLI::error('Gagal generate RSA key: ' . $this->drainOpenSSLErrors());
                if ($opensslCnf === null) {
                    CLI::write('Hint Windows: set OPENSSL_CONF ke openssl.cnf PHP/Laragon/XAMPP, cth:', 'yellow');
                    CLI::write('  set OPENSSL_CONF=C:\\laragon\\bin\\php\\php-8.4\\extras\\ssl\\openssl.cnf', 'yellow');
                }

                return EXIT_ERROR;
            }

            $privateKeyOutput = '';
            if (! openssl_pkey_export($resource, $privateKeyOutput)) {
                // Fallback 2: error 80000005 saat export = RAND gagal baca seed file
                // ($RANDFILE / .rnd). Coba arahkan ke file writable lalu retry sekali.
                $randError = $this->drainOpenSSLErrors();
                $randFile  = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.rnd';
                if (is_writable(sys_get_temp_dir()) || is_writable(dirname($randFile))) {
                    putenv('RANDFILE=' . $randFile);
                    $_SERVER['RANDFILE'] = $randFile;
                    $_ENV['RANDFILE']    = $randFile;
                    if (@openssl_pkey_export($resource, $privateKeyOutput) && $privateKeyOutput !== '') {
                        CLI::write('Export berhasil setelah retry RANDFILE.', 'green');
                    } else {
                        $this->drainOpenSSLErrors();
                        CLI::error('Gagal export private key: ' . $randError);
                        CLI::write('Hint Windows: tutup antivirus/file-lock di folder project, lalu ulangi --force.', 'yellow');

                        return EXIT_ERROR;
                    }
                } else {
                    CLI::error('Gagal export private key: ' . $randError);

                    return EXIT_ERROR;
                }
            }

            if (file_put_contents($privateKeyPath, $privateKeyOutput) === false) {
                CLI::error('Gagal menyimpan private key ke ' . $privateKeyPath);

                return EXIT_ERROR;
            }
            chmod($privateKeyPath, 0600);

            $publicKeyDetails = openssl_pkey_get_details($resource);
            if ($publicKeyDetails === false || ! isset($publicKeyDetails['key'])) {
                CLI::error('Gagal mengekstrak public key.');

                return EXIT_ERROR;
            }

            if (file_put_contents($publicKeyPath, $publicKeyDetails['key']) === false) {
                CLI::error('Gagal menyimpan public key ke ' . $publicKeyPath);

                return EXIT_ERROR;
            }
            chmod($publicKeyPath, 0644);

            CLI::write('Ditulis private key: ' . $privateKeyPath, 'green');
            CLI::write('Ditulis public key: ' . $publicKeyPath, 'green');
            CLI::write('Jangan commit private key ke git atau membagikannya.', 'yellow');

            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            CLI::error('Exception: ' . $e->getMessage());

            return EXIT_ERROR;
        }
    }

    private function resolveOpenSSLConfig(): ?string
    {
        $fromEnv = getenv('OPENSSL_CONF');
        if (is_string($fromEnv) && $fromEnv !== '' && is_file($fromEnv)) {
            putenv('OPENSSL_CONF=' . $fromEnv);
            $_SERVER['OPENSSL_CONF'] = $fromEnv;
            $_ENV['OPENSSL_CONF']    = $fromEnv;

            return $fromEnv;
        }

        $candidates = [];
        if (PHP_OS_FAMILY === 'Windows') {
            // Lokasi umum Laragon / XAMPP / PHP bawaan + folder instalasi PHP aktif.
            foreach (['C:\\laragon', 'C:\\xampp', dirname(PHP_BINARY, 2), dirname(PHP_BINARY)] as $base) {
                $candidates[] = $base . '\\bin\\php\\php-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '\\extras\\ssl\\openssl.cnf';
                $candidates[] = $base . '\\php\\extras\\ssl\\openssl.cnf';
                $candidates[] = $base . '\\apache\\conf\\openssl.cnf';
                $candidates[] = $base . '\\extras\\ssl\\openssl.cnf';
            }
        } else {
            foreach (['/opt/homebrew/etc/openssl/openssl.cnf', '/usr/local/etc/openssl/openssl.cnf', '/etc/ssl/openssl.cnf'] as $path) {
                $candidates[] = $path;
            }
        }

        foreach (array_unique($candidates) as $path) {
            if (is_file($path)) {
                // openssl_pkey_export spawn ulang butuh OPENSSL_CONF di env proses.
                putenv('OPENSSL_CONF=' . $path);
                $_SERVER['OPENSSL_CONF'] = $path;
                $_ENV['OPENSSL_CONF']    = $path;

                return $path;
            }
        }

        return null;
    }

    private function drainOpenSSLErrors(): string
    {
        $messages = [];
        while ($message = openssl_error_string()) {
            $messages[] = $message;
        }

        return $messages === [] ? 'unknown error' : implode(' | ', $messages);
    }
}
