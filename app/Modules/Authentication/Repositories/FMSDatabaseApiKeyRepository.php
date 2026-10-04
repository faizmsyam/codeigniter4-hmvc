<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Authentication\Contracts\FMSApiKeyRepositoryInterface;
use App\Modules\Authentication\Models\FMSApiKeyModel;

final class FMSDatabaseApiKeyRepository implements FMSApiKeyRepositoryInterface
{
    public function __construct(
        private readonly FMSApiKeyModel $apiKeyModel = new FMSApiKeyModel(),
    ) {
    }

    public function create(array $attributes): int
    {
        return (int) $this->apiKeyModel->insert($attributes, true);
    }

    public function findByKeyIdentifier(string $keyIdentifier): ?array
    {
        $keyRow = $this->apiKeyModel
            ->where('key_id', $keyIdentifier)
            ->first();

        return is_array($keyRow) ? $keyRow : null;
    }

    public function update(int $apiKeyIdentifier, array $attributes): bool
    {
        return (bool) $this->apiKeyModel->update($apiKeyIdentifier, $attributes);
    }
}
