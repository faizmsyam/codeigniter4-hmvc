<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Contracts\FMSAuthenticationLifecycleRepositoryInterface;
use App\Modules\Authentication\Services\FMSAuthenticationLifecycleService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSAuthenticationLifecycleServiceTest extends CIUnitTestCase
{
    public function testRegistrationDisabledDoesNotPersistUser(): void
    {
        $repository = new FakeAuthenticationLifecycleRepository();
        $repository->settings['public_registration_enabled'] = false;
        $service = new FMSAuthenticationLifecycleService($repository);

        $registrationResult = $service->register([
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'Valid-Password-123!',
            'full_name' => 'Member Example',
        ], '2026-09-29 10:00:00');

        $this->assertSame(FMSAuthenticationLifecycleService::STATUS_DISABLED, $registrationResult['status']);
        $this->assertSame([], $repository->createdUsers);
    }

    public function testRegistrationCreatesPendingPublicUserAndStoresOnlyHashedVerificationToken(): void
    {
        $repository = new FakeAuthenticationLifecycleRepository();
        $service = new FMSAuthenticationLifecycleService(
            $repository,
            static fn (int $byteLength): string => str_repeat("\x42", $byteLength),
        );

        $registrationResult = $service->register([
            'username' => ' Member.User ',
            'email' => ' Member@Example.com ',
            'password' => 'Valid-Password-123!',
            'full_name' => ' Member Example ',
        ], '2026-09-29 10:00:00');

        $this->assertSame(FMSAuthenticationLifecycleService::STATUS_ACCEPTED, $registrationResult['status']);
        $this->assertSame('member.user', $repository->createdUsers[0]['username_normalized']);
        $this->assertSame('member@example.com', $repository->createdUsers[0]['email_normalized']);
        $this->assertSame('pending', $repository->createdUsers[0]['status']);
        $this->assertSame('public', $repository->createdUsers[0]['registration_source']);
        $this->assertTrue(password_verify('Valid-Password-123!', $repository->createdUsers[0]['password_hash']));
        $this->assertArrayNotHasKey('validator', $repository->createdTokens[0]);
        $this->assertArrayHasKey('validator_hash', $repository->createdTokens[0]);
        $this->assertNotSame($registrationResult['verification_token']['validator'], $repository->createdTokens[0]['validator_hash']);
    }

    public function testDuplicateRegistrationReturnsSameAcceptedShapeWithoutCreatingToken(): void
    {
        $repository = new FakeAuthenticationLifecycleRepository();
        $repository->existingUser = ['id' => 22, 'email_verified_at' => null];
        $service = new FMSAuthenticationLifecycleService($repository);

        $registrationResult = $service->register([
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'Valid-Password-123!',
            'full_name' => 'Member Example',
        ], '2026-09-29 10:00:00');

        $this->assertSame(FMSAuthenticationLifecycleService::STATUS_ACCEPTED, $registrationResult['status']);
        $this->assertArrayNotHasKey('verification_token', $registrationResult);
        $this->assertSame([], $repository->createdUsers);
        $this->assertSame([], $repository->createdTokens);
    }

    public function testVerificationTokenCanOnlyBeConsumedOnceAndActivatesPendingUser(): void
    {
        $repository = new FakeAuthenticationLifecycleRepository();
        $repository->verificationToken = [
            'id' => 44,
            'user_id' => 22,
            'selector' => 'selector-one',
            'validator_hash' => hash('sha256', 'validator-one'),
            'purpose' => 'verify',
            'expires_at' => '2026-09-30 10:00:00',
            'used_at' => null,
            'revoked_at' => null,
        ];
        $repository->verificationUser = ['id' => 22, 'status' => 'pending', 'email_verified_at' => null];
        $service = new FMSAuthenticationLifecycleService($repository);

        $firstResult = $service->verifyEmail('selector-one', 'validator-one', '2026-09-29 10:00:00');
        $secondResult = $service->verifyEmail('selector-one', 'validator-one', '2026-09-29 10:01:00');

        $this->assertSame(FMSAuthenticationLifecycleService::STATUS_VERIFIED, $firstResult['status']);
        $this->assertSame('2026-09-29 10:00:00', $repository->updatedVerificationToken['used_at']);
        $this->assertSame('active', $repository->updatedUser['status']);
        $this->assertSame('2026-09-29 10:00:00', $repository->updatedUser['email_verified_at']);
        $this->assertSame(FMSAuthenticationLifecycleService::STATUS_INVALID_TOKEN, $secondResult['status']);
    }

    public function testResendIsAntiEnumerationAndHonorsCooldown(): void
    {
        $missingRepository = new FakeAuthenticationLifecycleRepository();
        $missingService = new FMSAuthenticationLifecycleService($missingRepository);
        $knownRepository = new FakeAuthenticationLifecycleRepository();
        $knownRepository->existingUser = ['id' => 22, 'email_verified_at' => null, 'status' => 'pending'];
        $knownRepository->latestTokenCreatedAt = '2026-09-29 09:59:30';
        $knownService = new FMSAuthenticationLifecycleService($knownRepository);

        $missingResult = $missingService->resendVerification('missing@example.com', '2026-09-29 10:00:00');
        $knownResult = $knownService->resendVerification('member@example.com', '2026-09-29 10:00:00');

        $this->assertSame(['status' => FMSAuthenticationLifecycleService::STATUS_ACCEPTED], $missingResult);
        $this->assertSame($missingResult, $knownResult);
        $this->assertSame([], $knownRepository->createdTokens);
    }
}

final class FakeAuthenticationLifecycleRepository implements FMSAuthenticationLifecycleRepositoryInterface
{
    public array $settings = [
        'public_registration_enabled' => true,
        'public_email_verification_required' => true,
        'verification_ttl_minutes' => 1440,
        'resend_cooldown_seconds' => 120,
    ];
    public ?array $existingUser = null;
    public ?array $verificationToken = null;
    public ?array $verificationUser = null;
    public ?string $latestTokenCreatedAt = null;
    public array $createdUsers = [];
    public array $createdTokens = [];
    public array $updatedVerificationToken = [];
    public array $updatedUser = [];
    private bool $tokenConsumed = false;

    public function authenticationSettings(): array
    {
        return $this->settings;
    }

    public function findUserByIdentity(string $normalizedUsername, string $normalizedEmail): ?array
    {
        return $this->existingUser;
    }

    public function findUserByNormalizedIdentifier(string $normalizedIdentifier): ?array
    {
        return $this->existingUser;
    }

    public function createUser(array $attributes): int
    {
        $this->createdUsers[] = $attributes;

        return 22;
    }

    public function findVerificationTokenForUpdate(string $selector): ?array
    {
        if ($this->tokenConsumed || $this->verificationToken === null || $this->verificationToken['selector'] !== $selector) {
            return null;
        }

        return $this->verificationToken;
    }

    public function findUserById(int $userIdentifier): ?array
    {
        return $this->verificationUser;
    }

    public function createVerificationToken(array $attributes): int
    {
        $this->createdTokens[] = $attributes;

        return 44;
    }

    public function updateVerificationToken(int $tokenIdentifier, array $attributes): bool
    {
        $this->updatedVerificationToken = $attributes;
        $this->tokenConsumed = true;

        return true;
    }

    public function revokeUnusedVerificationTokens(int $userIdentifier, string $revokedAt): void
    {
    }

    public function latestVerificationTokenCreatedAt(int $userIdentifier): ?string
    {
        return $this->latestTokenCreatedAt;
    }

    public function updateUser(int $userIdentifier, array $attributes): bool
    {
        $this->updatedUser = $attributes;

        return true;
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
}
