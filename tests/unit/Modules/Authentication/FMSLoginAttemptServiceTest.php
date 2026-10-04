<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Contracts\FMSAuthenticationRepositoryInterface;
use App\Modules\Authentication\Services\FMSLoginAttemptService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSLoginAttemptServiceTest extends CIUnitTestCase
{
    public function testSuccessfulLoginResetsFailureCountAndReturnsAuthenticated(): void
    {
        $userRecord = [
            'id'                  => 7,
            'username_normalized' => 'operator@example.com',
            'email_normalized'    => 'operator@example.com',
            'password_hash'       => password_hash('Correct-Password-1', PASSWORD_DEFAULT),
            'status'              => 'active',
            'registration_source' => 'admin',
            'email_verified_at'   => null,
            'failed_login_count'  => 2,
            'locked_until'        => null,
        ];
        $fakeRepository = new FakeAuthenticationRepository($userRecord);
        $loginService   = new FMSLoginAttemptService($fakeRepository);

        $loginResult = $loginService->attemptLogin(
            '  Operator@Example.com ',
            'Correct-Password-1',
            '10.0.0.1',
            ['admin_created_email_verification_required' => false, 'public_email_verification_required' => true],
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSLoginAttemptService::STATUS_AUTHENTICATED, $loginResult['status']);
        $this->assertSame(0, $fakeRepository->updatedAttributes['failed_login_count']);
        $this->assertSame('2026-09-29 10:00:00', $fakeRepository->updatedAttributes['last_login_at']);
    }

    public function testUnknownIdentifierReturnsInvalidCredentialsWithoutEnumeration(): void
    {
        $fakeRepository = new FakeAuthenticationRepository(null);
        $loginService   = new FMSLoginAttemptService($fakeRepository);

        $missingResult = $loginService->attemptLogin(
            'ghost@example.com',
            'anything',
            '10.0.0.2',
            ['admin_created_email_verification_required' => false, 'public_email_verification_required' => true],
            '2026-09-29 10:00:00',
        );
        $knownUserRecord = [
            'id'                  => 8,
            'username_normalized' => 'known@example.com',
            'password_hash'       => password_hash('Right-Password-1', PASSWORD_DEFAULT),
            'status'              => 'active',
            'registration_source' => 'admin',
            'email_verified_at'   => null,
            'failed_login_count'  => 0,
            'locked_until'        => null,
        ];
        $knownLoginService   = new FMSLoginAttemptService(new FakeAuthenticationRepository($knownUserRecord));
        $wrongPasswordResult = $knownLoginService->attemptLogin(
            'known@example.com',
            'wrong',
            '10.0.0.2',
            ['admin_created_email_verification_required' => false, 'public_email_verification_required' => true],
            '2026-09-29 10:01:00',
        );

        $this->assertSame(FMSLoginAttemptService::STATUS_INVALID_CREDENTIALS, $missingResult['status']);
        $this->assertSame(FMSLoginAttemptService::STATUS_INVALID_CREDENTIALS, $wrongPasswordResult['status']);
        $this->assertSame(array_keys($missingResult), array_keys($wrongPasswordResult));
    }

    public function testPublicUserRequiresVerifiedEmailWhenPolicyEnabled(): void
    {
        $userRecord = [
            'id'                  => 9,
            'username_normalized' => 'member',
            'password_hash'       => password_hash('Secret-123', PASSWORD_DEFAULT),
            'status'              => 'active',
            'registration_source' => 'public',
            'email_verified_at'   => null,
            'failed_login_count'  => 0,
            'locked_until'        => null,
        ];
        $loginService = new FMSLoginAttemptService(new FakeAuthenticationRepository($userRecord));

        $loginResult = $loginService->attemptLogin(
            'member',
            'Secret-123',
            '10.0.0.3',
            ['admin_created_email_verification_required' => false, 'public_email_verification_required' => true],
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSLoginAttemptService::STATUS_EMAIL_UNVERIFIED, $loginResult['status']);
    }

    public function testAdminCreatedUserNeverRequiresEmailVerification(): void
    {
        $userRecord = [
            'id'                  => 10,
            'username_normalized' => 'admin-staff',
            'email_normalized'    => 'staff@example.com',
            'password_hash'       => password_hash('Secret-123', PASSWORD_DEFAULT),
            'status'              => 'active',
            'registration_source' => 'admin',
            'email_verified_at'   => null,
            'failed_login_count'  => 0,
            'locked_until'        => null,
        ];
        $loginService = new FMSLoginAttemptService(new FakeAuthenticationRepository($userRecord));

        $loginResult = $loginService->attemptLogin(
            'admin-staff',
            'Secret-123',
            '10.0.0.5',
            ['admin_created_email_verification_required' => true, 'public_email_verification_required' => true],
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSLoginAttemptService::STATUS_AUTHENTICATED, $loginResult['status']);
    }

    public function testSuperAdministratorPublicUserBypassesEmailVerification(): void
    {
        $userRecord = [
            'id'                  => 12,
            'username_normalized' => 'owner',
            'email_normalized'    => 'owner@example.com',
            'password_hash'       => password_hash('Secret-123', PASSWORD_DEFAULT),
            'status'              => 'active',
            'registration_source' => 'public',
            'email_verified_at'   => null,
            'is_super_admin'      => true,
            'failed_login_count'  => 0,
            'locked_until'        => null,
        ];
        $loginService = new FMSLoginAttemptService(new FakeAuthenticationRepository($userRecord));

        $loginResult = $loginService->attemptLogin(
            'owner',
            'Secret-123',
            '10.0.0.6',
            ['admin_created_email_verification_required' => true, 'public_email_verification_required' => true],
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSLoginAttemptService::STATUS_AUTHENTICATED, $loginResult['status']);
    }

    public function testLockedAccountReturnsLockedWithoutPasswordCheck(): void
    {
        $userRecord = [
            'id'                  => 11,
            'username_normalized' => 'locked-user',
            'password_hash'       => password_hash('Secret-123', PASSWORD_DEFAULT),
            'status'              => 'active',
            'registration_source' => 'admin',
            'email_verified_at'   => '2026-09-01 00:00:00',
            'failed_login_count'  => 5,
            'locked_until'        => '2026-09-29 11:00:00',
        ];
        $loginService = new FMSLoginAttemptService(new FakeAuthenticationRepository($userRecord));

        $loginResult = $loginService->attemptLogin(
            'locked-user',
            'Secret-123',
            '10.0.0.4',
            ['admin_created_email_verification_required' => false, 'public_email_verification_required' => false],
            '2026-09-29 10:00:00',
        );

        $this->assertSame(FMSLoginAttemptService::STATUS_ACCOUNT_LOCKED, $loginResult['status']);
    }
}

final class FakeAuthenticationRepository implements FMSAuthenticationRepositoryInterface
{
    public array $updatedAttributes = [];

    /** @param array<string, mixed>|null $userRecord */
    public function __construct(private ?array $userRecord)
    {
    }

    public function findUserByNormalizedIdentifier(string $normalizedIdentifier): ?array
    {
        return $this->userRecord;
    }

    public function updateUser(int $userIdentifier, array $attributes): bool
    {
        $this->updatedAttributes = $attributes;

        return true;
    }

    public function recordAttempt(string $identifierHash, string $ipHash, string $resultCode, string $occurredAt): void
    {
    }

    public function countRecentFailures(string $identifierHash, string $ipHash, string $since): int
    {
        return 0;
    }

    public function isSuperAdministrator(int $userIdentifier): bool
    {
        return ($this->userRecord['id'] ?? 0) === $userIdentifier && ($this->userRecord['is_super_admin'] ?? false) === true;
    }
}
