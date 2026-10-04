<?php

namespace App\Commands;

use App\Config\FMSLicense;
use App\Libraries\FMSIntegrityVerifier;
use App\Libraries\FMSStartupGuard;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FMSIntegrityVerify extends BaseCommand
{
    protected $group = 'FMS';
    protected $name = 'fms:integrity:verify';
    protected $description = 'Memverifikasi signed license manifest dan signed release integrity.';
    protected $usage = 'fms:integrity:verify [options]';
    protected $options = [
        '--environment' => 'Environment yang diperiksa, default mengikuti CI_ENVIRONMENT.',
        '--current-time' => 'Timestamp override untuk pengujian lisensi, format YYYY-MM-DDTHH:MM:SSZ.',
    ];

    public function run(array $requestParameters): int
    {
        $selectedEnvironment = (string) (CLI::getOption('environment') ?: (getenv('CI_ENVIRONMENT') ?: ENVIRONMENT));
        $overrideTimestampValue = CLI::getOption('current-time');
        $overrideTimestamp = is_string($overrideTimestampValue) && $overrideTimestampValue !== ''
            ? strtotime($overrideTimestampValue)
            : false;
        $currentTimestamp = $overrideTimestamp === false ? time() : (int) $overrideTimestamp;

        $verificationResult = (new FMSStartupGuard())->verify($selectedEnvironment, $currentTimestamp);

        if ($verificationResult['allowed'] === false) {
            CLI::error(sprintf('FMS startup verification FAILED: %s', $verificationResult['reason']));

            return EXIT_ERROR;
        }

        $configuration = config(FMSLicense::class);
        $monitoredFileCount = 0;
        if (is_file($configuration->integrityManifestPath)) {
            try {
                $integrityManifest = json_decode((string) file_get_contents($configuration->integrityManifestPath), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($integrityManifest) && isset($integrityManifest['files']) && is_array($integrityManifest['files'])) {
                    $monitoredFileCount = count($integrityManifest['files']);
                }
            } catch (\JsonException $decodingException) {
                CLI::error('Integrity manifest cannot be parsed.');

                return EXIT_ERROR;
            }
        }

        CLI::write(sprintf('FMS startup verification PASSED: %s', $verificationResult['reason']), 'green');
        CLI::write(sprintf('Monitored release files: %d', $monitoredFileCount), 'yellow');
        CLI::write(sprintf('Excluded runtime paths: %s', implode(', ', $configuration->excludedPaths)), 'yellow');

        $sampleFileCount = count((new FMSIntegrityVerifier())->buildFileHashes(
            ROOTPATH,
            ['app/Libraries', 'app/Config'],
            $configuration->excludedPaths,
        ));
        CLI::write(sprintf('Sample monitored files under app/Libraries and app/Config: %d', $sampleFileCount), 'yellow');

        return EXIT_SUCCESS;
    }
}
