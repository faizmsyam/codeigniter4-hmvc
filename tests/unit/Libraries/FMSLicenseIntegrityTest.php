<?php

namespace Tests\Unit\Libraries;

use App\Config\FMSLicense;
use App\Libraries\FMSCanonicalJson;
use App\Libraries\FMSIntegrityVerifier;
use App\Libraries\FMSKeyLoader;
use App\Libraries\FMSLicenseVerifier;
use App\Libraries\FMSStartupGuard;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSLicenseIntegrityTest extends CIUnitTestCase
{
    private string $temporaryDirectory;
    private FMSKeyLoader $keyLoader;
    private string $privateKey;
    private string $publicKeyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = WRITEPATH . 'tests/fms-license-' . bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory, 0700, true);
        $this->keyLoader = new FMSKeyLoader();
        $keyPair = $this->keyLoader->generateKeyPair();
        $this->privateKey = $keyPair['private_key'];
        $this->publicKeyPath = $this->temporaryDirectory . '/public.key';
        $this->keyLoader->writeEncodedKey($this->publicKeyPath, $keyPair['public_key'], 0644);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->temporaryDirectory);
        parent::tearDown();
    }

    public function testValidLicensePassesAndMutationFails(): void
    {
        $licenseVerifier = new FMSLicenseVerifier();
        $licenseManifest = $this->validLicenseManifest();
        $signature = $this->sign($licenseVerifier->canonicalLicensePayload($licenseManifest));

        $validResult = $licenseVerifier->verify($licenseManifest, $signature, $this->publicKeyPath, strtotime('2026-09-29T00:00:00Z'));
        $this->assertTrue($validResult['valid']);

        $licenseManifest['app_id'] = 'tampered-app';
        $tamperedResult = $licenseVerifier->verify($licenseManifest, $signature, $this->publicKeyPath, strtotime('2026-09-29T00:00:00Z'));
        $this->assertFalse($tamperedResult['valid']);
        $this->assertSame('LICENSE_SIGNATURE_INVALID', $tamperedResult['reason']);
    }

    public function testExpiredRevokedAndWrongLicenseeFail(): void
    {
        $licenseVerifier = new FMSLicenseVerifier();

        $expiredLicense = $this->validLicenseManifest();
        $expiredLicense['valid_until'] = '2025-01-01T00:00:00Z';
        $expiredSignature = $this->sign($licenseVerifier->canonicalLicensePayload($expiredLicense));
        $this->assertSame(
            'LICENSE_EXPIRED',
            $licenseVerifier->verify($expiredLicense, $expiredSignature, $this->publicKeyPath, strtotime('2026-09-29T00:00:00Z'))['reason'],
        );

        $revokedLicense = $this->validLicenseManifest();
        $revokedLicense['revoked'] = true;
        $revokedSignature = $this->sign($licenseVerifier->canonicalLicensePayload($revokedLicense));
        $this->assertSame(
            'LICENSE_REVOKED',
            $licenseVerifier->verify($revokedLicense, $revokedSignature, $this->publicKeyPath, strtotime('2026-09-29T00:00:00Z'))['reason'],
        );

        $wrongLicensee = $this->validLicenseManifest();
        $wrongLicensee['licensee'] = 'Other Licensee';
        $wrongLicenseeSignature = $this->sign($licenseVerifier->canonicalLicensePayload($wrongLicensee));
        $this->assertSame(
            'LICENSE_LICENSEE_MISMATCH',
            $licenseVerifier->verify($wrongLicensee, $wrongLicenseeSignature, $this->publicKeyPath, strtotime('2026-09-29T00:00:00Z'))['reason'],
        );
    }

    public function testValidIntegrityPassesAndOneByteMutationFails(): void
    {
        $monitoredFilePath = $this->temporaryDirectory . '/monitored.php';
        file_put_contents($monitoredFilePath, '<?php return true;');

        $integrityVerifier = new FMSIntegrityVerifier();
        $integrityManifest = [
            'schema' => 1,
            'app_id' => 'fms-codeigniter4',
            'release_id' => 'test-release',
            'generated_at' => '2026-09-29T00:00:00Z',
            'files' => ['monitored.php' => hash_file('sha256', $monitoredFilePath)],
        ];
        $signature = $this->sign($integrityVerifier->canonicalIntegrityPayload($integrityManifest));

        $validResult = $integrityVerifier->verify($integrityManifest, $signature, $this->publicKeyPath, $this->temporaryDirectory);
        $this->assertTrue($validResult['valid']);

        file_put_contents($monitoredFilePath, '<?php return false;');
        $tamperedResult = $integrityVerifier->verify($integrityManifest, $signature, $this->publicKeyPath, $this->temporaryDirectory);
        $this->assertFalse($tamperedResult['valid']);
        $this->assertSame('INTEGRITY_HASH_MISMATCH', $tamperedResult['reason']);
    }

    public function testIntegrityRejectsTraversalAndMissingFiles(): void
    {
        $integrityVerifier = new FMSIntegrityVerifier();
        $traversalManifest = [
            'schema' => 1,
            'app_id' => 'fms-codeigniter4',
            'release_id' => 'test-release',
            'generated_at' => '2026-09-29T00:00:00Z',
            'files' => ['../outside.php' => str_repeat('a', 64)],
        ];
        $traversalSignature = $this->sign($integrityVerifier->canonicalIntegrityPayload($traversalManifest));
        $this->assertSame(
            'INTEGRITY_PATH_INVALID',
            $integrityVerifier->verify($traversalManifest, $traversalSignature, $this->publicKeyPath, $this->temporaryDirectory)['reason'],
        );

        $missingManifest = $traversalManifest;
        $missingManifest['files'] = ['missing.php' => str_repeat('a', 64)];
        $missingSignature = $this->sign($integrityVerifier->canonicalIntegrityPayload($missingManifest));
        $this->assertSame(
            'INTEGRITY_FILE_MISSING',
            $integrityVerifier->verify($missingManifest, $missingSignature, $this->publicKeyPath, $this->temporaryDirectory)['reason'],
        );
    }

    public function testProductionGuardFailsClosedWhenManifestsAreMissing(): void
    {
        $configuration = new FMSLicense();
        $configuration->licenseManifestPath = $this->temporaryDirectory . '/missing-license.json';
        $configuration->licenseSignaturePath = $this->temporaryDirectory . '/missing-license.sig';
        $configuration->licensePublicKeyPath = $this->publicKeyPath;
        $configuration->integrityManifestPath = $this->temporaryDirectory . '/missing-integrity.json';
        $configuration->integritySignaturePath = $this->temporaryDirectory . '/missing-integrity.sig';
        $configuration->integrityPublicKeyPath = $this->publicKeyPath;

        $startupGuard = new FMSStartupGuard(configuration: $configuration);
        $productionResult = $startupGuard->verify('production', strtotime('2026-09-29T00:00:00Z'));
        $developmentResult = $startupGuard->verify('development', strtotime('2026-09-29T00:00:00Z'));

        $this->assertFalse($productionResult['allowed']);
        $this->assertSame('LICENSE_MANIFEST_MISSING', $productionResult['reason']);
        $this->assertTrue($developmentResult['allowed']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validLicenseManifest(): array
    {
        return [
            'schema' => 1,
            'app_id' => 'fms-codeigniter4',
            'licensee' => FMSLicense::LICENSEE_NAME,
            'license_id' => 'fms-license-test',
            'issued_at' => '2026-09-01T00:00:00Z',
            'valid_until' => 'perpetual',
            'entitlements' => ['admin', 'api'],
            'revoked' => false,
            'revocation_id' => '',
        ];
    }

    private function sign(string $canonicalPayload): string
    {
        return $this->keyLoader->base64UrlEncode(sodium_crypto_sign_detached($canonicalPayload, $this->privateKey));
    }

    private function deleteDirectory(string $directoryPath): void
    {
        if (!is_dir($directoryPath)) {
            return;
        }

        $fileIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($fileIterator as $fileInformation) {
            if ($fileInformation->isDir()) {
                rmdir($fileInformation->getPathname());
            } else {
                unlink($fileInformation->getPathname());
            }
        }
        rmdir($directoryPath);
    }
}
