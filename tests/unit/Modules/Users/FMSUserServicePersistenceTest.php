<?php

namespace Tests\Unit\Modules\Users;

use App\Modules\Users\Contracts\FMSUserRepositoryInterface;
use App\Modules\Users\Services\FMSUserService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSUserServicePersistenceTest extends CIUnitTestCase
{
    public function testCrudLifecycleAndAuthenticationMutations(): void
    {
        $repository = new FMSInMemoryUserRepository();
        $userService = new FMSUserService($repository);

        $userIdentifier = $userService->create([
            'username' => 'operator.one',
            'email' => 'Operator@One.Example',
            'password' => 'Strong-Passphrase-2026',
            'full_name' => 'Operator One',
            'status' => 'active',
            'registration_source' => 'admin',
        ], 7);

        $this->assertSame(1, $userIdentifier);
        $this->assertSame('operator.one', $repository->users[1]['username_normalized']);
        $this->assertTrue(password_verify('Strong-Passphrase-2026', $repository->users[1]['password_hash']));

        $this->assertTrue($userService->updateByUuid($repository->users[1]['uuid'], [
            'email' => 'new@example.test',
        ], 8));
        $this->assertNull($repository->users[1]['email_verified_at']);
        $this->assertSame(2, $repository->users[1]['token_version']);
        $this->assertSame(2, $repository->users[1]['session_version']);

        $this->assertTrue($userService->verifyEmailByUuid($repository->users[1]['uuid'], 8));
        $this->assertNotNull($repository->users[1]['email_verified_at']);
        $this->assertTrue($userService->unverifyEmailByUuid($repository->users[1]['uuid'], 8));
        $this->assertNull($repository->users[1]['email_verified_at']);

        $repository->users[1]['failed_login_count'] = 6;
        $repository->users[1]['locked_until'] = '2026-12-31 00:00:00';
        $this->assertTrue($userService->unlockByUuid($repository->users[1]['uuid'], 8));
        $this->assertSame(0, $repository->users[1]['failed_login_count']);
        $this->assertNull($repository->users[1]['locked_until']);

        $this->assertTrue($userService->resetPasswordByUuid(
            $repository->users[1]['uuid'],
            'Another-Strong-Passphrase-2026',
            8,
        ));
        $this->assertTrue(password_verify('Another-Strong-Passphrase-2026', $repository->users[1]['password_hash']));
        $this->assertSame(1, $repository->users[1]['must_change_password']);

        $tokenVersionBeforeStatusChange = $repository->users[1]['token_version'];
        $this->assertTrue($userService->changeStatusByUuid($repository->users[1]['uuid'], 'suspended', 8));
        $this->assertSame('suspended', $repository->users[1]['status']);
        $this->assertSame($tokenVersionBeforeStatusChange + 1, $repository->users[1]['token_version']);

        $tokenVersionBeforeDeletion = $repository->users[1]['token_version'];
        $this->assertTrue($userService->deleteByUuid($repository->users[1]['uuid'], 8));
        $this->assertNotNull($repository->users[1]['deleted_at']);
        $this->assertSame($tokenVersionBeforeDeletion + 1, $repository->users[1]['token_version']);
        $this->assertTrue($userService->restoreByUuid($repository->users[1]['uuid'], 8));
        $this->assertNull($repository->users[1]['deleted_at']);
    }

    public function testSessionAndGroupFoundationsAreUuidScopedAndVersioned(): void
    {
        $repository = FMSInMemoryUserRepository::withUser();
        $userService = new FMSUserService($repository);
        $userUuid = $repository->users[1]['uuid'];

        $repository->sessions[1] = [
            ['session_uuid' => '11111111-1111-4111-8111-111111111111', 'revoked_at' => null],
            ['session_uuid' => '22222222-2222-4222-8222-222222222222', 'revoked_at' => null],
        ];

        $this->assertCount(2, $userService->sessionsByUuid($userUuid));
        $this->assertSame(1, $userService->revokeSessionsByUuid(
            $userUuid,
            9,
            '11111111-1111-4111-8111-111111111111',
        ));
        $this->assertSame(2, $repository->users[1]['session_version']);

        $userService->replaceGroupsByUuid($userUuid, [5, '2', 5], 9);
        $this->assertSame([2, 5], $userService->groupsByUuid($userUuid));
        $this->assertSame(2, $repository->users[1]['token_version']);
    }

    public function testListOutputNeverExposesPasswordHash(): void
    {
        $repository = FMSInMemoryUserRepository::withUser();
        $result = (new FMSUserService($repository))->list(['page' => 1, 'per_page' => 20]);

        $this->assertSame(1, $result['total']);
        $this->assertArrayNotHasKey('password_hash', $result['users'][0]);
    }
}

final class FMSInMemoryUserRepository implements FMSUserRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $users = [];

    /** @var array<int, list<array<string, mixed>>> */
    public array $sessions = [];

    /** @var array<int, list<int>> */
    public array $groups = [];

    public static function withUser(): self
    {
        $repository = new self();
        $repository->users[1] = [
            'id' => 1,
            'uuid' => '11111111-1111-4111-8111-111111111111',
            'username' => 'operator',
            'username_normalized' => 'operator',
            'email' => 'operator@example.test',
            'email_normalized' => 'operator@example.test',
            'password_hash' => password_hash('Initial-Strong-Pass-2026', PASSWORD_DEFAULT),
            'full_name' => 'Operator',
            'status' => 'active',
            'registration_source' => 'admin',
            'email_verified_at' => null,
            'failed_login_count' => 0,
            'locked_until' => null,
            'token_version' => 1,
            'session_version' => 1,
            'deleted_at' => null,
        ];

        return $repository;
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $users = [];
        foreach ($this->users as $user) {
            if ($user['deleted_at'] !== null && ($filters['include_deleted'] ?? false) !== true) {
                continue;
            }
            if (($filters['status'] ?? '') !== '' && $user['status'] !== $filters['status']) {
                continue;
            }
            unset($user['password_hash']);
            $users[] = $user;
        }

        return ['users' => $users, 'total' => count($users), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $userIdentifier, bool $includeDeleted = false): ?array
    {
        $user = $this->users[$userIdentifier] ?? null;
        if ($user === null || (! $includeDeleted && $user['deleted_at'] !== null)) {
            return null;
        }

        return $user;
    }

    public function findByUuid(string $userUuid, bool $includeDeleted = false): ?array
    {
        foreach ($this->users as $user) {
            if ($user['uuid'] === $userUuid && ($includeDeleted || $user['deleted_at'] === null)) {
                return $user;
            }
        }

        return null;
    }

    public function findByIdentity(string $normalizedUsername, string $normalizedEmail, ?int $excludeUserIdentifier = null): ?array
    {
        foreach ($this->users as $user) {
            if ($excludeUserIdentifier !== null && $user['id'] === $excludeUserIdentifier) {
                continue;
            }
            if ($user['username_normalized'] === $normalizedUsername || $user['email_normalized'] === $normalizedEmail) {
                return $user;
            }
        }

        return null;
    }

    public function create(array $userData): int
    {
        $userIdentifier = count($this->users) + 1;
        $this->users[$userIdentifier] = array_merge([
            'id' => $userIdentifier,
            'uuid' => sprintf('11111111-1111-4111-8111-%012d', $userIdentifier),
            'token_version' => 1,
            'session_version' => 1,
            'email_verified_at' => null,
            'deleted_at' => null,
        ], $userData);

        return $userIdentifier;
    }

    public function update(int $userIdentifier, array $userData): bool
    {
        if (! isset($this->users[$userIdentifier])) {
            return false;
        }
        $this->users[$userIdentifier] = array_merge($this->users[$userIdentifier], $userData);

        return true;
    }

    public function delete(int $userIdentifier, int $actorIdentifier): bool
    {
        if (! isset($this->users[$userIdentifier])) {
            return false;
        }
        $this->users[$userIdentifier]['deleted_at'] = '2026-09-29 00:00:00';
        $this->users[$userIdentifier]['deleted_by'] = $actorIdentifier;

        return true;
    }

    public function restore(int $userIdentifier, int $actorIdentifier): bool
    {
        if (! isset($this->users[$userIdentifier])) {
            return false;
        }
        $this->users[$userIdentifier]['deleted_at'] = null;
        $this->users[$userIdentifier]['deleted_by'] = null;

        return true;
    }

    public function sessions(int $userIdentifier): array
    {
        return $this->sessions[$userIdentifier] ?? [];
    }

    public function revokeSessions(int $userIdentifier, ?string $sessionUuid = null): int
    {
        $revoked = 0;
        foreach ($this->sessions[$userIdentifier] ?? [] as &$session) {
            if ($session['revoked_at'] !== null || ($sessionUuid !== null && $session['session_uuid'] !== $sessionUuid)) {
                continue;
            }
            $session['revoked_at'] = '2026-09-29 00:00:00';
            $revoked++;
        }
        unset($session);

        return $revoked;
    }

    public function groupIdentifiers(int $userIdentifier): array
    {
        return $this->groups[$userIdentifier] ?? [];
    }

    public function replaceGroups(int $userIdentifier, array $groupIdentifiers, int $actorIdentifier): void
    {
        $this->groups[$userIdentifier] = $groupIdentifiers;
    }
}
