<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Contracts\FMSUserRepositoryInterface;
use App\Modules\Users\Validation\FMSUserValidation;
use CodeIgniter\Database\Exceptions\DatabaseException;
use InvalidArgumentException;

final class FMSUserService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_LOCKED = 'locked';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_PUBLIC = 'public';
    public const SOURCE_IMPORT = 'import';
    public const MAXIMUM_PER_PAGE = 100;

    /** @var list<string> */
    private array $allowedStatuses = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_LOCKED,
    ];

    /** @var list<string> */
    private array $allowedSources = [
        self::SOURCE_ADMIN,
        self::SOURCE_PUBLIC,
        self::SOURCE_IMPORT,
    ];

    public function __construct(
        private readonly ?FMSUserRepositoryInterface $userRepository = null,
    ) {
    }

    /** @param array<string, mixed> $userData */
    public function prepareCreateData(array $userData, int $actorIdentifier): array
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        $username = trim((string) ($userData['username'] ?? ''));
        $email = trim((string) ($userData['email'] ?? ''));
        $password = (string) ($userData['password'] ?? '');
        $fullName = trim((string) ($userData['full_name'] ?? ''));

        // if ($username === '' || $email === '' || $password === '' || $fullName === '') {
        if ($username === '' || $password === '' || $fullName === '') {
            throw new InvalidArgumentException('Username, email, password, and full name are required.');
        }

        $status = trim((string) ($userData['status'] ?? self::STATUS_PENDING));
        $registrationSource = trim((string) ($userData['registration_source'] ?? self::SOURCE_ADMIN));
        if (! in_array($status, $this->allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid user status.');
        }

        if (! in_array($registrationSource, $this->allowedSources, true)) {
            throw new InvalidArgumentException('Invalid registration source.');
        }

        $this->assertPasswordStrength($password);

        return [
            'username' => $username,
            'username_normalized' => $this->normalizeUsername($username),
            'email' => $email,
            'email_normalized' => mb_strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'phone' => $this->normalizeNullableText($userData['phone'] ?? null),
            'avatar' => $this->normalizeNullableText($userData['avatar'] ?? null),
            'status' => $status,
            'registration_source' => $registrationSource,
            'email_verified_at' => $registrationSource === self::SOURCE_ADMIN ? date('Y-m-d H:i:s') : null,
            'must_change_password' => (int) ($userData['must_change_password'] ?? 0) === 1 ? 1 : 0,
            'created_by' => $actorIdentifier,
            'updated_by' => $actorIdentifier,
        ];
    }

    /**
     * @param array<string, mixed> $userData
     * @param array<string, mixed> $existingUser
     * @return array<string, mixed>
     */
    public function prepareUpdateData(array $userData, array $existingUser, int $actorIdentifier): array
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        $preparedData = [];

        if (array_key_exists('username', $userData)) {
            $username = trim((string) $userData['username']);
            if ($username === '') {
                throw new InvalidArgumentException('Username must not be empty.');
            }

            $preparedData['username'] = $username;
            $preparedData['username_normalized'] = $this->normalizeUsername($username);
        }

        if (array_key_exists('email', $userData)) {
            $email = trim((string) $userData['email']);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('Email is invalid.');
            }

            $preparedData['email'] = $email;
            $preparedData['email_normalized'] = mb_strtolower($email);
            $preparedData['email_verified_at'] = ($existingUser['registration_source'] ?? null) === self::SOURCE_ADMIN
                ? date('Y-m-d H:i:s')
                : null;
            $preparedData['token_version'] = ((int) ($existingUser['token_version'] ?? 1)) + 1;
            $preparedData['session_version'] = ((int) ($existingUser['session_version'] ?? 1)) + 1;
        }

        if (array_key_exists('full_name', $userData)) {
            $fullName = trim((string) $userData['full_name']);
            if ($fullName === '') {
                throw new InvalidArgumentException('Full name must not be empty.');
            }

            $preparedData['full_name'] = $fullName;
        }

        if (array_key_exists('phone', $userData)) {
            $preparedData['phone'] = $this->normalizeNullableText($userData['phone']);
        }

        if (array_key_exists('avatar', $userData)) {
            $preparedData['avatar'] = $this->normalizeNullableText($userData['avatar']);
        }

        if (array_key_exists('status', $userData)) {
            $status = trim((string) $userData['status']);
            if (! in_array($status, $this->allowedStatuses, true)) {
                throw new InvalidArgumentException('Invalid user status.');
            }

            $preparedData['status'] = $status;
        }

        if (array_key_exists('must_change_password', $userData)) {
            $preparedData['must_change_password'] = (int) $userData['must_change_password'] === 1 ? 1 : 0;
        }

        $preparedData['updated_by'] = $actorIdentifier;

        return $preparedData;
    }

    /**
     * @param array<string, mixed> $existingUser
     * @return array<string, mixed>
     */
    public function preparePasswordResetData(string $newPassword, array $existingUser, int $actorIdentifier): array
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        $this->assertPasswordStrength($newPassword);

        return [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'token_version' => ((int) ($existingUser['token_version'] ?? 1)) + 1,
            'session_version' => ((int) ($existingUser['session_version'] ?? 1)) + 1,
            'must_change_password' => 1,
            'failed_login_count' => 0,
            'locked_until' => null,
            'password_changed_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorIdentifier,
        ];
    }

    /** @return array<string, mixed> */
    public function prepareEmailVerifiedData(int $actorIdentifier): array
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        return [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorIdentifier,
        ];
    }

    /** @return array<string, mixed> */
    public function prepareEmailUnverifiedData(int $actorIdentifier): array
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        return [
            'email_verified_at' => null,
            'updated_by' => $actorIdentifier,
        ];
    }

    /** @return array<string, mixed> */
    public function prepareUnlockData(int $actorIdentifier): array
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        return [
            'failed_login_count' => 0,
            'locked_until' => null,
            'updated_by' => $actorIdentifier,
        ];
    }

    /** @return array{page: int, per_page: int, offset: int} */
    public function normalizePagination(mixed $paginationInput): array
    {
        $page = 1;
        $perPage = 20;

        if (is_array($paginationInput)) {
            $page = (int) ($paginationInput['page'] ?? 1);
            $perPage = (int) ($paginationInput['per_page'] ?? 20);
        }

        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1) {
            $perPage = 20;
        }

        if ($perPage > self::MAXIMUM_PER_PAGE) {
            $perPage = self::MAXIMUM_PER_PAGE;
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    /** @return list<int> */
    public function normalizeGroupIdentifiers(mixed $groupIdentifiers): array
    {
        if (! is_array($groupIdentifiers)) {
            throw new InvalidArgumentException('Group identifiers must be an array.');
        }

        $normalizedGroupIdentifiers = [];
        foreach ($groupIdentifiers as $groupIdentifier) {
            if (is_int($groupIdentifier)) {
                $identifier = $groupIdentifier;
            } elseif (is_string($groupIdentifier) && ctype_digit(trim($groupIdentifier))) {
                $identifier = (int) trim($groupIdentifier);
            } else {
                throw new InvalidArgumentException('Group identifier must be a positive integer.');
            }

            if ($identifier <= 0) {
                throw new InvalidArgumentException('Group identifier must be a positive integer.');
            }

            $normalizedGroupIdentifiers[$identifier] = $identifier;
        }

        $normalizedGroupIdentifiers = array_values($normalizedGroupIdentifiers);
        sort($normalizedGroupIdentifiers, SORT_NUMERIC);

        return $normalizedGroupIdentifiers;
    }

    /** @return array<string, mixed>|null */
    public function removeSensitiveFields(?array $user): ?array
    {
        if (! is_array($user)) {
            return null;
        }

        foreach (['password_hash'] as $sensitiveFieldName) {
            unset($user[$sensitiveFieldName]);
        }

        return $user;
    }

    /** @return array{users: list<array<string, mixed>>, total: int, page: int, per_page: int} */
    public function list(array $filters, mixed $paginationInput = null): array
    {
        if ($paginationInput === null) {
            $paginationInput = $filters;
        }

        $pagination = $this->normalizePagination($paginationInput);
        $repository = $this->requireRepository();

        return $repository->paginate($filters, $pagination['page'], $pagination['per_page']);
    }

    public function detail(string $userUuid, bool $includeDeleted = false): ?array
    {
        return $this->removeSensitiveFields($this->requireRepository()->findByUuid($userUuid, $includeDeleted));
    }

    /** @param array<string, mixed> $userData */
    public function updateByUuid(string $userUuid, array $userData, int $actorIdentifier): bool
    {
        return $this->update($this->userIdentifierFromUuid($userUuid, true), $userData, $actorIdentifier);
    }

    public function deleteByUuid(string $userUuid, int $actorIdentifier): bool
    {
        return $this->delete($this->userIdentifierFromUuid($userUuid, true), $actorIdentifier);
    }

    public function restoreByUuid(string $userUuid, int $actorIdentifier): bool
    {
        return $this->restore($this->userIdentifierFromUuid($userUuid, true), $actorIdentifier);
    }

    public function resetPasswordByUuid(string $userUuid, string $newPassword, int $actorIdentifier): bool
    {
        return $this->resetPassword($this->userIdentifierFromUuid($userUuid, true), $newPassword, $actorIdentifier);
    }

    public function verifyEmailByUuid(string $userUuid, int $actorIdentifier): bool
    {
        return $this->verifyEmail($this->userIdentifierFromUuid($userUuid, true), $actorIdentifier);
    }

    public function unverifyEmailByUuid(string $userUuid, int $actorIdentifier): bool
    {
        return $this->unverifyEmail($this->userIdentifierFromUuid($userUuid, true), $actorIdentifier);
    }

    public function unlockByUuid(string $userUuid, int $actorIdentifier): bool
    {
        return $this->unlock($this->userIdentifierFromUuid($userUuid, true), $actorIdentifier);
    }

    public function changeStatusByUuid(string $userUuid, string $status, int $actorIdentifier): bool
    {
        return $this->changeStatus($this->userIdentifierFromUuid($userUuid, true), $status, $actorIdentifier);
    }

    /** @return list<array<string, mixed>> */
    public function sessionsByUuid(string $userUuid): array
    {
        return $this->userSessions($this->userIdentifierFromUuid($userUuid, true));
    }

    public function revokeSessionsByUuid(string $userUuid, int $actorIdentifier, ?string $sessionUuid = null): int
    {
        return $this->revokeUserSessions($this->userIdentifierFromUuid($userUuid, true), $actorIdentifier, $sessionUuid);
    }

    /** @return list<int> */
    public function groupsByUuid(string $userUuid): array
    {
        return $this->userGroups($this->userIdentifierFromUuid($userUuid, true));
    }

    /** @param list<int> $groupIdentifiers */
    public function replaceGroupsByUuid(string $userUuid, array $groupIdentifiers, int $actorIdentifier): void
    {
        $this->replaceUserGroups(
            $this->userIdentifierFromUuid($userUuid, true),
            $groupIdentifiers,
            $actorIdentifier,
        );
    }

    /** @param array<string, mixed> $userData */
    public function create(array $userData, int $actorIdentifier): int
    {
        $repository = $this->requireRepository();
        $preparedData = $this->prepareCreateData($userData, $actorIdentifier);
        $this->assertIdentityAvailable($preparedData['username_normalized'], $preparedData['email_normalized'], null);

        try {
            return $repository->create($preparedData);
        } catch (DatabaseException $databaseException) {
            throw new InvalidArgumentException('Username or email is already in use.', 0, $databaseException);
        }
    }

    /** @param array<string, mixed> $userData */
    public function update(int $userIdentifier, array $userData, int $actorIdentifier): bool
    {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        $repository = $this->requireRepository();
        $existingUser = $this->getUserOrThrow($userIdentifier, true);
        $preparedData = $this->prepareUpdateData($userData, $existingUser, $actorIdentifier);

        $normalizedUsername = $preparedData['username_normalized'] ?? null;
        $normalizedEmail = $preparedData['email_normalized'] ?? null;
        if ($normalizedUsername !== null || $normalizedEmail !== null) {
            $this->assertIdentityAvailable(
                $normalizedUsername ?? (string) ($existingUser['username_normalized'] ?? ''),
                $normalizedEmail ?? (string) ($existingUser['email_normalized'] ?? ''),
                $userIdentifier,
            );
        }

        return $repository->update($userIdentifier, $preparedData);
    }

    public function delete(int $userIdentifier, int $actorIdentifier): bool
    {
        if ($userIdentifier <= 0 || $actorIdentifier <= 0) {
            throw new InvalidArgumentException('User and actor identifiers must be positive integers.');
        }

        $repository = $this->requireRepository();
        $existingUser = $this->getUserOrThrow($userIdentifier, true);
        $deleted = $repository->delete($userIdentifier, $actorIdentifier);
        if (! $deleted) {
            return false;
        }

        // Soft-deleting must revoke every active session and invalidate bearer tokens.
        $repository->revokeSessions($userIdentifier, null);
        $repository->update($userIdentifier, [
            'token_version' => ((int) ($existingUser['token_version'] ?? 1)) + 1,
            'session_version' => ((int) ($existingUser['session_version'] ?? 1)) + 1,
            'updated_by' => $actorIdentifier,
        ]);

        return true;
    }

    public function restore(int $userIdentifier, int $actorIdentifier): bool
    {
        if ($userIdentifier <= 0 || $actorIdentifier <= 0) {
            throw new InvalidArgumentException('User and actor identifiers must be positive integers.');
        }

        $this->getUserOrThrow($userIdentifier, true);

        return $this->requireRepository()->restore($userIdentifier, $actorIdentifier);
    }

    public function resetPassword(int $userIdentifier, string $newPassword, int $actorIdentifier): bool
    {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        $repository = $this->requireRepository();
        $existingUser = $this->getUserOrThrow($userIdentifier, true);

        $updated = $repository->update(
            $userIdentifier,
            $this->preparePasswordResetData($newPassword, $existingUser, $actorIdentifier),
        );

        if ($updated) {
            // A credential reset must terminate every active session record.
            $repository->revokeSessions($userIdentifier, null);
        }

        return $updated;
    }

    public function verifyEmail(int $userIdentifier, int $actorIdentifier): bool
    {
        $this->getUserOrThrow($userIdentifier, true);

        return $this->requireRepository()->update($userIdentifier, $this->prepareEmailVerifiedData($actorIdentifier));
    }

    public function unverifyEmail(int $userIdentifier, int $actorIdentifier): bool
    {
        $repository = $this->requireRepository();
        $existingUser = $this->getUserOrThrow($userIdentifier, true);

        // Unverification must invalidate bearer tokens issued before the change.
        return $repository->update($userIdentifier, array_merge(
            $this->prepareEmailUnverifiedData($actorIdentifier),
            ['token_version' => ((int) ($existingUser['token_version'] ?? 1)) + 1],
        ));
    }

    public function unlock(int $userIdentifier, int $actorIdentifier): bool
    {
        $this->getUserOrThrow($userIdentifier, true);

        return $this->requireRepository()->update($userIdentifier, $this->prepareUnlockData($actorIdentifier));
    }

    public function changeStatus(int $userIdentifier, string $status, int $actorIdentifier): bool
    {
        if (! in_array($status, $this->allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid user status.');
        }

        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }

        $repository = $this->requireRepository();
        $existingUser = $this->getUserOrThrow($userIdentifier, true);

        // Any status change invalidates tokens so a disabled account cannot act.
        return $repository->update($userIdentifier, [
            'status' => $status,
            'token_version' => ((int) ($existingUser['token_version'] ?? 1)) + 1,
            'updated_by' => $actorIdentifier,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function userSessions(int $userIdentifier): array
    {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        $this->getUserOrThrow($userIdentifier, true);

        return $this->requireRepository()->sessions($userIdentifier);
    }

    public function revokeUserSessions(int $userIdentifier, int $actorIdentifier, ?string $sessionUuid = null): int
    {
        if ($userIdentifier <= 0 || $actorIdentifier <= 0) {
            throw new InvalidArgumentException('User and actor identifiers must be positive integers.');
        }

        $this->getUserOrThrow($userIdentifier, true);

        $revokedSessions = $this->requireRepository()->revokeSessions($userIdentifier, $sessionUuid);

        // Session revocation must immediately invalidate bearer tokens.
        $this->requireRepository()->update($userIdentifier, [
            'session_version' => ((int) ($this->getUserOrThrow($userIdentifier, true)['session_version'] ?? 1)) + 1,
            'updated_by' => $actorIdentifier,
        ]);

        return $revokedSessions;
    }

    /** @return list<int> */
    public function userGroups(int $userIdentifier): array
    {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        $this->getUserOrThrow($userIdentifier, true);

        return $this->requireRepository()->groupIdentifiers($userIdentifier);
    }

    /** @param list<int> $groupIdentifiers */
    public function replaceUserGroups(int $userIdentifier, array $groupIdentifiers, int $actorIdentifier): void
    {
        if ($userIdentifier <= 0 || $actorIdentifier <= 0) {
            throw new InvalidArgumentException('User and actor identifiers must be positive integers.');
        }

        $this->getUserOrThrow($userIdentifier, true);
        $normalizedGroupIdentifiers = $this->normalizeGroupIdentifiers($groupIdentifiers);

        $this->requireRepository()->replaceGroups($userIdentifier, $normalizedGroupIdentifiers, $actorIdentifier);

        // Group changes must invalidate permission caches derived from tokens.
        $existingUser = $this->getUserOrThrow($userIdentifier, true);
        $this->requireRepository()->update($userIdentifier, [
            'token_version' => ((int) ($existingUser['token_version'] ?? 1)) + 1,
            'updated_by' => $actorIdentifier,
        ]);
    }

    private function userIdentifierFromUuid(string $userUuid, bool $includeDeleted): int
    {
        $userUuid = trim($userUuid);
        if (! FMSUserValidation::isValidUuid($userUuid)) {
            throw new InvalidArgumentException('User UUID is invalid.');
        }

        $user = $this->requireRepository()->findByUuid($userUuid, $includeDeleted);
        if (! is_array($user) || ! isset($user['id'])) {
            throw new InvalidArgumentException('User was not found.');
        }

        return (int) $user['id'];
    }

    /** @return array<string, mixed> */
    private function getUserOrThrow(int $userIdentifier, bool $includeDeleted): array
    {
        $user = $this->requireRepository()->findById($userIdentifier, $includeDeleted);
        if (! is_array($user)) {
            throw new InvalidArgumentException('User was not found.');
        }

        return $user;
    }

    private function assertIdentityAvailable(string $normalizedUsername, string $normalizedEmail, ?int $excludeUserIdentifier): void
    {
        $existingUser = $this->requireRepository()->findByIdentity($normalizedUsername, $normalizedEmail, $excludeUserIdentifier);
        if (is_array($existingUser)) {
            throw new InvalidArgumentException('Username or email is already in use.');
        }
    }

    private function requireRepository(): FMSUserRepositoryInterface
    {
        if (! $this->userRepository instanceof FMSUserRepositoryInterface) {
            throw new InvalidArgumentException('A user repository is required for persistence operations.');
        }

        return $this->userRepository;
    }

    private function normalizeUsername(string $username): string
    {
        return mb_strtolower(trim($username));
    }

    private function normalizeNullableText(mixed $textValue): ?string
    {
        $textValue = trim((string) ($textValue ?? ''));

        return $textValue === '' ? null : $textValue;
    }

    private function assertPasswordStrength(string $password): void
    {
        if (mb_strlen($password) < 5) {
            throw new InvalidArgumentException('Password must be at least 5 characters.');
        }

        $characterClasses = 0;
        $characterClasses += preg_match('/[a-z]/', $password) === 1 ? 1 : 0;
        $characterClasses += preg_match('/[A-Z]/', $password) === 1 ? 1 : 0;
        $characterClasses += preg_match('/[0-9]/', $password) === 1 ? 1 : 0;
        $characterClasses += preg_match('/[^A-Za-z0-9]/', $password) === 1 ? 1 : 0;

        if ($characterClasses < 3) {
            throw new InvalidArgumentException('Password must mix at least three character classes.');
        }
    }
}
