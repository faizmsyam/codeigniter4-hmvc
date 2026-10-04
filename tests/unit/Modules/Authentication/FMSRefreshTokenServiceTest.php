<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Contracts\FMSRefreshTokenRepositoryInterface;
use App\Modules\Authentication\Services\FMSRefreshTokenService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSRefreshTokenServiceTest extends CIUnitTestCase
{
    public function testRotationIssuesNewTokenAndRevokesOldToken(): void
    {
        $repository = new FakeRefreshTokenRepository([
            'id'              => 41,
            'user_id'         => 100,
            'token_family_id' => 'family-01',
            'selector'        => 'selector-01',
            'validator_hash'  => hash('sha256', 'validator-01'),
            'issued_at'       => '2026-09-29 09:00:00',
            'expires_at'      => '2026-10-13 09:00:00',
            'used_at'         => null,
            'revoked_at'      => null,
        ]);
        $service = new FMSRefreshTokenService(
            $repository,
            1209600,
            static fn (int $byteLength): string => str_repeat("\x44", $byteLength),
        );

        $rotationResult = $service->rotateToken(
            'selector-01.validator-01',
            '2026-09-29 10:00:00',
            'office-tablet',
            'ip-hash-01',
            'agent-hash-01',
        );

        $this->assertSame(FMSRefreshTokenService::STATUS_ROTATED, $rotationResult['status']);
        $this->assertNotEmpty($rotationResult['selector'] ?? null);
        $this->assertNotSame('selector-01', $rotationResult['selector'] ?? null);
        $this->assertSame('family-01', $rotationResult['token_family_id'] ?? null);
        $this->assertSame('2026-09-29 10:00:00', $repository->updatedAttributes['used_at'] ?? null);
        $this->assertSame('2026-09-29 10:00:00', $repository->updatedAttributes['revoked_at'] ?? null);
    }

    public function testReuseOfRotatedTokenRevokesWholeFamily(): void
    {
        $repository = new FakeRefreshTokenRepository([
            'id'              => 42,
            'user_id'         => 101,
            'token_family_id' => 'family-02',
            'selector'        => 'selector-02',
            'validator_hash'  => hash('sha256', 'validator-02'),
            'issued_at'       => '2026-09-29 08:00:00',
            'expires_at'      => '2026-10-13 08:00:00',
            'used_at'         => '2026-09-29 09:00:00',
            'revoked_at'      => '2026-09-29 09:00:00',
        ]);
        $service = new FMSRefreshTokenService($repository, 1209600);

        $rotationResult = $service->rotateToken('selector-02.validator-02', '2026-09-29 10:00:00');

        $this->assertSame(FMSRefreshTokenService::STATUS_REUSED, $rotationResult['status']);
        $this->assertSame('family-02', $repository->revokedFamilyIdentifier);
    }

    public function testExpiredTokenIsRejectedWithoutRotation(): void
    {
        $repository = new FakeRefreshTokenRepository([
            'id'              => 43,
            'user_id'         => 102,
            'token_family_id' => 'family-03',
            'selector'        => 'selector-03',
            'validator_hash'  => hash('sha256', 'validator-03'),
            'issued_at'       => '2026-09-01 08:00:00',
            'expires_at'      => '2026-09-15 08:00:00',
            'used_at'         => null,
            'revoked_at'      => null,
        ]);
        $service = new FMSRefreshTokenService($repository, 1209600);

        $rotationResult = $service->rotateToken('selector-03.validator-03', '2026-09-29 10:00:00');

        $this->assertSame(FMSRefreshTokenService::STATUS_EXPIRED, $rotationResult['status']);
        $this->assertSame([], $repository->insertedAttributes);
    }

    public function testValidatorMismatchIsRejected(): void
    {
        $repository = new FakeRefreshTokenRepository([
            'id'              => 44,
            'user_id'         => 103,
            'token_family_id' => 'family-04',
            'selector'        => 'selector-04',
            'validator_hash'  => hash('sha256', 'correct-validator'),
            'issued_at'       => '2026-09-29 09:00:00',
            'expires_at'      => '2026-10-13 09:00:00',
            'used_at'         => null,
            'revoked_at'      => null,
        ]);
        $service = new FMSRefreshTokenService($repository, 1209600);

        $rotationResult = $service->rotateToken('selector-04.wrong-validator', '2026-09-29 10:00:00');

        $this->assertSame(FMSRefreshTokenService::STATUS_INVALID, $rotationResult['status']);
    }
}

final class FakeRefreshTokenRepository implements FMSRefreshTokenRepositoryInterface
{
    public array $updatedAttributes = [];
    public array $insertedAttributes = [];
    public ?string $revokedFamilyIdentifier = null;
    public ?string $revokedUserTimestamp = null;
    public int $nextIdentifier = 99;

    /** @param array<string, mixed>|null $tokenRow */
    public function __construct(private ?array $tokenRow)
    {
    }

    public function findBySelectorForUpdate(string $selector): ?array
    {
        if ($this->tokenRow === null || ($this->tokenRow['selector'] ?? null) !== $selector) {
            return null;
        }

        return $this->tokenRow;
    }

    public function beginTransaction(): void
    {
    }

    public function commitTransaction(): void
    {
    }

    public function rollbackTransaction(): void
    {
    }

    public function insertToken(array $attributes): int
    {
        $this->insertedAttributes = $attributes;

        return $this->nextIdentifier;
    }

    public function updateToken(int $tokenIdentifier, array $attributes): bool
    {
        $this->updatedAttributes = $attributes;

        return true;
    }

    public function revokeFamily(string $tokenFamilyIdentifier, string $revokedAt): void
    {
        $this->revokedFamilyIdentifier = $tokenFamilyIdentifier;
    }

    public function revokeUserTokens(int $userIdentifier, string $currentTimestamp): void
    {
        $this->revokedUserTimestamp = $currentTimestamp;
    }

    public function findActiveUser(int $userIdentifier): ?array
    {
        return ['id' => $userIdentifier, 'uuid' => 'user-uuid-01'];
    }
}
