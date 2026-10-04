<?php

namespace Tests\Unit\Libraries;

use App\Config\FMSJwt;
use App\Libraries\FMSJwtService;
use App\Libraries\FMSKeyLoader;
use CodeIgniter\Test\CIUnitTestCase;
use Firebase\JWT\JWT;

final class FMSJwtServiceTest extends CIUnitTestCase
{
    private string $temporaryDirectory = '';
    private string $privateKeyPath = '';
    private string $publicKeyPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = WRITEPATH . 'tests/jwt-service-' . bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory, 0700, true);
        $this->privateKeyPath = $this->temporaryDirectory . '/active.key';
        $this->publicKeyPath = $this->temporaryDirectory . '/active.pub';

        $keyLoader = new FMSKeyLoader();
        $generatedKeys = $keyLoader->generateKeyPair();
        $keyLoader->writeEncodedKey($this->privateKeyPath, $generatedKeys['private_key']);
        $keyLoader->writeEncodedKey($this->publicKeyPath, $generatedKeys['public_key']);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        if (is_dir($this->temporaryDirectory)) {
            rmdir($this->temporaryDirectory);
        }

        parent::tearDown();
    }

    public function testValidTokenAccepted(): void
    {
        $jwtService = $this->createJwtService();
        $accessToken = $jwtService->issueAccessToken('user-uuid-123', 1);
        $verificationResult = $jwtService->verifyAccessToken($accessToken);

        $this->assertTrue($verificationResult['valid']);
        $this->assertSame('user-uuid-123', $verificationResult['payload']['sub']);
        $this->assertSame(1, $verificationResult['payload']['token_version']);
    }

    public function testClaimAndKeyNegativeCases(): void
    {
        $jwtService = $this->createJwtService();
        $validToken = $jwtService->issueAccessToken('user-uuid-123', 1);

        $tokenSegments = explode('.', $validToken);
        $firstSignatureCharacter = substr($tokenSegments[2], 0, 1);
        $tokenSegments[2] = ($firstSignatureCharacter === 'A' ? 'B' : 'A') . substr($tokenSegments[2], 1);
        $tamperedToken = implode('.', $tokenSegments);
        $this->assertSame('JWT_SIGNATURE_INVALID', $jwtService->verifyAccessToken($tamperedToken)['reason']);
        $this->assertSame('JWT_TOKEN_MALFORMED', $jwtService->verifyAccessToken('not-a-token')['reason']);
        $this->assertSame('JWT_TOKEN_MALFORMED', $jwtService->verifyAccessToken('')['reason']);

        $expiredService = $this->createJwtService();
        $expiredToken = $expiredService->issueAccessToken('user-uuid-123', 1, [], time() - 7200);
        $this->assertSame('JWT_TOKEN_EXPIRED', $expiredService->verifyAccessToken($expiredToken)['reason']);

        $this->assertSame('JWT_CLAIM_INVALID', $jwtService->verifyAccessToken($this->buildTokenWithClaims(['sub' => 123]))['reason']);
        $this->assertSame('JWT_CLAIM_INVALID', $jwtService->verifyAccessToken($this->buildTokenWithClaims(['iss' => 'wrong']))['reason']);
        $this->assertSame('JWT_CLAIM_INVALID', $jwtService->verifyAccessToken($this->buildTokenWithClaims(['aud' => 'wrong']))['reason']);
        $this->assertSame('JWT_CLAIM_INVALID', $jwtService->verifyAccessToken($this->buildTokenWithClaims(['nbf' => time() + 3600]))['reason']);
        $this->assertSame('JWT_TOKEN_EXPIRED', $jwtService->verifyAccessToken($this->buildTokenWithClaims(['exp' => time() - 3600]))['reason']);
        $this->assertSame('JWT_ALGORITHM_INVALID', $jwtService->verifyAccessToken($this->buildTokenWithAlgorithm('HS256'))['reason']);
        $this->assertSame('JWT_KEY_UNKNOWN', $jwtService->verifyAccessToken($this->buildTokenWithKeyId('unknown'))['reason']);
    }

    public function testMissingConfigurationFailsClosed(): void
    {
        $jwtConfiguration = $this->createJwtConfiguration();
        $jwtConfiguration->publicKeyPaths = [];
        $jwtService = new FMSJwtService($jwtConfiguration, new FMSKeyLoader());

        $this->assertSame('JWT_CONFIGURATION_INVALID', $jwtService->verifyAccessToken('header.payload.signature')['reason']);
    }

    /**
     * @param array<string, mixed> $claimOverrides
     */
    private function buildTokenWithClaims(array $claimOverrides): string
    {
        $jwtConfiguration = $this->createJwtConfiguration();
        $currentTimestamp = time();
        $payload = array_merge(
            [
                'iss' => $jwtConfiguration->issuer,
                'aud' => $jwtConfiguration->audience,
                'sub' => 'user-uuid-123',
                'iat' => $currentTimestamp,
                'nbf' => $currentTimestamp,
                'exp' => $currentTimestamp + 600,
                'jti' => bin2hex(random_bytes(16)),
                'token_version' => 1,
            ],
            $claimOverrides,
        );

        $keyLoader = new FMSKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($this->privateKeyPath);

        return JWT::encode($payload, $keyLoader->base64UrlEncode($privateKey), 'EdDSA', $jwtConfiguration->activeKeyId);
    }

    private function buildTokenWithAlgorithm(string $algorithm): string
    {
        $payload = [
            'iss' => 'fms-api',
            'aud' => 'fms-client',
            'sub' => 'user-uuid-123',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 600,
            'jti' => bin2hex(random_bytes(16)),
            'token_version' => 1,
        ];

        return JWT::encode($payload, str_repeat('s', 64), $algorithm, 'foreign-key');
    }

    private function buildTokenWithKeyId(string $keyId): string
    {
        $keyLoader = new FMSKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($this->privateKeyPath);
        $payload = [
            'iss' => 'fms-api',
            'aud' => 'fms-client',
            'sub' => 'user-uuid-123',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 600,
            'jti' => bin2hex(random_bytes(16)),
            'token_version' => 1,
        ];

        return JWT::encode($payload, $keyLoader->base64UrlEncode($privateKey), 'EdDSA', $keyId);
    }

    private function createJwtService(int $accessTtl = 600, int $clockSkew = 30): FMSJwtService
    {
        $jwtConfiguration = $this->createJwtConfiguration();
        $jwtConfiguration->accessTokenTtlSeconds = $accessTtl;
        $jwtConfiguration->clockSkewToleranceSeconds = $clockSkew;

        return new FMSJwtService($jwtConfiguration, new FMSKeyLoader());
    }

    private function createJwtConfiguration(): FMSJwt
    {
        $jwtConfiguration = new FMSJwt();
        $jwtConfiguration->allowedAlgorithms = ['EdDSA'];
        $jwtConfiguration->activeAlgorithm = 'EdDSA';
        $jwtConfiguration->activeKeyId = 'fms-jwt-test-active';
        $jwtConfiguration->issuer = 'fms-api';
        $jwtConfiguration->audience = 'fms-client';
        $jwtConfiguration->accessTokenTtlSeconds = 600;
        $jwtConfiguration->clockSkewToleranceSeconds = 30;
        $jwtConfiguration->privateKeyPath = $this->privateKeyPath;
        $jwtConfiguration->publicKeyPaths = [
            'fms-jwt-test-active' => $this->publicKeyPath,
        ];

        return $jwtConfiguration;
    }
}
