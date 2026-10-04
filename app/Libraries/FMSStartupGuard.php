<?php

namespace App\Libraries;

use App\Config\FMSLicense;

final class FMSStartupGuard
{
    public const PRODUCTION_ENVIRONMENT = 'production';

    public function __construct(
        private readonly FMSLicenseVerifier $licenseVerifier = new FMSLicenseVerifier(),
        private readonly FMSIntegrityVerifier $integrityVerifier = new FMSIntegrityVerifier(),
        private readonly ?FMSLicense $configuration = null,
    ) {
    }

    /**
     * @return array{allowed: bool, reason: string}
     */
    public function verify(string $environment, int $currentTimestamp): array
    {
        $configuration = $this->configuration ?? config(FMSLicense::class);
        if ($environment !== self::PRODUCTION_ENVIRONMENT || !$configuration->enforceInProduction) {
            return ['allowed' => true, 'reason' => 'STARTUP_VERIFICATION_SKIPPED'];
        }

        $licenseManifestPath = $configuration->licenseManifestPath;
        $licenseSignaturePath = $configuration->licenseSignaturePath;
        if (!is_file($licenseManifestPath) || !is_file($licenseSignaturePath)) {
            return ['allowed' => false, 'reason' => 'LICENSE_MANIFEST_MISSING'];
        }

        $licenseManifest = $this->decodeJsonFile($licenseManifestPath);
        if ($licenseManifest === null) {
            return ['allowed' => false, 'reason' => 'LICENSE_MANIFEST_INVALID'];
        }

        $licenseResult = $this->licenseVerifier->verify(
            $licenseManifest,
            $this->readTrimmedFile($licenseSignaturePath),
            $configuration->licensePublicKeyPath,
            $currentTimestamp,
        );
        if ($licenseResult['valid'] === false) {
            return ['allowed' => false, 'reason' => $licenseResult['reason']];
        }

        $integrityManifestPath = $configuration->integrityManifestPath;
        $integritySignaturePath = $configuration->integritySignaturePath;
        if (!is_file($integrityManifestPath) || !is_file($integritySignaturePath)) {
            return ['allowed' => false, 'reason' => 'INTEGRITY_MANIFEST_MISSING'];
        }

        $integrityManifest = $this->decodeJsonFile($integrityManifestPath);
        if ($integrityManifest === null) {
            return ['allowed' => false, 'reason' => 'INTEGRITY_MANIFEST_INVALID'];
        }

        $integrityResult = $this->integrityVerifier->verify(
            $integrityManifest,
            $this->readTrimmedFile($integritySignaturePath),
            $configuration->integrityPublicKeyPath,
            ROOTPATH,
            $configuration->excludedPaths,
        );
        if ($integrityResult['valid'] === false) {
            return ['allowed' => false, 'reason' => $integrityResult['reason']];
        }

        return ['allowed' => true, 'reason' => 'STARTUP_VERIFIED'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonFile(string $filePath): ?array
    {
        $fileContents = @file_get_contents($filePath);
        if ($fileContents === false) {
            return null;
        }

        try {
            $decodedContents = json_decode($fileContents, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $decodingException) {
            return null;
        }

        return is_array($decodedContents) ? $decodedContents : null;
    }

    private function readTrimmedFile(string $filePath): string
    {
        $fileContents = @file_get_contents($filePath);

        return trim((string) $fileContents);
    }
}
