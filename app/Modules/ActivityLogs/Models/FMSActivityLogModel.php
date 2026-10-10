<?php

namespace App\Modules\ActivityLogs\Models;

use App\Core\FMSModel;
use App\Modules\ActivityLogs\Contracts\FMSActivityLogRepositoryInterface;

final class FMSActivityLogModel extends FMSModel implements FMSActivityLogRepositoryInterface
{
    protected $table = 't_activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $deletedField = '';
    protected $allowedFields = [
        'uuid',
        'request_id',
        'actor_user_id',
        'event',
        'module',
        'entity_type',
        'entity_id',
        'description',
        'before_json',
        'after_json',
        'metadata_json',
        'ip_address',
        'user_agent',
        'device_label',
        'ip_hash',
        'user_agent_hash',
        'http_method',
        'route_name',
        'status_code',
        'previous_hash',
        'entry_hash',
        'created_at',
    ];

    /**
     * @param array<string, mixed> $activityLogData
     */
    public function append(array $activityLogData): int
    {
        $insertedIdentifier = $this->insert($activityLogData, true);

        return (int) $insertedIdentifier;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(array $filters, int $limit, int $offset): array
    {
        return $this->activitySearchBuilder($filters, true)
            ->select('fms_t_activity_logs.id, fms_t_activity_logs.uuid, fms_t_activity_logs.request_id, fms_t_activity_logs.actor_user_id, fms_t_activity_logs.event, fms_t_activity_logs.module, fms_t_activity_logs.entity_type, fms_t_activity_logs.entity_id, fms_t_activity_logs.description, fms_t_activity_logs.ip_address, fms_t_activity_logs.device_label, fms_t_activity_logs.http_method, fms_t_activity_logs.route_name, fms_t_activity_logs.status_code, fms_t_activity_logs.created_at, fms_m_users.full_name AS actor_name, fms_m_users.username AS actor_username')
            ->select("CASE WHEN COALESCE(fms_t_activity_logs.before_json, '{}') <> '{}' OR COALESCE(fms_t_activity_logs.after_json, '{}') <> '{}' THEN 1 ELSE 0 END AS has_changes", false)
            ->orderBy('fms_t_activity_logs.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countSearch(array $filters): int
    {
        $row = $this->activitySearchBuilder($filters, false)
            ->select('COUNT(*) AS cnt')
            ->get()
            ->getRow();

        return (int) ($row->cnt ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $activityLogUuid): ?array
    {
        if (trim($activityLogUuid) === '') {
            return null;
        }

        $foundActivityLog = $this->where('uuid', trim($activityLogUuid))->first();

        if (! is_array($foundActivityLog)) {
            return null;
        }

        return $foundActivityLog;
    }

    public function latestEntryHash(): ?string
    {
        $latestActivityLog = $this
            ->select('entry_hash')
            ->orderBy('id', 'DESC')
            ->first();

        if (! is_array($latestActivityLog)) {
            return null;
        }

        $latestEntryHash = $latestActivityLog['entry_hash'] ?? null;

        return is_string($latestEntryHash) && $latestEntryHash !== '' ? $latestEntryHash : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function activitySearchBuilder(array $filters, bool $withActorJoin = true)
    {
        $db = $this->db;
        $query = $db->table('t_activity_logs');

        if ($withActorJoin) {
            $query->join('m_users', 'fms_m_users.id = fms_t_activity_logs.actor_user_id', 'left');
        }

        $exact = [
            'fms_t_activity_logs.actor_user_id' => $filters['actor_user_id'] ?? null,
            'fms_t_activity_logs.event'          => $filters['event'] ?? null,
            'fms_t_activity_logs.module'         => $filters['module'] ?? null,
            'fms_t_activity_logs.entity_type'    => $filters['entity_type'] ?? null,
            'fms_t_activity_logs.entity_id'      => $filters['entity_id'] ?? null,
            'fms_t_activity_logs.http_method'    => $filters['http_method'] ?? null,
            'fms_t_activity_logs.status_code'    => $filters['status_code'] ?? null,
            'fms_t_activity_logs.request_id'     => $filters['request_id'] ?? null,
        ];
        foreach ($exact as $col => $val) {
            if ($val !== null && $val !== '') {
                $query->where($col, $val);
            }
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo   = trim((string) ($filters['date_to'] ?? ''));
        if ($dateFrom !== '') {
            $query->where('fms_t_activity_logs.created_at >=', $dateFrom . (strlen($dateFrom) === 10 ? ' 00:00:00' : ''));
        }
        if ($dateTo !== '') {
            $query->where('fms_t_activity_logs.created_at <=', $dateTo . (strlen($dateTo) === 10 ? ' 23:59:59' : ''));
        }

        $keyword = trim((string) ($filters['search_keyword'] ?? ''));
        if ($keyword !== '') {
            $query->groupStart()
                ->like('fms_t_activity_logs.event', $keyword)
                ->orLike('fms_t_activity_logs.description', $keyword)
                ->orLike('fms_t_activity_logs.entity_id', $keyword)
                ->groupEnd();
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applySearchFilters(array $filters): self
    {
        $filteredQuery = $this;
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'actor_user_id');
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'event');
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'module');
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'entity_type');
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'entity_id');
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'http_method');
        $filteredQuery = $this->applyExactFilter($filteredQuery, $filters, 'status_code');

        if (isset($filters['request_id']) && is_string($filters['request_id']) && $filters['request_id'] !== '') {
            $filteredQuery = $filteredQuery->where('request_id', $filters['request_id']);
        }

        if (isset($filters['date_from']) && is_string($filters['date_from']) && $filters['date_from'] !== '') {
            $filteredQuery = $filteredQuery->where('created_at >=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && is_string($filters['date_to']) && $filters['date_to'] !== '') {
            $filteredQuery = $filteredQuery->where('created_at <=', $filters['date_to']);
        }

        if (isset($filters['search_keyword']) && is_string($filters['search_keyword']) && $filters['search_keyword'] !== '') {
            $filteredQuery = $filteredQuery
                ->groupStart()
                ->like('event', $filters['search_keyword'])
                ->orLike('description', $filters['search_keyword'])
                ->orLike('entity_id', $filters['search_keyword'])
                ->groupEnd();
        }

        return $filteredQuery;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyExactFilter(self $filteredQuery, array $filters, string $filterKey): self
    {
        if (! array_key_exists($filterKey, $filters)) {
            return $filteredQuery;
        }

        $filterValue = $filters[$filterKey];

        if ($filterValue === null || $filterValue === '') {
            return $filteredQuery;
        }

        return $filteredQuery->where($filterKey, $filterValue);
    }
}
