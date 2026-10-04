<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\UserGroups\Services;

use App\Modules\UserGroups\Models\FMSUserGroupModel;
use App\Modules\UserGroups\Validation\FMSUserGroupValidation;
use InvalidArgumentException;
use RuntimeException;

final class FMSUserGroupService
{
    private FMSUserGroupModel $model;

    public function __construct(?FMSUserGroupModel $model = null)
    {
        $this->model = $model ?? new FMSUserGroupModel();
    }

    /**
     * @return array{items: list<array>, summary: array, pagination: array}
     */
    public function listGroups(int $page = 1, int $perPage = 10, string $search = '', string $status = ''): array
    {
        $builder = $this->model->builder();

        if ($search !== '') {
            $builder->like('name', $search);
        }

        if ($status === 'active') {
            $builder->where('is_active', 1)->where('deleted_at', null);
        } elseif ($status === 'inactive') {
            $builder->where('is_active', 0)->where('deleted_at', null);
        } elseif ($status === 'deleted') {
            $builder->where('deleted_at IS NOT NULL');
        } else {
            $builder->where('deleted_at', null);
        }

        $total   = $builder->countAllResults(false);
        $offset  = ($page - 1) * $perPage;
        $items   = $builder->orderBy('id', 'ASC')->limit($perPage, $offset)->get()->getResultArray();

        /* Hitung total member per grup, satu query batch per halaman list. */
        $db = \Config\Database::connect();
        $memberCounts = [];
        if ($items !== []) {
            $ids = array_values(array_unique(array_map(static fn ($item): int => (int) ($item['id'] ?? 0), $items)));
            $rows = $db->table('t_user_groups')
                ->select('group_id, COUNT(DISTINCT user_id) AS total', false)
                ->whereIn('group_id', $ids)
                ->groupBy('group_id')
                ->get()
                ->getResultArray();
            foreach ($rows as $r) {
                $memberCounts[(int) $r['group_id']] = (int) $r['total'];
            }
        }

        $presented = array_map(static fn (array $g): array => [
            'id'           => (int) $g['id'],
            'hash'         => fmsEncodeId((int) $g['id']),
            'name'         => (string) ($g['name'] ?? ''),
            'is_active'    => (int) ($g['is_active'] ?? 1),
            'is_system'    => (int) $g['id'] === 1 ? 1 : 0, /* ID 1 Super Admin protected */
            'total_users'  => $memberCounts[(int) $g['id']] ?? 0,
            'member_count' => $memberCounts[(int) $g['id']] ?? 0,
            'is_deleted'   => $g['deleted_at'] !== null,
            'deleted_at'   => $g['deleted_at'] ?? null,
            'created_at'   => (string) ($g['created_at'] ?? ''),
            'updated_at'   => (string) ($g['updated_at'] ?? ''),
        ], $items);

        /* Summary satu query, bukan tiga COUNT terpisah. */
        $summaryRow = $db->table('c_group_users')
            ->select("SUM(CASE WHEN deleted_at IS NULL AND is_active = 1 THEN 1 ELSE 0 END) AS active_count, SUM(CASE WHEN deleted_at IS NULL AND is_active = 0 THEN 1 ELSE 0 END) AS inactive_count, SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted_count", false)
            ->get()
            ->getRowArray() ?? [];
        $totalActive   = (int) ($summaryRow['active_count'] ?? 0);
        $totalInactive = (int) ($summaryRow['inactive_count'] ?? 0);
        $totalDeleted  = (int) ($summaryRow['deleted_count'] ?? 0);
        $totalAll      = $totalActive + $totalInactive;

        return [
            'items'      => $presented,
            'summary'    => [
                'total'    => $totalAll,
                'active'   => $totalActive,
                'inactive' => $totalInactive,
                'deleted'  => $totalDeleted,
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ];
    }

    public function findById(int $id, bool $withDeleted = false): ?array
    {
        $m = $withDeleted ? $this->model->withDeleted() : $this->model;
        $row = $m->find($id);
        if (! is_array($row)) {
            return null;
        }

        $row['hash']         = fmsEncodeId((int) $row['id']);
        $row['is_system']    = (int) $row['id'] === 1 ? 1 : 0;
        $row['member_count'] = $this->countGroupMembers($id);
        $row['total_users']  = $row['member_count'];

        return $row;
    }

    public function createGroup(array $payload, int $actorId): array
    {
        $name = FMSUserGroupValidation::validateName((string) ($payload['name'] ?? ''));

        /* Cek nama duplikat */
        $existing = $this->model->where('name', $name)->first();
        if ($existing !== null) {
            throw new InvalidArgumentException('Nama grup sudah digunakan.');
        }

        $isActive = FMSUserGroupValidation::validateStatus($payload['is_active'] ?? 1);

        $id = $this->model->insert([
            'name'       => $name,
            'is_active'  => $isActive,
            'created_by' => $actorId,
        ]);

        if ($id === false) {
            throw new RuntimeException('Gagal membuat user group baru.');
        }

        return $this->findById((int) $id) ?? [];
    }

    public function updateGroup(int $id, array $payload, int $actorId): array
    {
        $group = $this->findById($id);
        if ($group === null) {
            throw new InvalidArgumentException('User group tidak ditemukan.');
        }

        /* Super Admin ID 1 tidak boleh diubah namanya sembarangan */
        if ((int) $id === 1 && isset($payload['name']) && trim($payload['name']) !== 'Super Administrator') {
            throw new InvalidArgumentException('Nama grup Super Administrator tidak boleh diubah.');
        }

        $data = ['updated_by' => $actorId];

        if (isset($payload['name'])) {
            $name = FMSUserGroupValidation::validateName((string) $payload['name'], $id);
            /* Cek duplikat terhadap grup lain */
            $existing = $this->model->where('name', $name)->where('id !=', $id)->first();
            if ($existing !== null) {
                throw new InvalidArgumentException('Nama grup sudah digunakan oleh grup lain.');
            }
            $data['name'] = $name;
        }

        if (isset($payload['is_active'])) {
            /* Super Admin tidak boleh dinonaktifkan */
            if ((int) $id === 1) {
                $data['is_active'] = 1;
            } else {
                $data['is_active'] = FMSUserGroupValidation::validateStatus($payload['is_active']);
            }
        }

        $updated = $this->model->update($id, $data);
        if ($updated === false) {
            throw new RuntimeException('Gagal mengubah user group.');
        }

        return $this->findById($id) ?? [];
    }

    public function deleteGroup(int $id, int $actorId): bool
    {
        if ((int) $id === 1) {
            throw new InvalidArgumentException('Grup Super Administrator tidak boleh dihapus.');
        }

        if ((int) $id === 2) {
            throw new InvalidArgumentException('Grup Administrator sistem tidak boleh dihapus.');
        }

        $group = $this->findById($id);
        if ($group === null) {
            throw new InvalidArgumentException('User group tidak ditemukan.');
        }

        $this->model->update($id, ['deleted_by' => $actorId]);
        return (bool) $this->model->delete($id);
    }

    public function restoreGroup(int $id, int $actorId): bool
    {
        $group = $this->findById($id, true);
        if ($group === null) {
            throw new InvalidArgumentException('User group tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        return (bool) $db->table('c_group_users')->where('id', $id)->update([
            'deleted_at' => null,
            'deleted_by' => null,
            'updated_by' => $actorId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function changeStatus(int $id, int $status, int $actorId): array
    {
        if ((int) $id === 1) {
            throw new InvalidArgumentException('Status grup Super Administrator tidak boleh diubah.');
        }

        return $this->updateGroup($id, ['is_active' => $status], $actorId);
    }

    /**
     * Daftar anggota grup: t_user_groups JOIN m_users.
     *
     * @return list<array{full_name: string, username: string, email: string, is_active: int}>
     */
    public function listGroupMembers(int $groupId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $db    = \Config\Database::connect();

        $rows = $db->table('t_user_groups')
            ->select('fms_m_users.full_name, fms_m_users.username, fms_m_users.email, fms_m_users.status AS user_status')
            ->join('m_users', 'fms_m_users.id = fms_t_user_groups.user_id', 'inner')
            ->where('fms_t_user_groups.group_id', $groupId)
            ->orderBy('fms_m_users.full_name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'full_name' => (string) ($row['full_name'] ?? ''),
            'username'  => (string) ($row['username'] ?? ''),
            'email'     => (string) ($row['email'] ?? ''),
            'is_active' => (($row['user_status'] ?? '') === 'active') ? 1 : 0,
        ], $rows);
    }

    private function countGroupMembers(int $groupId): int
    {
        $db = \Config\Database::connect();
        return (int) $db->table('t_user_groups')
            ->where('group_id', $groupId)
            ->countAllResults();
    }
}
