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
            ->select('session_uuid, device_label, last_activity_at, expires_at, revoked_at, created_at')
            ->where('user_id', $userIdentifier)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return array_values($sessions);
    }

    public function revokeSessions(int $userIdentifier, ?string $sessionUuid = null): int
    {
        $builder = $this->databaseConnection
            ->table('t_user_sessions')
            ->where('user_id', $userIdentifier)
            ->where('revoked_at', null);

        if ($sessionUuid !== null) {
            $builder->where('session_uuid', $sessionUuid);
        }

        $builder->update(['revoked_at' => $this->timestamp()]);

        return $this->databaseConnection->affectedRows();
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
