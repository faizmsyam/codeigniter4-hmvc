<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Contracts\FMSBasicAuthClientRepositoryInterface;
use App\Modules\Authentication\Services\FMSBasicAuthService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSBasicAuthServiceTest extends CIUnitTestCase
{
    public function testValidCredentialsAuthenticateAndResetFailures(): void
    {
        $repository = new FakeBasicAuthClientRepository([
            'id'              => 31,
            'username'        => 'IntegrationClient',
            'password_hash'   => password_hash('Client-Secret-123', PASSWORD_DEFAULT),
            'scopes_json'     => '["invoices.read","invoices.write"]',
            'environment'     => 'testing',
            'expires_at'      => null,
            'locked_until'    => null,
            'failed_attempts' => 2,
            'revoked_at'      => null,
        ]);
        $service = new FMSBasicAuthService($repository);

        $result = $service->verifyCredentials(
            'Basic ' . base64_encode('IntegrationClient:Client-Secret-123'),
            ['invoices.read'],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSBasicAuthService::STATUS_AUTHENTICATED, $result['status']);
        $this->assertSame('integrationclient', $repository->lastNormalizedUsername);
        $this->assertSame(0, $repository->lastUpdate['failed_attempts']);
    }

    public function testMalformedHeaderIsRejected(): void
    {
        $repository = new FakeBasicAuthClientRepository(null);
        $service    = new FMSBasicAuthService($repository);

        $result = $service->verifyCredentials(
            'Bearer anything',
            [],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSBasicAuthService::STATUS_MALFORMED, $result['status']);
    }

    public function testWrongPasswordIncrementsFailureCounter(): void
    {
        $repository = new FakeBasicAuthClientRepository([
            'id'              => 32,
            'username'        => 'batch-client',
            'password_hash'   => password_hash('Right-Secret', PASSWORD_DEFAULT),
            'scopes_json'     => '[]',
            'environment'     => 'testing',
            'expires_at'      => null,
            'locked_until'    => null,
            'failed_attempts' => 3,
            'revoked_at'      => null,
        ]);
        $service = new FMSBasicAuthService($repository);

        $result = $service->verifyCredentials(
            'Basic ' . base64_encode('batch-client:Wrong-Secret'),
            [],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSBasicAuthService::STATUS_INVALID_CREDENTIALS, $result['status']);
        $this->assertSame(4, $repository->lastUpdate['failed_attempts']);
    }

    public function testScopeMismatchIsDenied(): void
    {
        $repository = new FakeBasicAuthClientRepository([
            'id'              => 33,
            'username'        => 'reader',
            'password_hash'   => password_hash('Reader-Secret', PASSWORD_DEFAULT),
            'scopes_json'     => '["invoices.read"]',
            'environment'     => 'testing',
            'expires_at'      => null,
            'locked_until'    => null,
            'failed_attempts' => 0,
            'revoked_at'      => null,
        ]);
        $service = new FMSBasicAuthService($repository);

        $result = $service->verifyCredentials(
            'Basic ' . base64_encode('reader:Reader-Secret'),
            ['invoices.write'],
            'testing',
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSBasicAuthService::STATUS_SCOPE_DENIED, $result['status']);
    }
}

final class FakeBasicAuthClientRepository implements FMSBasicAuthClientRepositoryInterface
{
    public string $lastNormalizedUsername = '';
    public array $lastUpdate = [];

    /** @param array<string, mixed>|null $record */
    public function __construct(private ?array $record)
    {
    }

    public function findByNormalizedUsername(string $normalizedUsername): ?array
    {
        $this->lastNormalizedUsername = $normalizedUsername;

        return $this->record;
    }

    public function update(int $clientIdentifier, array $attributes): bool
    {
        $this->lastUpdate = $attributes;

        return true;
    }
}
