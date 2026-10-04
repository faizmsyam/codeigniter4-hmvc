<?php

namespace App\Libraries;

use RuntimeException;

final class FMSIntegrityVerifier
{
    public function __construct(
        private readonly FMSCanonicalJson $canonicalJson = new FMSCanonicalJson(),
        private readonly FMSKeyLoader $keyLoader = new FMSKeyLoader(),
    ) {
    }

    /**
     * @param array<string, mixed> $integrityManifest
     */
    public function canonicalIntegrityPayload(array $integrityManifest): string
    {
        $files = (array) ($integrityManifest['files'] ?? []);
        ksort($files, SORT_STRING);

        return $this->canonicalJson->encode([
            'app_id' => (string) ($integrityManifest['app_id'] ?? ''),
            'files' => $files,
            'generated_at' => (string) ($integrityManifest['generated_at'] ?? ''),
            'release_id' => (string) ($integrityManifest['release_id'] ?? ''),
            'schema' => (int) ($integrityManifest['schema'] ?? 1),
        ]);
    }

    /**
     * @param array<string, mixed> $integrityManifest
     * @param array<int, string> $excludedPaths
     * @return array{valid: bool, reason: string, file: string|null}
     */
    public function verify(
        array $integrityManifest,
        string $encodedSignature,
        string $publicKeyPath,
        string $rootPath,
        array $excludedPaths = [],
    ): array {
        if (!$this->hasValidSchema($integrityManifest)) {
            return ['valid' => false, 'reason' => 'INTEGRITY_SCHEMA_INVALID', 'file' => null];
        }

        try {
            $publicKey = $this->keyLoader->loadPublicKey($publicKeyPath);
            $detachedSignature = $this->keyLoader->base64UrlDecode($encodedSignature);
        } catch (RuntimeException $runtimeException) {
            return ['valid' => false, 'reason' => 'INTEGRITY_SIGNATURE_INVALID', 'file' => null];
        }

        if (!sodium_crypto_sign_verify_detached(
            $detachedSignature,
            $this->canonicalIntegrityPayload($integrityManifest),
            $publicKey,
        )) {
            return ['valid' => false, 'reason' => 'INTEGRITY_SIGNATURE_INVALID', 'file' => null];
        }

        $normalizedRootPath = rtrim(str_replace('\\', '/', realpath($rootPath) ?: $rootPath), '/');
        $manifestFiles = (array) $integrityManifest['files'];

        foreach ($manifestFiles as $relativeFilePath => $expectedFileHash) {
            if (!$this->isSafeRelativePath((string) $relativeFilePath, $excludedPaths)) {
                return ['valid' => false, 'reason' => 'INTEGRITY_PATH_INVALID', 'file' => (string) $relativeFilePath];
            }

            $absoluteFilePath = $normalizedRootPath . '/' . $relativeFilePath;
            if (!is_file($absoluteFilePath) || is_link($absoluteFilePath)) {
                return ['valid' => false, 'reason' => 'INTEGRITY_FILE_MISSING', 'file' => (string) $relativeFilePath];
            }

            $actualFileHash = hash_file('sha256', $absoluteFilePath);
            if (!is_string($expectedFileHash) || !hash_equals(strtolower($expectedFileHash), strtolower($actualFileHash))) {
                return ['valid' => false, 'reason' => 'INTEGRITY_HASH_MISMATCH', 'file' => (string) $relativeFilePath];
            }
        }

        return ['valid' => true, 'reason' => 'INTEGRITY_OK', 'file' => null];
    }

    /**
     * @param array<int, string> $monitoredPaths
     * @param array<int, string> $excludedPaths
     * @return array<string, string>
     */
    public function buildFileHashes(string $rootPath, array $monitoredPaths, array $excludedPaths): array
    {
        $normalizedRootPath = rtrim(str_replace('\\', '/', realpath($rootPath) ?: $rootPath), '/');
        $fileHashes = [];

        foreach ($monitoredPaths as $monitoredPath) {
            $absoluteMonitoredPath = $normalizedRootPath . '/' . trim($monitoredPath, '/');
            if (!file_exists($absoluteMonitoredPath)) {
                continue;
            }

            if (is_file($absoluteMonitoredPath)) {
                $candidateFiles = [$absoluteMonitoredPath];
            } else {
                $candidateFiles = [];
                $directoryIterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($absoluteMonitoredPath, \FilesystemIterator::SKIP_DOTS),
                );
                foreach ($directoryIterator as $fileInformation) {
                    if ($fileInformation->isFile() && !$fileInformation->isLink()) {
                        $candidateFiles[] = $fileInformation->getPathname();
                    }
                }
            }

            foreach ($candidateFiles as $absoluteFilePath) {
                $normalizedAbsoluteFilePath = str_replace('\\', '/', $absoluteFilePath);
                $relativeFilePath = ltrim(substr($normalizedAbsoluteFilePath, strlen($normalizedRootPath)), '/');
                if (!$this->isSafeRelativePath($relativeFilePath, $excludedPaths)) {
                    continue;
                }

                $fileHashes[$relativeFilePath] = hash_file('sha256', $absoluteFilePath);
            }
        }

        ksort($fileHashes, SORT_STRING);

        return $fileHashes;
    }

    /**
     * @param array<string, mixed> $integrityManifest
     */
    private function hasValidSchema(array $integrityManifest): bool
    {
        if ((int) ($integrityManifest['schema'] ?? 0) !== 1) {
            return false;
        }

        if (($integrityManifest['app_id'] ?? '') === '' || ($integrityManifest['release_id'] ?? '') === '') {
            return false;
        }

        if (!is_array($integrityManifest['files'] ?? null) || $integrityManifest['files'] === []) {
            return false;
        }

        foreach ($integrityManifest['files'] as $relativeFilePath => $fileHash) {
            if (!is_string($relativeFilePath) || preg_match('/^[a-f0-9]{64}$/', (string) $fileHash) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $excludedPaths
     */
    private function isSafeRelativePath(string $relativeFilePath, array $excludedPaths): bool
    {
        $normalizedRelativeFilePath = ltrim(str_replace('\\', '/', $relativeFilePath), '/');
        if ($normalizedRelativeFilePath === '' || str_contains($normalizedRelativeFilePath, "\0")) {
            return false;
        }

        if (preg_match('#(^|/)\.\.(/|$)#', $normalizedRelativeFilePath) === 1) {
            return false;
        }

        foreach ($excludedPaths as $excludedPath) {
            $normalizedExcludedPath = trim(str_replace('\\', '/', $excludedPath), '/');
            if ($normalizedRelativeFilePath === $normalizedExcludedPath || str_starts_with($normalizedRelativeFilePath, $normalizedExcludedPath . '/')) {
                return false;
            }
        }

        return true;
    }
}
