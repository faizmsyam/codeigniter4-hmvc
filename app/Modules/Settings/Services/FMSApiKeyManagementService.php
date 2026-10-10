<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Services;

use App\Modules\Authentication\Models\FMSApiKeyModel;
use App\Modules\Settings\Validation\FMSSettingsValidation;
use InvalidArgumentException;
use RuntimeException;

final class FMSApiKeyManagementService
{
    private string $serverPepper;

    public function __construct(
        private readonly FMSApiKeyModel $apiKeyModel = new FMSApiKeyModel(),
        private readonly FMSSettingsValidation $validation = new FMSSettingsValidation(),
        ?string $serverPepper = null,
    ) {
        $this->serverPepper = $serverPepper
            ?? (string) (getenv('fms.api_key_pepper') ?: getenv('API_KEY_PEPPER') ?: 'fms-starter-secret-pepper-32bytes-key');
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
    public function listApiKeys(array $filters): array
    {
        $page    = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $now     = date('Y-m-d H:i:s');

        $builder = $this->apiKeyModel->builder();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('label', $search)
                ->orLike('key_id', $search)
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
                    ->where('expires_at IS NULL')
                    ->orWhere('expires_at >', $now)
                ->groupEnd();
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
            $isExpired = ! empty($row['expires_at']) && $row['expires_at'] <= $now;

            $statusText = 'active';
            if ($isRevoked) {
                $statusText = 'revoked';
            } elseif ($isExpired) {
                $statusText = 'expired';
            }

            $scopes = json_decode((string) ($row['scopes_json'] ?? '[]'), true) ?: ['*'];
            $ipAllowlist = json_decode((string) ($row['ip_allowlist_json'] ?? '[]'), true) ?: [];

            return [
                'id'                => (int) $row['id'],
                'key_id'            => $row['key_id'],
                'label'             => $row['label'],
                'scopes'            => $scopes,
                'environment'       => $row['environment'],
                'ip_allowlist'      => $ipAllowlist,
                'status'            => $statusText,
                'expires_at'        => $row['expires_at'],
                'revoked_at'        => $row['revoked_at'],
                'last_used_at'      => $row['last_used_at'],
                'created_at'        => $row['created_at'],
                'updated_at'        => $row['updated_at'],
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

    public function getApiKey(string $keyId): ?array
    {
        $row = $this->apiKeyModel->where('key_id', $keyId)->first();
        if (! is_array($row)) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $isRevoked = ! empty($row['revoked_at']);
        $isExpired = ! empty($row['expires_at']) && $row['expires_at'] <= $now;

        $statusText = 'active';
        if ($isRevoked) {
            $statusText = 'revoked';
        } elseif ($isExpired) {
            $statusText = 'expired';
        }

        $scopes = json_decode((string) ($row['scopes_json'] ?? '[]'), true) ?: ['*'];
        $ipAllowlist = json_decode((string) ($row['ip_allowlist_json'] ?? '[]'), true) ?: [];

        return [
            'id'                => (int) $row['id'],
            'key_id'            => $row['key_id'],
            'label'             => $row['label'],
            'scopes'            => $scopes,
            'environment'       => $row['environment'],
            'ip_allowlist'      => $ipAllowlist,
            'status'            => $statusText,
            'expires_at'        => $row['expires_at'],
            'revoked_at'        => $row['revoked_at'],
            'last_used_at'      => $row['last_used_at'],
            'created_at'        => $row['created_at'],
            'updated_at'        => $row['updated_at'],
        ];
    }

    /**
     * @return array{presented_key: string, key: array<string, mixed>}
     */
    public function createApiKey(array $payload, int $actorId): array
    {
        $validated = $this->validation->validateApiKey($payload, false);

        // Generate key identifier (16 chars alphanumeric)
        $keyIdentifier = bin2hex(random_bytes(8));
        // Ensure uniqueness
        while ($this->apiKeyModel->where('key_id', $keyIdentifier)->countAllResults() > 0) {
            $keyIdentifier = bin2hex(random_bytes(8));
        }

        // Generate random secret (32 bytes = 43 chars base64url)
        $secretBinary  = random_bytes(32);
        $secretEncoded = rtrim(strtr(base64_encode($secretBinary), '+/', '-_'), '=');
        $presentedKey  = 'fms_' . $keyIdentifier . '_' . $secretEncoded;
        $secretHash    = hash_hmac('sha256', $secretEncoded, $this->serverPepper);

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'key_id'            => $keyIdentifier,
            'label'             => $validated['label'],
            'secret_hash'       => $secretHash,
            'scopes_json'       => $validated['scopes_json'],
            'environment'       => $validated['environment'],
            'ip_allowlist_json' => $validated['ip_allowlist_json'],
            'expires_at'        => $validated['expires_at'],
            'revoked_at'        => null,
            'created_by'        => $actorId > 0 ? $actorId : null,
            'created_at'        => $now,
        ];

        $insertId = $this->apiKeyModel->insert($insertData, true);
        if ($insertId === false) {
            throw new RuntimeException('Gagal membuat API Key baru.');
        }

        $createdKey = $this->getApiKey($keyIdentifier);

        return [
            'presented_key' => $presentedKey,
            'key'           => $createdKey,
        ];
    }

    public function updateApiKey(string $keyId, array $payload, int $actorId): array
    {
        $row = $this->apiKeyModel->where('key_id', $keyId)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('API Key tidak ditemukan.');
        }

        $validated = $this->validation->validateApiKey($payload, true);

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
        if (array_key_exists('ip_allowlist_json', $validated)) {
            $updateData['ip_allowlist_json'] = $validated['ip_allowlist_json'];
        }
        if (array_key_exists('expires_at', $validated)) {
            $updateData['expires_at'] = $validated['expires_at'];
        }

        $this->apiKeyModel->update((int) $row['id'], $updateData);

        return $this->getApiKey($keyId);
    }

    public function revokeApiKey(string $keyId, int $actorId): array
    {
        $row = $this->apiKeyModel->where('key_id', $keyId)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('API Key tidak ditemukan.');
        }

        $this->apiKeyModel->update((int) $row['id'], [
            'revoked_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorId > 0 ? $actorId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getApiKey($keyId);
    }

    public function activateApiKey(string $keyId, int $actorId): array
    {
        $row = $this->apiKeyModel->where('key_id', $keyId)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('API Key tidak ditemukan.');
        }

        $this->apiKeyModel->update((int) $row['id'], [
            'revoked_at' => null,
            'updated_by' => $actorId > 0 ? $actorId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getApiKey($keyId);
    }

    /**
     * Roll secret: generate secret baru untuk key_id yang sama.
     *
     * @return array{presented_key: string, key: array<string, mixed>}
     */
    public function rollApiKey(string $keyId, int $actorId): array
    {
        $row = $this->apiKeyModel->where('key_id', $keyId)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('API Key tidak ditemukan.');
        }

        $secretBinary  = random_bytes(32);
        $secretEncoded = rtrim(strtr(base64_encode($secretBinary), '+/', '-_'), '=');
        $presentedKey  = 'fms_' . $keyId . '_' . $secretEncoded;
        $secretHash    = hash_hmac('sha256', $secretEncoded, $this->serverPepper);

        $this->apiKeyModel->update((int) $row['id'], [
            'secret_hash' => $secretHash,
            'revoked_at'  => null, // re-aktifkan jika sebelumnya revoked
            'updated_by'  => $actorId > 0 ? $actorId : null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return [
            'presented_key' => $presentedKey,
            'key'           => $this->getApiKey($keyId),
        ];
    }

    public function deleteApiKey(string $keyId, int $actorId): bool
    {
        $row = $this->apiKeyModel->where('key_id', $keyId)->first();
        if (! is_array($row)) {
            throw new InvalidArgumentException('API Key tidak ditemukan.');
        }

        return (bool) $this->apiKeyModel->delete((int) $row['id'], true);
    }
}
