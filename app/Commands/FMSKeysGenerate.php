<?php

namespace App\Commands;

use App\Libraries\FMSKeyLoader;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FMSKeysGenerate extends BaseCommand
{
    protected $group = 'FMS';
    protected $name = 'fms:keys:generate';
    protected $description = 'Membuat Ed25519 response/JWT key pair lokal untuk development dan test.';
    protected $usage = 'fms:keys:generate [--scope=<response|jwt>] [--force]';
    protected $options = [
        '--scope' => 'Pilih scope key: response atau jwt. Default: response.',
        '--force' => 'Timpa file key existing.',
    ];

    /**
     * @param array<int, string> $requestParameters
     */
    public function run(array $requestParameters): int
    {
        $selectedScope = $this->resolveScopeOption();
        if (!in_array($selectedScope, ['response', 'jwt'], true)) {
            CLI::error('Scope key tidak valid. Gunakan response atau jwt.');

            return EXIT_ERROR;
        }

        $forceOverwrite = CLI::getOption('force') !== null;
        $keyLoader = new FMSKeyLoader();
        $generatedKeyPair = $keyLoader->generateKeyPair();
        $keyPaths = $this->resolveKeyPaths($selectedScope);

        foreach ($keyPaths as $keyType => $keyPath) {
            if (is_file($keyPath) && $forceOverwrite === false) {
                CLI::write(sprintf('Lewati %s karena file sudah ada: %s', $keyType, $keyPath), 'yellow');

                continue;
            }

            $binaryKey = $keyType === 'private'
                ? $generatedKeyPair['private_key']
                : $generatedKeyPair['public_key'];
            $keyLoader->writeEncodedKey($keyPath, $binaryKey);
            CLI::write(sprintf('Ditulis %s: %s', $keyType, $keyPath), 'green');
        }

        CLI::write('Jangan commit private key production atau menyalin private key ke response/log.', 'yellow');

        return EXIT_SUCCESS;
    }

    /**
     * Ambil nilai --scope baik dalam format "--scope=jwt" maupun "--scope jwt".
     * Parser CLI CI4 (system/CLI/CLI.php::parseCommandLine) tidak memecah "--scope=jwt",
     * sehingga option tersimpan dengan key literal "scope=jwt" dan getOption('scope') null.
     */
    private function resolveScopeOption(): string
    {
        $options = CLI::getOptions();

        foreach (array_keys($options) as $optionKey) {
            if (str_starts_with($optionKey, 'scope=')) {
                return strtolower((string) substr($optionKey, strlen('scope=')));
            }
        }

        return strtolower((string) (CLI::getOption('scope') ?: 'response'));
    }

    /**
     * @return array{private: string, public: string}
     */
    private function resolveKeyPaths(string $selectedScope): array
    {
        if ($selectedScope === 'jwt') {
            return [
                'private' => WRITEPATH . 'keys/fms-jwt-active.key',
                'public' => WRITEPATH . 'keys/fms-jwt-active.pub',
            ];
        }

        return [
            'private' => WRITEPATH . 'keys/fms-response-private.key',
            'public' => WRITEPATH . 'keys/fms-response-public.key',
        ];
    }
}
