<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Contracts\FMSApiKeyRepositoryInterface;
use App\Modules\Authentication\Services\FMSApiKeyService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSApiKeyServiceTest extends CIUnitTestCase
{
    public function testGeneratedKeyCanBeVerifiedForRequiredScope(): void
    {
        $repository = new FakeApiKeyRepository();
        $service    = new FMSApiKeyService(
            $repository,
            'test-server-pepper-that-is-not-empty',
            static fn (): string => str_repeat("\x11", 32),
            static fn (): string => 'ABCDEF1234567890',
        );
        $generatedKey = $service->generateKey();
        $repository->record = [
            'id'          => 21,
            'key_id'      => $generatedKey['key_identifier'],
            'secret_hash' => $generatedKey['secret_hash'],
            'scopes_json' => json_encode(['orders.read', 'orders.write'], JSON_THROW_ON_ERROR),
            'environment' => 'testing',
            'expires_at'  => '2026-10-29 00:00:00',
            'revoked_at'  => null,
        ];

        $verificationResult = $service->verifyKey(
            $generatedKey['presented'],
            ['orders.read'],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSApiKeyService::STATUS_VERIFIED, $verificationResult['status']);
        $this->assertSame('ABCDEF1234567890', $generatedKey['key_identifier']);
        $this->assertStringStartsWith('fms_ABCDEF1234567890_', $generatedKey['presented']);
    }

    public function testSecretMutationIsRejectedWithoutRevealingKeyExistence(): void
    {
        $repository = new FakeApiKeyRepository();
        $service    = new FMSApiKeyService(
            $repository,
            'test-server-pepper-that-is-not-empty',
            static fn (): string => str_repeat("\x22", 32),
            static fn (): string => 'KEYIDENTIFIER123',
        );
        $generatedKey      = $service->generateKey();
        $repository->record = [
            'id'          => 22,
            'key_id'      => $generatedKey['key_identifier'],
            'secret_hash' => $generatedKey['secret_hash'],
            'scopes_json' => '["orders.read"]',
            'environment' => 'testing',
            'expires_at'  => null,
            'revoked_at'  => null,
        ];
        $mutatedKey = substr($generatedKey['presented'], 0, -1) . 'A';

        $verificationResult = $service->verifyKey(
            $mutatedKey,
            ['orders.read'],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSApiKeyService::STATUS_UNKNOWN, $verificationResult['status']);
    }

    public function testMissingRequiredScopeIsDenied(): void
    {
        $repository = new FakeApiKeyRepository();
        $service    = new FMSApiKeyService(
            $repository,
            'test-server-pepper-that-is-not-empty',
            static fn (): string => str_repeat("\x33", 32),
            static fn (): string => 'KEYIDENTIFIER456',
        );
        $generatedKey       = $service->generateKey();
        $repository->record = [
            'id'          => 23,
            'key_id'      => $generatedKey['key_identifier'],
            'secret_hash' => $generatedKey['secret_hash'],
            'scopes_json' => '["orders.read"]',
            'environment' => 'testing',
            'expires_at'  => null,
            'revoked_at'  => null,
        ];

        $verificationResult = $service->verifyKey(
            $generatedKey['presented'],
            ['orders.delete'],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSApiKeyService::STATUS_SCOPE_DENIED, $verificationResult['status']);
    }
}

final class FakeApiKeyRepository implements FMSApiKeyRepositoryInterface
{
    public ?array $record = null;

    public function create(array $attributes): int
    {
        $this->record = $attributes;

        return 1;
    }

    public function findByKeyIdentifier(string $keyIdentifier): ?array
    {
        return $this->record;
    }

    public function update(int $apiKeyIdentifier, array $attributes): bool
    {
        return true;
    }
}
