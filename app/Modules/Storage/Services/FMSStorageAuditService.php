<?php
namespace App\Modules\Storage\Services;

final class FMSStorageAuditService
{
    public function log(?array $user, string $event, ?string $objectKey = null, array $metadata = [], string $ipAddress = ''): void
    {
        db_connect()->table('storage_audit_logs')->insert([
            'user_id' => isset($user['id']) ? (int) $user['id'] : null,
            'event' => substr($event, 0, 100),
            'object_key' => $objectKey !== null ? substr($objectKey, 0, 1024) : null,
            'ip_hash' => $ipAddress !== '' ? hash('sha256', $ipAddress) : null,
            'metadata_json' => $metadata === [] ? null : json_encode($this->redact($metadata), JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function list(int $limit = 100, int $offset = 0): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $db = db_connect();
        $rows = $db->table('storage_audit_logs AS audit')
            ->select('audit.id, audit.event, audit.object_key, audit.metadata_json, audit.created_at, users.username, users.full_name')
            ->join('storage_users AS users', 'users.id = audit.user_id', 'left')
            ->orderBy('audit.id', 'DESC')
            ->get($limit, $offset)->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $metadata = json_decode((string) ($row['metadata_json'] ?? ''), true);
            $result[] = [
                'id' => (int) $row['id'],
                'event' => (string) $row['event'],
                'object_key' => $row['object_key'] !== null ? (string) $row['object_key'] : null,
                'metadata' => is_array($metadata) ? $metadata : [],
                'username' => $row['username'] !== null ? (string) $row['username'] : 'system',
                'full_name' => $row['full_name'] !== null ? (string) $row['full_name'] : 'System',
                'created_at' => (string) $row['created_at'],
            ];
        }
        return $result;
    }

    private function redact(array $data): array
    {
        $secretKeys = ['password', 'password_hash', 'token', 'token_hash', 'secret', 'access_key', 'secret_key', 'authorization', 'cookie'];
        $result = [];
        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, $secretKeys, true) || str_contains($normalized, 'credential')) {
                $result[$key] = '[REDACTED]';
                continue;
            }
            $result[$key] = is_array($value) ? $this->redact($value) : $value;
        }
        return $result;
    }
}
