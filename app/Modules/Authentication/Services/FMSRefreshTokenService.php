<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Contracts\FMSRefreshTokenRepositoryInterface;
use Throwable;

/**
 * @phpstan-type RotationResult array{status: string, selector?: string|null, validator?: string|null, token_family_id?: string|null, user?: array<string, mixed>|null}
 */
final class FMSRefreshTokenService
{
    public const STATUS_ROTATED         = 'rotated';
    public const STATUS_REUSED          = 'reused';
    public const STATUS_INVALID         = 'invalid';
    public const STATUS_EXPIRED         = 'expired';
    public const STATUS_REVOKED         = 'revoked';
    public const STATUS_USER_UNAVAILABLE = 'user_unavailable';

    private int $refreshTokenTtlSeconds;

    /** @var callable */
    private $randomBytesGenerator;

    public function __construct(
        private readonly FMSRefreshTokenRepositoryInterface $refreshTokenRepository,
        int $refreshTokenTtlSeconds = 1209600,
        ?callable $randomBytesGenerator = null,
    ) {
        $this->refreshTokenTtlSeconds = $refreshTokenTtlSeconds;
        $this->randomBytesGenerator   = $randomBytesGenerator ?? static fn (int $byteLength): string => random_bytes($byteLength);
    }

    /**
     * @return array{selector: string, validator: string, presented: string}
     */
    public function generatePresentedToken(): array
    {
        $randomBytesGenerator = $this->randomBytesGenerator;
        $selector             = $this->base64UrlEncode($randomBytesGenerator(16));
        $validator            = $this->base64UrlEncode($randomBytesGenerator(32));

        return [
            'selector'  => $selector,
            'validator' => $validator,
            'presented' => $selector . '.' . $validator,
        ];
    }

    public function hashValidator(string $validatorSecret): string
    {
        return hash('sha256', $validatorSecret);
    }

    public function parsePresentedToken(string $presentedToken): ?array
    {
        if (strlen($presentedToken) > 512) {
            return null;
        }

        $tokenParts = explode('.', $presentedToken);
        if (count($tokenParts) !== 2) {
            return null;
        }

        [$selectorPart, $validatorPart] = $tokenParts;
        if ($selectorPart === '' || $validatorPart === '') {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $selectorPart)) {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $validatorPart)) {
            return null;
        }

        return ['selector' => $selectorPart, 'validator' => $validatorPart];
    }

    /**
     * @return array{selector: string, validator: string, presented: string, token_family_id: string}
     */
    public function issueToken(
        int $userIdentifier,
        string $currentTimestamp,
        string $deviceLabel = '',
        string $ipAddressHash = '',
        string $userAgentHash = '',
        ?string $tokenFamilyIdentifier = null,
    ): array {
        $generatedToken   = $this->generatePresentedToken();
        $resolvedFamilyId = $tokenFamilyIdentifier ?? $this->generateFamilyIdentifier();
        $expiresTimestamp = date('Y-m-d H:i:s', strtotime($currentTimestamp) + $this->refreshTokenTtlSeconds);

        $this->refreshTokenRepository->insertToken([
            'user_id'         => $userIdentifier,
            'token_family_id' => $resolvedFamilyId,
            'selector'        => $generatedToken['selector'],
            'validator_hash'  => $this->hashValidator($generatedToken['validator']),
            'issued_at'       => $currentTimestamp,
            'expires_at'      => $expiresTimestamp,
            'device_label'    => substr($deviceLabel, 0, 191),
            'ip_hash'         => $ipAddressHash,
            'user_agent_hash' => $userAgentHash,
            'created_at'      => $currentTimestamp,
        ]);

        return [
            'selector'         => $generatedToken['selector'],
            'validator'        => $generatedToken['validator'],
            'presented'        => $generatedToken['presented'],
            'token_family_id'  => $resolvedFamilyId,
        ];
    }

    /**
     * @return RotationResult
     */
    public function rotateToken(
        string $presentedToken,
        string $currentTimestamp,
        string $deviceLabel = '',
        string $ipAddressHash = '',
        string $userAgentHash = '',
    ): array {
        $parsedToken = $this->parsePresentedToken($presentedToken);
        if ($parsedToken === null) {
            return ['status' => self::STATUS_INVALID, 'user' => null];
        }

        $this->refreshTokenRepository->beginTransaction();

        try {
            $storedToken = $this->refreshTokenRepository->findBySelectorForUpdate($parsedToken['selector']);
            if ($storedToken === null) {
                $this->refreshTokenRepository->rollbackTransaction();

                return ['status' => self::STATUS_INVALID, 'user' => null];
            }

            if (!hash_equals((string) $storedToken['validator_hash'], $this->hashValidator($parsedToken['validator']))) {
                $this->refreshTokenRepository->rollbackTransaction();

                return ['status' => self::STATUS_INVALID, 'user' => null];
            }

            if (!empty($storedToken['revoked_at']) || !empty($storedToken['used_at'])) {
                $this->refreshTokenRepository->revokeFamily((string) $storedToken['token_family_id'], $currentTimestamp);
                $this->refreshTokenRepository->commitTransaction();

                return ['status' => self::STATUS_REUSED, 'user' => null];
            }

            if ((string) $storedToken['expires_at'] <= $currentTimestamp) {
                $this->refreshTokenRepository->rollbackTransaction();

                return ['status' => self::STATUS_EXPIRED, 'user' => null];
            }

            $activeUser = $this->refreshTokenRepository->findActiveUser((int) $storedToken['user_id']);
            if ($activeUser === null) {
                $this->refreshTokenRepository->rollbackTransaction();

                return ['status' => self::STATUS_USER_UNAVAILABLE, 'user' => null];
            }

            $generatedToken   = $this->generatePresentedToken();
            $expiresTimestamp = date('Y-m-d H:i:s', strtotime($currentTimestamp) + $this->refreshTokenTtlSeconds);

            $replacementIdentifier = $this->refreshTokenRepository->insertToken([
                'user_id'         => (int) $storedToken['user_id'],
                'token_family_id' => (string) $storedToken['token_family_id'],
                'selector'        => $generatedToken['selector'],
                'validator_hash'  => $this->hashValidator($generatedToken['validator']),
                'issued_at'       => $currentTimestamp,
                'expires_at'      => $expiresTimestamp,
                'device_label'    => substr($deviceLabel, 0, 191),
                'ip_hash'         => $ipAddressHash,
                'user_agent_hash' => $userAgentHash,
                'created_at'      => $currentTimestamp,
            ]);

            $this->refreshTokenRepository->updateToken((int) $storedToken['id'], [
                'used_at'        => $currentTimestamp,
                'revoked_at'     => $currentTimestamp,
                'replaced_by_id' => $replacementIdentifier,
            ]);

            $this->refreshTokenRepository->commitTransaction();

            return [
                'status'          => self::STATUS_ROTATED,
                'selector'        => $generatedToken['selector'],
                'validator'       => $generatedToken['validator'],
                'token_family_id' => (string) $storedToken['token_family_id'],
                'user'            => $activeUser,
            ];
        } catch (Throwable $rotationFailure) {
            $this->refreshTokenRepository->rollbackTransaction();

            throw $rotationFailure;
        }
    }

    public function revokeFamily(string $tokenFamilyIdentifier, string $currentTimestamp): void
    {
        $this->refreshTokenRepository->revokeFamily($tokenFamilyIdentifier, $currentTimestamp);
    }

    public function revokeUserTokens(int $userIdentifier, string $currentTimestamp): void
    {
        $this->refreshTokenRepository->revokeUserTokens($userIdentifier, $currentTimestamp);
    }

    private function generateFamilyIdentifier(): string
    {
        $randomBytesGenerator = $this->randomBytesGenerator;

        return $this->base64UrlEncode($randomBytesGenerator(16));
    }

    private function base64UrlEncode(string $binaryValue): string
    {
        return rtrim(strtr(base64_encode($binaryValue), '+/', '-_'), '=');
    }
}
