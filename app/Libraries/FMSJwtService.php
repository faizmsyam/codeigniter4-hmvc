<?php

namespace App\Libraries;

use App\Config\FMSJwt;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class FMSJwtService
{
    public const REASON_CONFIGURATION_INVALID = 'JWT_CONFIGURATION_INVALID';
    public const REASON_TOKEN_MALFORMED = 'JWT_TOKEN_MALFORMED';
    public const REASON_SIGNATURE_INVALID = 'JWT_SIGNATURE_INVALID';
    public const REASON_TOKEN_EXPIRED = 'JWT_TOKEN_EXPIRED';
    public const REASON_CLAIM_INVALID = 'JWT_CLAIM_INVALID';
    public const REASON_ALGORITHM_INVALID = 'JWT_ALGORITHM_INVALID';
    public const REASON_KEY_UNKNOWN = 'JWT_KEY_UNKNOWN';

    private readonly FMSJwt $jwtConfiguration;
    private readonly FMSKeyLoader $keyLoader;

    public function __construct(?FMSJwt $jwtConfiguration = null, ?FMSKeyLoader $keyLoader = null)
    {
        $this->jwtConfiguration = $jwtConfiguration ?? config(FMSJwt::class);
        $this->keyLoader = $keyLoader ?? new FMSKeyLoader();
    }

    /**
     * @param array<string, mixed> $customClaims
     */
    public function issueAccessToken(string $userUuid, int $tokenVersion, array $customClaims = [], ?int $currentTimestamp = null): string
    {
        $configurationError = $this->validateConfiguration(true);
        if ($configurationError !== null) {
            throw new RuntimeException($configurationError);
        }

        $issuedAt = ($currentTimestamp ?? time());
        $notBefore = $issuedAt;
        $expiresAt = $issuedAt + $this->jwtConfiguration->accessTokenTtlSeconds;
        $tokenIdentifier = $this->createTokenIdentifier();

        $payload = array_merge(
            $customClaims,
            [
                'iss' => $this->jwtConfiguration->issuer,
                'aud' => $this->jwtConfiguration->audience,
                'sub' => $userUuid,
                'iat' => $issuedAt,
                'nbf' => $notBefore,
                'exp' => $expiresAt,
                'jti' => $tokenIdentifier,
                'token_version' => $tokenVersion,
            ],
        );

        return JWT::encode(
            $payload,
            $this->resolveSigningMaterial(),
            $this->jwtConfiguration->activeAlgorithm,
            $this->jwtConfiguration->activeKeyId,
        );
    }

    /**
     * @return array{valid: bool, reason: string|null, payload: array<string, mixed>|null}
     */
    public function verifyAccessToken(string $accessToken, ?int $currentTimestamp = null): array
    {
        if ($accessToken === '' || strlen($accessToken) > 8192) {
            return ['valid' => false, 'reason' => self::REASON_TOKEN_MALFORMED, 'payload' => null];
        }

        $configurationError = $this->validateConfiguration(false);
        if ($configurationError !== null) {
            return ['valid' => false, 'reason' => self::REASON_CONFIGURATION_INVALID, 'payload' => null];
        }

        $tokenParts = explode('.', $accessToken);
        if (count($tokenParts) !== 3) {
            return ['valid' => false, 'reason' => self::REASON_TOKEN_MALFORMED, 'payload' => null];
        }

        $headerPayload = $this->decodeTokenHeader($tokenParts[0]);
        if ($headerPayload === null) {
            return ['valid' => false, 'reason' => self::REASON_TOKEN_MALFORMED, 'payload' => null];
        }

        $tokenAlgorithm = $headerPayload['alg'] ?? null;
        $tokenKeyId = $headerPayload['kid'] ?? null;
        if (!is_string($tokenAlgorithm) || !is_string($tokenKeyId) || $tokenKeyId === '') {
            return ['valid' => false, 'reason' => self::REASON_TOKEN_MALFORMED, 'payload' => null];
        }

        if (!in_array($tokenAlgorithm, $this->jwtConfiguration->resolveAllowedAlgorithms(), true)) {
            return ['valid' => false, 'reason' => self::REASON_ALGORITHM_INVALID, 'payload' => null];
        }

        $expectedAlgorithm = $this->expectedAlgorithmForKeyId($tokenKeyId);
        if ($expectedAlgorithm === null || $tokenAlgorithm !== $expectedAlgorithm) {
            return ['valid' => false, 'reason' => self::REASON_KEY_UNKNOWN, 'payload' => null];
        }

        try {
            $verificationMaterial = $this->resolveVerificationMaterial($tokenKeyId, $tokenAlgorithm);
            $decodedPayloadObject = JWT::decode(
                $accessToken,
                new Key($verificationMaterial, $tokenAlgorithm),
            );
        } catch (ExpiredException $expiredException) {
            return ['valid' => false, 'reason' => self::REASON_TOKEN_EXPIRED, 'payload' => null];
        } catch (SignatureInvalidException $signatureException) {
            return ['valid' => false, 'reason' => self::REASON_SIGNATURE_INVALID, 'payload' => null];
        } catch (Throwable $verificationException) {
            return ['valid' => false, 'reason' => $this->mapVerificationException($verificationException), 'payload' => null];
        }

        $decodedPayload = (array) $decodedPayloadObject;
        $claimCheck = $this->validateClaims($decodedPayload, $currentTimestamp ?? time(), $tokenKeyId, $tokenAlgorithm);
        if ($claimCheck['valid'] === false) {
            return $claimCheck;
        }

        return ['valid' => true, 'reason' => null, 'payload' => $decodedPayload];
    }

    public function createTokenIdentifier(): string
    {
        return $this->keyLoader->base64UrlEncode(random_bytes(32));
    }

    public function configuration(): FMSJwt
    {
        return $this->jwtConfiguration;
    }

    private function validateConfiguration(bool $requirePrivateKey): ?string
    {
        $allowedAlgorithms = $this->jwtConfiguration->resolveAllowedAlgorithms();
        if ($allowedAlgorithms === []) {
            return 'JWT allowed algorithm list is empty.';
        }

        if (!in_array($this->jwtConfiguration->activeAlgorithm, $allowedAlgorithms, true)) {
            return 'JWT active algorithm is not allowlisted.';
        }

        if ($this->jwtConfiguration->issuer === '' || $this->jwtConfiguration->audience === '') {
            return 'JWT issuer/audience configuration is incomplete.';
        }

        if ($this->jwtConfiguration->accessTokenTtlSeconds < 60 || $this->jwtConfiguration->accessTokenTtlSeconds > 3600) {
            return 'JWT access token TTL is outside the allowed policy range.';
        }

        if ($this->jwtConfiguration->clockSkewToleranceSeconds < 0 || $this->jwtConfiguration->clockSkewToleranceSeconds > 300) {
            return 'JWT clock skew tolerance is outside the allowed policy range.';
        }

        if ($this->jwtConfiguration->activeKeyId === '') {
            return 'JWT active key id is missing.';
        }

        try {
            $publicKeyPaths = $this->jwtConfiguration->publicKeyPaths;
            if (!isset($publicKeyPaths[$this->jwtConfiguration->activeKeyId])) {
                return 'JWT active public key path is missing.';
            }

            $this->keyLoader->loadPublicKey($publicKeyPaths[$this->jwtConfiguration->activeKeyId]);

            if ($requirePrivateKey) {
                $this->resolveSigningMaterial();
            }
        } catch (Throwable $configurationException) {
            return 'JWT signing/verifying key configuration is invalid.';
        }

        return null;
    }

    private function expectedAlgorithmForKeyId(string $keyId): ?string
    {
        if ($keyId === $this->jwtConfiguration->activeKeyId) {
            return $this->jwtConfiguration->activeAlgorithm;
        }

        return null;
    }

    private function resolveSigningMaterial(): string
    {
        if ($this->jwtConfiguration->activeAlgorithm === 'HS256') {
            if (!$this->jwtConfiguration->hasSymmetricSecret()) {
                throw new RuntimeException('JWT symmetric mode is not explicitly enabled with a strong secret.');
            }

            return $this->jwtConfiguration->symmetricSecret;
        }

        if ($this->jwtConfiguration->activeAlgorithm === 'EdDSA') {
            return $this->keyLoader->base64UrlEncode(
                $this->keyLoader->loadPrivateKey($this->jwtConfiguration->privateKeyPath),
            );
        }

        return (string) file_get_contents($this->jwtConfiguration->privateKeyPath);
    }

    private function resolveVerificationMaterial(string $keyId, string $algorithm): string
    {
        if ($algorithm === 'HS256') {
            if (!$this->jwtConfiguration->hasSymmetricSecret()) {
                throw new RuntimeException('JWT symmetric mode is not explicitly enabled with a strong secret.');
            }

            return $this->jwtConfiguration->symmetricSecret;
        }

        $publicKeyPaths = $this->jwtConfiguration->publicKeyPaths;
        $binaryPublicKey = $this->keyLoader->loadPublicKey($publicKeyPaths[$keyId] ?? '');

        if ($algorithm === 'EdDSA') {
            return $this->keyLoader->base64UrlEncode($binaryPublicKey);
        }

        return (string) file_get_contents($publicKeyPaths[$keyId]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeTokenHeader(string $encodedHeader): ?array
    {
        try {
            $decodedHeader = base64_decode(strtr($encodedHeader, '-_', '+/'), true);
            if ($decodedHeader === false) {
                return null;
            }

            $headerPayload = json_decode($decodedHeader, true, 8, JSON_THROW_ON_ERROR);

            return is_array($headerPayload) ? $headerPayload : null;
        } catch (Throwable $decodingException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $decodedPayload
     * @return array{valid: bool, reason: string|null, payload: array<string, mixed>|null}
     */
    private function validateClaims(array $decodedPayload, int $currentTimestamp, string $expectedKeyId, string $expectedAlgorithm): array
    {
        $requiredStringClaims = ['iss', 'aud', 'sub', 'jti'];
        foreach ($requiredStringClaims as $requiredClaim) {
            if (!isset($decodedPayload[$requiredClaim]) || !is_string($decodedPayload[$requiredClaim]) || $decodedPayload[$requiredClaim] === '') {
                return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
            }
        }

        $requiredIntegerClaims = ['iat', 'nbf', 'exp', 'token_version'];
        foreach ($requiredIntegerClaims as $requiredClaim) {
            if (!isset($decodedPayload[$requiredClaim]) || !is_int($decodedPayload[$requiredClaim])) {
                return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
            }
        }

        if ($decodedPayload['iss'] !== $this->jwtConfiguration->issuer || $decodedPayload['aud'] !== $this->jwtConfiguration->audience) {
            return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
        }

        $clockSkew = $this->jwtConfiguration->clockSkewToleranceSeconds;
        if ($decodedPayload['iat'] > $currentTimestamp + $clockSkew) {
            return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
        }

        if ($decodedPayload['nbf'] > $currentTimestamp + $clockSkew) {
            return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
        }

        if ($decodedPayload['exp'] <= $currentTimestamp - $clockSkew) {
            return ['valid' => false, 'reason' => self::REASON_TOKEN_EXPIRED, 'payload' => null];
        }

        if (strlen($decodedPayload['sub']) > 64 || strlen($decodedPayload['jti']) > 128 || $decodedPayload['token_version'] < 1) {
            return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
        }

        if (isset($decodedPayload['scope'])) {
            if (!is_string($decodedPayload['scope']) || strlen($decodedPayload['scope']) > 512) {
                return ['valid' => false, 'reason' => self::REASON_CLAIM_INVALID, 'payload' => null];
            }
        }

        return ['valid' => true, 'reason' => null, 'payload' => $decodedPayload];
    }

    private function mapVerificationException(Throwable $verificationException): string
    {
        if ($verificationException instanceof InvalidArgumentException) {
            return self::REASON_TOKEN_MALFORMED;
        }

        $exceptionMessage = strtolower($verificationException->getMessage());
        if (str_contains($exceptionMessage, 'expired') || $verificationException instanceof ExpiredException) {
            return self::REASON_TOKEN_EXPIRED;
        }

        if (
            str_contains($exceptionMessage, 'signature')
            || str_contains($exceptionMessage, 'sodium')
            || str_contains($exceptionMessage, '64 bytes')
        ) {
            return self::REASON_SIGNATURE_INVALID;
        }

        if (str_contains($exceptionMessage, 'algorithm') || str_contains($exceptionMessage, 'key')) {
            return self::REASON_KEY_UNKNOWN;
        }

        return self::REASON_CLAIM_INVALID;
    }
}
