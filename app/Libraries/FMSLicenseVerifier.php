<?php

namespace App\Libraries;

use RuntimeException;

final class FMSLicenseVerifier
{
    private const SCHEMA_VERSION = 1;
    private const DEFAULT_GRACE_SECONDS = 86400;

    public function __construct(
        private readonly FMSCanonicalJson $canonicalJson = new FMSCanonicalJson(),
        private readonly FMSKeyLoader $keyLoader = new FMSKeyLoader(),
    ) {
    }

    /**
     * @param array<string, mixed> $licenseManifest
     */
    public function canonicalLicensePayload(array $licenseManifest): string
    {
        $payload = [
            'app_id' => (string) ($licenseManifest['app_id'] ?? ''),
            'entitlements' => (array) ($licenseManifest['entitlements'] ?? []),
            'issued_at' => (string) ($licenseManifest['issued_at'] ?? ''),
            'license_id' => (string) ($licenseManifest['license_id'] ?? ''),
            'licensee' => (string) ($licenseManifest['licensee'] ?? ''),
            'revoked' => (bool) ($licenseManifest['revoked'] ?? false),
            'revocation_id' => (string) ($licenseManifest['revocation_id'] ?? ''),
            'schema' => (int) ($licenseManifest['schema'] ?? self::SCHEMA_VERSION),
            'valid_until' => (string) ($licenseManifest['valid_until'] ?? ''),
        ];

        sort($payload['entitlements'], SORT_STRING);

        return $this->canonicalJson->encode($payload);
    }

    /**
     * @param array<string, mixed> $licenseManifest
     * @return array{valid: bool, reason: string}
     */
    public function verify(array $licenseManifest, string $encodedSignature, string $publicKeyPath, int $currentTimestamp): array
    {
        $validationResult = $this->validateSchema($licenseManifest);
        if ($validationResult['valid'] === false) {
            return $validationResult;
        }

        $canonicalPayload = $this->canonicalLicensePayload($licenseManifest);
        $publicKey = $this->keyLoader->loadPublicKey($publicKeyPath);

        try {
            $detachedSignature = $this->keyLoader->base64UrlDecode($encodedSignature);
        } catch (RuntimeException $decodingException) {
            return ['valid' => false, 'reason' => 'LICENSE_SIGNATURE_INVALID'];
        }

        if (!sodium_crypto_sign_verify_detached($detachedSignature, $canonicalPayload, $publicKey)) {
            return ['valid' => false, 'reason' => 'LICENSE_SIGNATURE_INVALID'];
        }

        if ($licenseManifest['revoked'] === true) {
            return ['valid' => false, 'reason' => 'LICENSE_REVOKED'];
        }

        return $this->validateTimeWindow($licenseManifest, $currentTimestamp);
    }

    /**
     * @param array<string, mixed> $licenseManifest
     * @return array{valid: bool, reason: string}
     */
    private function validateSchema(array $licenseManifest): array
    {
        $requiredFields = ['app_id', 'licensee', 'license_id', 'issued_at', 'schema'];
        foreach ($requiredFields as $requiredField) {
            if (!isset($licenseManifest[$requiredField]) || $licenseManifest[$requiredField] === '') {
                return ['valid' => false, 'reason' => 'LICENSE_SCHEMA_INVALID'];
            }
        }

        if ((int) $licenseManifest['schema'] !== self::SCHEMA_VERSION) {
            return ['valid' => false, 'reason' => 'LICENSE_SCHEMA_INVALID'];
        }

        if ($licenseManifest['licensee'] !== \App\Config\FMSLicense::LICENSEE_NAME) {
            return ['valid' => false, 'reason' => 'LICENSE_LICENSEE_MISMATCH'];
        }

        if (!isset($licenseManifest['entitlements']) || !is_array($licenseManifest['entitlements']) || $licenseManifest['entitlements'] === []) {
            return ['valid' => false, 'reason' => 'LICENSE_SCHEMA_INVALID'];
        }

        if ($this->parseTimestamp((string) $licenseManifest['issued_at']) === null) {
            return ['valid' => false, 'reason' => 'LICENSE_SCHEMA_INVALID'];
        }

        $validUntil = (string) ($licenseManifest['valid_until'] ?? '');
        if ($validUntil !== '' && strtolower($validUntil) !== 'perpetual' && $this->parseTimestamp($validUntil) === null) {
            return ['valid' => false, 'reason' => 'LICENSE_SCHEMA_INVALID'];
        }

        return ['valid' => true, 'reason' => 'LICENSE_OK'];
    }

    /**
     * @param array<string, mixed> $licenseManifest
     * @return array{valid: bool, reason: string}
     */
    private function validateTimeWindow(array $licenseManifest, int $currentTimestamp): array
    {
        $issuedAt = $this->parseTimestamp((string) $licenseManifest['issued_at']);
        if ($issuedAt === null || $currentTimestamp + self::DEFAULT_GRACE_SECONDS < $issuedAt) {
            return ['valid' => false, 'reason' => 'LICENSE_TIME_INVALID'];
        }

        $validUntil = strtolower((string) ($licenseManifest['valid_until'] ?? ''));
        if ($validUntil === '' || $validUntil === 'perpetual') {
            return ['valid' => true, 'reason' => 'LICENSE_OK'];
        }

        $validUntilTimestamp = $this->parseTimestamp((string) $licenseManifest['valid_until']);
        if ($validUntilTimestamp === null || $currentTimestamp > $validUntilTimestamp + self::DEFAULT_GRACE_SECONDS) {
            return ['valid' => false, 'reason' => 'LICENSE_EXPIRED'];
        }

        return ['valid' => true, 'reason' => 'LICENSE_OK'];
    }

    private function parseTimestamp(string $timestampValue): ?int
    {
        try {
            $parsedTimestamp = new \DateTimeImmutable($timestampValue);
        } catch (\Exception $parsingException) {
            return null;
        }

        return (int) $parsedTimestamp->format('U');
    }
}
