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
                'digest_alg' => 'sha256',
            ];

            $resource = openssl_pkey_new($config);
            if ($resource === false) {
                CLI::error('Gagal generate RSA key: ' . (openssl_error_string() ?: 'unknown error'));

                return EXIT_ERROR;
            }

            $privateKeyOutput = '';
            if (! openssl_pkey_export($resource, $privateKeyOutput)) {
                CLI::error('Gagal export private key: ' . (openssl_error_string() ?: 'unknown error'));

                return EXIT_ERROR;
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
}
