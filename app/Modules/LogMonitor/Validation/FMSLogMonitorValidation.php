<?php

namespace App\Modules\LogMonitor\Validation;

use CodeIgniter\Validation\Validation;

final class FMSLogMonitorValidation
{
    public function getFilesRules(): array
    {
        return [
            'path' => [
                'label' => 'Path',
                'rules' => 'permit_empty|max_length[255]',
            ],
        ];
    }

    public function getReadRules(): array
    {
        return [
            'path' => [
                'label' => 'Path',
                'rules' => 'required|max_length[255]',
            ],
            'offset' => [
                'label' => 'Offset',
                'rules' => 'permit_empty|integer|greater_than_equal_to[0]',
            ],
            'limit' => [
                'label' => 'Limit',
                'rules' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[10000]',
            ],
            'search' => [
                'label' => 'Search',
                'rules' => 'permit_empty|max_length[255]',
            ],
        ];
    }

    public function getStatsRules(): array
    {
        return [
            'path' => [
                'label' => 'Path',
                'rules' => 'permit_empty|max_length[255]',
            ],
        ];
    }

    public function getDownloadRules(): array
    {
        return [
            'path' => [
                'label' => 'Path',
                'rules' => 'required|max_length[255]',
            ],
        ];
    }
}
