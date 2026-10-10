<?php

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Contracts\FMSUserRepositoryInterface;
use App\Modules\Users\Models\FMSUserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;
use RuntimeException;
use Throwable;

final class FMSDatabaseUserRepository implements FMSUserRepositoryInterface
{
    private readonly BaseConnection $databaseConnection;

    public function __construct(
        private readonly FMSUserModel $userModel = new FMSUserModel(),
        ?BaseConnection $databaseConnection = null,
    ) {
        $this->databaseConnection = $databaseConnection ?? Database::connect();
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $model = clone $this->userModel;
        if (($filters['include_deleted'] ?? false) === true) {
            $model = $model->withDeleted();
        }
        if (($filters['deleted_only'] ?? false) === true) {
            $model->where('deleted_at IS NOT NULL', null, false);
        }
        if (($filters['exclude_programmer_super_admin'] ?? false) === true) {
            $model->where('username_normalized !=', 'faizmsyam');
        }

        $searchTerm = trim((string) ($filters['search'] ?? ''));
        if ($searchTerm !== '') {
            $escapedSearchTerm = $model->escapeLikeString(mb_strtolower($searchTerm));
            $model->groupStart()
                ->like('username_normalized', $escapedSearchTerm, 'both', null, true)
                ->orLike('email_normalized', $escapedSearchTerm, 'both', null, true)
                ->orLike('full_name', $escapedSearchTerm, 'both', null, true)
                ->groupEnd();
        }

        foreach (['status', 'registration_source'] as $filterFieldName) {
            $filterValue = trim((string) ($filters[$filterFieldName] ?? ''));
            if ($filterValue !== '') {
                $model->where($filterFieldName, $filterValue);
            }
        }

        $totalUsers = $model->countAllResults(false);
        $users = $model
            ->select($this->safeSelectColumns())
            ->orderBy('id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        return [
            'users' => is_array($users) ? array_values($users) : [],
            'total' => $totalUsers,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function findById(int $userIdentifier, bool $includeDeleted = false): ?array
    {
        $userModel = $includeDeleted ? $this->userModel->withDeleted() : $this->userModel;
        $user = $userModel
            ->select($this->safeSelectColumns())
            ->where('id', $userIdentifier)
            ->first();

        return is_array($user) ? $user : null;
    }

    public function findByUuid(string $userUuid, bool $includeDeleted = false): ?array
    {
        $model = $includeDeleted ? $this->userModel->withDeleted() : $this->userModel;
        $user = $model->select($this->safeSelectColumns())->where('uuid', $userUuid)->first();

        return is_array($user) ? $user : null;
    }

    public function findByIdentity(string $normalizedUsername, string $normalizedEmail, ?int $excludeUserIdentifier = null): ?array
    {
        $model = $this->userModel->withDeleted()
            ->groupStart()
            ->where('username_normalized', $normalizedUsername)
            ->orWhere('email_normalized', $normalizedEmail)
            ->groupEnd();

        if ($excludeUserIdentifier !== null) {
            $model->where('id !=', $excludeUserIdentifier);
        }

        $user = $model->first();

        return is_array($user) ? $user : null;
    }

    public function create(array $userData): int
    {
        $createdAt = $this->timestamp();
        $uuid = $this->uuidV4();
        $userData['created_at'] = $createdAt;
        $userData['updated_at'] = $createdAt;

        $builder = $this->databaseConnection->table('m_users');
        $created = $builder->insert(array_merge(['uuid' => $uuid], $userData));
        if ($created !== true) {
            throw new RuntimeException('User could not be created.');
        }

        return (int) $this->databaseConnection->insertID();
    }

    public function update(int $userIdentifier, array $userData): bool
    {
        $userData['updated_at'] = $this->timestamp();

        return $this->databaseConnection
            ->table('m_users')
            ->where('id', $userIdentifier)
            ->update($userData);
    }

    public function delete(int $userIdentifier, int $actorIdentifier): bool
    {
        return $this->databaseConnection
            ->table('m_users')
            ->where('id', $userIdentifier)
            ->where('deleted_at', null)
            ->update([
                'deleted_by' => $actorIdentifier,
                'deleted_at' => $this->timestamp(),
                'updated_by' => $actorIdentifier,
                'updated_at' => $this->timestamp(),
            ]);
    }

    public function restore(int $userIdentifier, int $actorIdentifier): bool
    {
        return $this->databaseConnection
            ->table('m_users')
            ->where('id', $userIdentifier)
            ->where('deleted_at IS NOT NULL', null, false)
            ->update([
                'deleted_by' => null,
                'deleted_at' => null,
                'updated_by' => $actorIdentifier,
                'updated_at' => $this->timestamp(),
            ]);
    }

    public function sessions(int $userIdentifier): array
    {
        $sessions = $this->databaseConnection
            ->table('t_user_sessions')
            ->select('session_uuid, token_family_id, device_label, last_activity_at, expires_at, revoked_at, created_at')
            ->where('user_id', $userIdentifier)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        /* Login lama hanya tercatat sebagai refresh-token family; tampilkan status terakhir per family. */
        if ($this->databaseConnection->tableExists('t_api_refresh_tokens')) {
            $knownFamilies = array_fill_keys(array_map(
                static fn (array $row): string => (string) ($row['token_family_id'] ?? ''),
                $sessions,
            ), true);
            $refreshRows = $this->databaseConnection
                ->table('t_api_refresh_tokens')
                ->select('token_family_id, device_label, issued_at, expires_at, revoked_at, created_at')
                ->where('user_id', $userIdentifier)
                ->orderBy('issued_at', 'DESC')
                ->get()
                ->getResultArray();
            $latestRefreshByFamily = [];

            foreach ($refreshRows as $refreshRow) {
                $familyIdentifier = (string) ($refreshRow['token_family_id'] ?? '');
                if ($familyIdentifier !== '' && ! isset($latestRefreshByFamily[$familyIdentifier])) {
                    $latestRefreshByFamily[$familyIdentifier] = $refreshRow;
                }
            }

            foreach ($latestRefreshByFamily as $familyIdentifier => $refreshRow) {
                if (isset($knownFamilies[$familyIdentifier])) {
                    continue;
                }

                $knownFamilies[$familyIdentifier] = true;
                $sessions[] = [
                    'session_uuid'     => $familyIdentifier,
                    'token_family_id'  => $familyIdentifier,
                    'device_label'     => (string) ($refreshRow['device_label'] ?? ''),
                    'last_activity_at' => (string) ($refreshRow['issued_at'] ?? ''),
                    'expires_at'       => (string) ($refreshRow['expires_at'] ?? ''),
                    'revoked_at'       => $refreshRow['revoked_at'] ?? null,
                    'created_at'       => (string) ($refreshRow['created_at'] ?? ''),
                ];
            }
        }

        $currentTimestamp = $this->timestamp();
        foreach ($sessions as &$session) {
            if (empty($session['revoked_at']) && (string) ($session['expires_at'] ?? '') <= $currentTimestamp) {
                $session['revoked_at'] = (string) ($session['expires_at'] ?? $currentTimestamp);
            }
        }
        unset($session);

        usort($sessions, static fn (array $left, array $right): int => strcmp(
            (string) ($right['last_activity_at'] ?? $right['created_at'] ?? ''),
            (string) ($left['last_activity_at'] ?? $left['created_at'] ?? ''),
        ));

        return array_values($sessions);
    }

    public function revokeSessions(int $userIdentifier, ?string $sessionUuid = null): int
    {
        $builder = $this->databaseConnection
            ->table('t_user_sessions')
            ->where('user_id', $userIdentifier)
            ->where('revoked_at', null);

        $isSessionUuid = $sessionUuid !== null
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $sessionUuid) === 1;
        if ($sessionUuid !== null) {
            $builder->where($isSessionUuid ? 'session_uuid' : 'token_family_id', $sessionUuid);
        }

        /* Ambil family token yang tercakup agar refresh token ikut dicabut. */
        $familyIdentifiers = [];
        if ($sessionUuid !== null && ! $isSessionUuid) {
            $familyIdentifiers[] = $sessionUuid;
        }
        $familyRows = (clone $builder)
            ->select('token_family_id')
            ->get()
            ->getResultArray();
        $familyIdentifiers = array_values(array_unique(array_merge($familyIdentifiers, array_filter(array_map(
            static fn (array $row): string => (string) ($row['token_family_id'] ?? ''),
            $familyRows,
        )))));

        $revokedAt = $this->timestamp();
        $builder->update(['revoked_at' => $revokedAt]);
        $revokedCount = $this->databaseConnection->affectedRows();

        /* Cegah refresh token perangkat yang di-kick tetap bisa memperpanjang sesi. */
        $refreshTokenAffectedRows = 0;
        if ($this->databaseConnection->tableExists('t_api_refresh_tokens')) {
            if ($sessionUuid === null) {
                $this->databaseConnection
                    ->table('t_api_refresh_tokens')
                    ->where('user_id', $userIdentifier)
                    ->where('revoked_at', null)
                    ->update(['revoked_at' => $revokedAt]);
                $refreshTokenAffectedRows = $this->databaseConnection->affectedRows();
            } elseif ($familyIdentifiers !== []) {
                $this->databaseConnection
                    ->table('t_api_refresh_tokens')
                    ->whereIn('token_family_id', $familyIdentifiers)
                    ->where('revoked_at', null)
                    ->update(['revoked_at' => $revokedAt]);
                $refreshTokenAffectedRows = $this->databaseConnection->affectedRows();
            }
        }

        return max($revokedCount, $refreshTokenAffectedRows);
    }

    public function groupIdentifiers(int $userIdentifier): array
    {
        if (! $this->databaseConnection->tableExists('t_user_groups')) {
            return [];
        }

        $rows = $this->databaseConnection
            ->table('t_user_groups')
            ->select('group_id')
            ->where('user_id', $userIdentifier)
            ->orderBy('group_id', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): int => (int) $row['group_id'], $rows);
    }

    public function replaceGroups(int $userIdentifier, array $groupIdentifiers, int $actorIdentifier): void
    {
        if (! $this->databaseConnection->tableExists('t_user_groups')) {
            throw new RuntimeException('User-group table is not available.');
        }

        $this->databaseConnection->transException(true)->transStart();
        try {
            $this->databaseConnection->table('t_user_groups')->where('user_id', $userIdentifier)->delete();
            $assignedAt = $this->timestamp();
            foreach ($groupIdentifiers as $groupIdentifier) {
                $this->databaseConnection->table('t_user_groups')->insert([
                    'user_id'     => $userIdentifier,
                    'group_id'    => $groupIdentifier,
                    'assigned_by' => $actorIdentifier,
                    'created_at'  => $assignedAt,
                ]);
            }
            $this->databaseConnection->transComplete();
        } catch (Throwable $throwable) {
            if ($this->databaseConnection->transStatus()) {
                $this->databaseConnection->transRollback();
            }

            throw $throwable;
        }
    }

    /** @return list<string> */
    private function safeSelectColumns(): array
    {
        return [
            'id', 'uuid', 'username', 'email', 'full_name', 'phone', 'avatar',
            'status', 'registration_source', 'email_verified_at', 'password_changed_at',
            'failed_login_count', 'locked_until', 'last_login_at', 'must_change_password',
            'token_version', 'session_version', 'created_at', 'updated_at', 'deleted_at',
        ];
    }

    private function timestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function uuidV4(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0f) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3f) | 0x80);
        $hexadecimal = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimal, 0, 8),
            substr($hexadecimal, 8, 4),
            substr($hexadecimal, 12, 4),
            substr($hexadecimal, 16, 4),
            substr($hexadecimal, 20),
        );
    }
}
