<?php

namespace App\Modules\Users\Validation;

use InvalidArgumentException;

final class FMSUserValidation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_LOCKED = 'locked';

    public const SOURCE_PUBLIC = 'public';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_IMPORT = 'import';

    /**
     * @return array<int, string>
     */
    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_LOCKED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedRegistrationSources(): array
    {
        return [
            self::SOURCE_PUBLIC,
            self::SOURCE_ADMIN,
            self::SOURCE_IMPORT,
        ];
    }

    /**
     * Validate a full create payload. Returns field errors (field => messages).
     *
     * @param array<string, mixed> $userData
     * @return array<string, array<int, string>>
     */
    public static function validateCreatePayload(array $userData): array
    {
        $validationErrors = [];

        $usernameErrors = self::validateUsername((string) ($userData['username'] ?? ''));
        if ($usernameErrors !== []) {
            $validationErrors['username'] = $usernameErrors;
        }

        $emailErrors = self::validateEmail((string) ($userData['email'] ?? ''));
        if ($emailErrors !== []) {
            $validationErrors['email'] = $emailErrors;
        }

        $passwordErrors = self::validatePassword((string) ($userData['password'] ?? ''));
        if ($passwordErrors !== []) {
            $validationErrors['password'] = $passwordErrors;
        }

        $fullName = trim((string) ($userData['full_name'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) > 150) {
            $validationErrors['full_name'] = ['Nama lengkap wajib diisi maksimal 150 karakter.'];
        }

        if (isset($userData['status']) && ! in_array($userData['status'], self::allowedStatuses(), true)) {
            $validationErrors['status'] = ['Status tidak dikenal.'];
        }

        if (isset($userData['registration_source']) && ! in_array($userData['registration_source'], self::allowedRegistrationSources(), true)) {
            $validationErrors['registration_source'] = ['Sumber registrasi tidak dikenal.'];
        }

        if (array_key_exists('uuid', $userData) && ! self::isValidUuid((string) $userData['uuid'])) {
            $validationErrors['uuid'] = ['UUID tidak valid.'];
        }

        return $validationErrors;
    }

    /**
     * @return array<int, string>
     */
    public static function validateUsername(string $username): array
    {
        $normalizedUsername = trim($username);
        if ($normalizedUsername === '' || mb_strlen($normalizedUsername) < 3 || mb_strlen($normalizedUsername) > 100) {
            return ['Username wajib 3-100 karakter.'];
        }

        if (preg_match('/^[A-Za-z0-9._-]+$/', $normalizedUsername) !== 1) {
            return ['Username hanya boleh huruf, angka, titik, underscore, dan strip.'];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function validateEmail(string $email): array
    {
        $normalizedEmail = trim($email);
        if ($normalizedEmail === '' || mb_strlen($normalizedEmail) > 190 || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            return ['Email tidak valid.'];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function validatePassword(string $password): array
    {
        if (mb_strlen($password) < 12) {
            return ['Password minimal 12 karakter.'];
        }

        $missingClasses = [];
        if (preg_match('/[a-z]/', $password) !== 1) {
            $missingClasses[] = 'huruf kecil';
        }
        if (preg_match('/[A-Z]/', $password) !== 1) {
            $missingClasses[] = 'huruf besar';
        }
        if (preg_match('/[0-9]/', $password) !== 1) {
            $missingClasses[] = 'angka';
        }
        if (preg_match('/[^A-Za-z0-9]/', $password) !== 1) {
            $missingClasses[] = 'simbol';
        }

        if ($missingClasses !== []) {
            return ['Password wajib memuat ' . implode(', ', $missingClasses) . '.'];
        }

        return [];
    }

    public static function isValidUuid(string $userUuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $userUuid,
        ) === 1;
    }

    /**
     * @param array<string, mixed> $userData
     * @return array<string, array<int, string>>
     */
    public static function validateCreate(array $userData): array
    {
        return self::validateCreatePayload($userData);
    }

    /**
     * @param array<string, mixed> $userData
     * @return array<string, array<int, string>>
     */
    public static function validateUpdate(array $userData): array
    {
        $validationErrors = [];
        if (array_key_exists('username', $userData)) {
            $usernameErrors = self::validateUsername((string) $userData['username']);
            if ($usernameErrors !== []) {
                $validationErrors['username'] = $usernameErrors;
            }
        }

        if (array_key_exists('email', $userData)) {
            $emailErrors = self::validateEmail((string) $userData['email']);
            if ($emailErrors !== []) {
                $validationErrors['email'] = $emailErrors;
            }
        }

        return $validationErrors;
    }

    public static function normalizeIdentity(string $identity): string
    {
        return mb_strtolower(trim($identity), 'UTF-8');
    }

    /**
     * @param array<int, mixed> $groupIdentifiers
     * @return array<int, int>
     */
    public static function normalizeGroupIdentifiers(array $groupIdentifiers): array
    {
        $normalizedGroupIdentifiers = [];
        foreach ($groupIdentifiers as $groupIdentifier) {
            if (is_int($groupIdentifier)) {
                $normalizedGroupIdentifier = $groupIdentifier;
            } elseif (is_string($groupIdentifier) && ctype_digit(trim($groupIdentifier))) {
                $normalizedGroupIdentifier = (int) trim($groupIdentifier);
            } else {
                throw new InvalidArgumentException('Group identifier must be a positive integer.');
            }

            if ($normalizedGroupIdentifier <= 0 || $normalizedGroupIdentifier > 9007199254740991) {
                throw new InvalidArgumentException('Group identifier must be a positive integer.');
            }

            $normalizedGroupIdentifiers[$normalizedGroupIdentifier] = $normalizedGroupIdentifier;
        }

        $sortedGroupIdentifiers = array_values($normalizedGroupIdentifiers);
        sort($sortedGroupIdentifiers, SORT_NUMERIC);

        return $sortedGroupIdentifiers;
    }

    /**
     * @param array<string, mixed> $paginationInput
     * @return array{page: int, perPage: int, search: string, status: string}
     */
    public static function normalizeListInput(array $paginationInput): array
    {
        $requestedPage = (int) ($paginationInput['page'] ?? 1);
        $requestedPerPage = (int) ($paginationInput['per_page'] ?? 20);
        $normalizedPage = max(1, min($requestedPage, 100000));
        $normalizedPerPage = max(1, min($requestedPerPage <= 0 ? 20 : $requestedPerPage, 100));

        $normalizedSearch = mb_substr(trim((string) ($paginationInput['search'] ?? '')), 0, 100, 'UTF-8');
        $normalizedStatus = trim((string) ($paginationInput['status'] ?? ''));
        if ($normalizedStatus !== '' && ! in_array($normalizedStatus, self::allowedStatuses(), true)) {
            throw new InvalidArgumentException('Status filter is invalid.');
        }

        return [
            'page'    => $normalizedPage,
            'perPage' => $normalizedPerPage,
            'search'  => $normalizedSearch,
            'status'  => $normalizedStatus,
        ];
    }
}
