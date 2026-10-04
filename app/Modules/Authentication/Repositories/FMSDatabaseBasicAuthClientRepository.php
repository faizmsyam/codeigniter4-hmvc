<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Authentication\Contracts\FMSBasicAuthClientRepositoryInterface;
use App\Modules\Authentication\Models\FMSBasicAuthClientModel;

final class FMSDatabaseBasicAuthClientRepository implements FMSBasicAuthClientRepositoryInterface
{
    public function __construct(
        private readonly FMSBasicAuthClientModel $clientModel = new FMSBasicAuthClientModel(),
    ) {
    }

    public function findByNormalizedUsername(string $normalizedUsername): ?array
    {
        $clientRow = $this->clientModel
            ->where('LOWER(username)', $normalizedUsername)
            ->first();

        return is_array($clientRow) ? $clientRow : null;
    }

    public function update(int $clientIdentifier, array $attributes): bool
    {
        return (bool) $this->clientModel->update($clientIdentifier, $attributes);
    }
}
