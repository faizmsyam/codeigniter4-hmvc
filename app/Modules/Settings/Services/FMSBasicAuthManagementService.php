<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Services;

use App\Modules\Authentication\Models\FMSBasicAuthClientModel;
use App\Modules\Settings\Validation\FMSSettingsValidation;
use InvalidArgumentException;
use RuntimeException;

final class FMSBasicAuthManagementService
{
    public function __construct(
        private readonly FMSBasicAuthClientModel $clientModel = new FMSBasicAuthClientModel(),
        private readonly FMSSettingsValidation $validation = new FMSSettingsValidation(),
    ) {
    }

    /**
     * @param array{
     *     search?: string,
     *     status?: string,
     *     environment?: string,
     *     page?: int,
     *     per_page?: int
     * } $filters
     * @return array{items: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function listClients(array $filters): array
    {
        $page    = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $now     = date('Y-m-d H:i:s');

        $builder = $this->clientModel->builder();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('username', $search)
                ->orLike('label', $search)
                ->groupEnd();
        }

        $env = trim((string) ($filters['environment'] ?? ''));
        if ($env !== '' && $env !== 'all') {
            $builder->where('environment', $env);
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'active') {
            $builder->where('revoked_at IS NULL')
                ->groupStart()
                    ->where('locked_until IS NULL')
                    ->orWhere('locked_until <=', $now)
                ->groupEnd()
                ->groupStart()
                    ->where('expires_at IS NULL')
                    ->orWhere('expires_at >', $now)
                ->groupEnd();
        } elseif ($status === 'locked') {
            $builder->where('locked_until IS NOT NULL')
                ->where('locked_until >', $now);
        } elseif ($status === 'revoked') {
            $builder->where('revoked_at IS NOT NULL');
        } elseif ($status === 'expired') {
            $builder->where('revoked_at IS NULL')
                ->where('expires_at IS NOT NULL')
                ->where('expires_at <=', $now);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults();

        $items = $builder->orderBy('id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        $formatted = array_map(function (array $row) use ($now): array {
            $isRevoked = ! empty($row['revoked_at']);
            $isLocked  = ! empty($row['locked_until']) && $row['locked_until'] > $now;
            $isExpired = ! empty($row['expires_at']) && $row['expires_at'] <= $now;

            $statusText = 'active';
            if ($isRevoked) {
                $statusText = 'revoked';
            } elseif ($isLocked) {
                $statusText = 'locked';
            } elseif ($isExpired) {
                $statusText = 'expired';
            }

            $scopes = json_decode((string) ($row['scopes_json'] ?? '[]'), true) ?: ['*'];

            return [
                'id'              => (int) $row['id'],
                'username'        => $row['username'],
                'label'           => $row['label'],
                'scopes'          => $scopes,
                'environment'     => $row['environment'],
                'status'          => $statusText,
                'failed_attempts' => (int) ($row['failed_attempts'] ?? 0),
                'locked_until'    => $row['locked_until'],
                'expires_at'      => $row['expires_at'],
                'revoked_at'      => $row['revoked_at'],
                'last_used_at'    => $row['last_used_at'],
                'created_at'      => $row['created_at'],
                'updated_at'      => $row['updated_at'],
            ];
        }, $items);

        return [
            'items' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ];
    }

    public function getClient(string $username): ?array
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $isRevoked = ! empty($row['revoked_at']);
        $isLocked  = ! empty($row['locked_until']) && $row['locked_until'] > $now;
        $isExpired = ! empty($row['expires_at']) && $row['expires_at'] <= $now;

        $statusText = 'active';
        if ($isRevoked) {
            $statusText = 'revoked';
        } elseif ($isLocked) {
            $statusText = 'locked';
        } elseif ($isExpired) {
            $statusText = 'expired';
        }

        $scopes = json_decode((string) ($row['scopes_json'] ?? '[]'), true) ?: ['*'];

        return [
            'id'              => (int) $row['id'],
            'username'        => $row['username'],
            'label'           => $row['label'],
            'scopes'          => $scopes,
            'environment'     => $row['environment'],
            'status'          => $statusText,
            'failed_attempts' => (int) ($row['failed_attempts'] ?? 0),
            'locked_until'    => $row['locked_until'],
            'expires_at'      => $row['expires_at'],
            'revoked_at'      => $row['revoked_at'],
            'last_used_at'    => $row['last_used_at'],
            'created_at'      => $row['created_at'],
            'updated_at'      => $row['updated_at'],
        ];
    }

    /**
     * @return array{plain_password: string, client: array<string, mixed>}
     */
    public function createClient(array $payload, int $actorId): array
    {
        $validated = $this->validation->validateBasicAuthClient($payload, false);
        $username  = strtolower(trim($validated['username']));

        if ($this->clientModel->where('LOWER(username)', $username)->countAllResults() > 0) {
            throw new InvalidArgumentException(json_encode(['username' => 'Username client sudah digunakan.']));
        }

        $plainPassword = $validated['password'];
        if ($plainPassword === '') {
            $plainPassword = bin2hex(random_bytes(10)) . '!' . rand(10, 99);
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'username'        => $username,
            'label'           => $validated['label'],
            'password_hash'   => password_hash($plainPassword, PASSWORD_DEFAULT),
            'scopes_json'     => $validated['scopes_json'],
            'environment'     => $validated['environment'],
            'expires_at'      => $validated['expires_at'],
            'locked_until'    => null,
            'failed_attempts' => 0,
            'revoked_at'      => null,
            'created_by'      => $actorId > 0 ? $actorId : null,
            'created_at'      => $now,
        ];

        $insertId = $this->clientModel->insert($insertData, true);
        if ($insertId === false) {
            throw new RuntimeException('Gagal membuat Basic Auth Client.');
        }

        return [
            'plain_password' => $plainPassword,
            'client'         => $this->getClient($username),
        ];
    }

    public function updateClient(string $username, array $payload, int $actorId): array
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('Basic Auth Client tidak ditemukan.');
        }

        $validated = $this->validation->validateBasicAuthClient($payload, true);

        $updateData = [
            'updated_by' => $actorId > 0 ? $actorId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (isset($validated['label']) && $validated['label'] !== '') {
            $updateData['label'] = $validated['label'];
        }
        if (isset($validated['environment'])) {
            $updateData['environment'] = $validated['environment'];
        }
        if (isset($validated['scopes_json'])) {
            $updateData['scopes_json'] = $validated['scopes_json'];
        }
        if (array_key_exists('expires_at', $validated)) {
            $updateData['expires_at'] = $validated['expires_at'];
        }

        $this->clientModel->update((int) $row['id'], $updateData);

        return $this->getClient($normalizedUsername);
    }

    /**
     * @return array{plain_password: string, client: array<string, mixed>}
     */
    public function resetPassword(string $username, ?string $customPassword, int $actorId): array
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('Basic Auth Client tidak ditemukan.');
        }

        $plainPassword = trim((string) $customPassword);
        if ($plainPassword === '') {
            $plainPassword = bin2hex(random_bytes(10)) . '!' . rand(10, 99);
        } elseif (strlen($plainPassword) < 8) {
            throw new InvalidArgumentException(json_encode(['password' => 'Password minimal 8 karakter.']));
        }

        $this->clientModel->update((int) $row['id'], [
            'password_hash'   => password_hash($plainPassword, PASSWORD_DEFAULT),
            'failed_attempts' => 0,
            'locked_until'    => null,
            'updated_by'      => $actorId > 0 ? $actorId : null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return [
            'plain_password' => $plainPassword,
            'client'         => $this->getClient($normalizedUsername),
        ];
    }

    public function unlockClient(string $username, int $actorId): array
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('Basic Auth Client tidak ditemukan.');
        }

        $this->clientModel->update((int) $row['id'], [
            'locked_until'    => null,
            'failed_attempts' => 0,
            'updated_by'      => $actorId > 0 ? $actorId : null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return $this->getClient($normalizedUsername);
    }

    public function revokeClient(string $username, int $actorId): array
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('Basic Auth Client tidak ditemukan.');
        }

        $this->clientModel->update((int) $row['id'], [
            'revoked_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorId > 0 ? $actorId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getClient($normalizedUsername);
    }

    public function activateClient(string $username, int $actorId): array
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('Basic Auth Client tidak ditemukan.');
        }

        $this->clientModel->update((int) $row['id'], [
            'revoked_at' => null,
            'updated_by' => $actorId > 0 ? $actorId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getClient($normalizedUsername);
    }

    public function deleteClient(string $username, int $actorId): bool
    {
        $normalizedUsername = strtolower(trim($username));
        $row = $this->clientModel->where('LOWER(username)', $normalizedUsername)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('Basic Auth Client tidak ditemukan.');
        }

        return (bool) $this->clientModel->delete((int) $row['id'], true);
    }
}
