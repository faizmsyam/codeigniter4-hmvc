<?php

namespace App\Modules\ActivityLogs\Validation;

final class FMSActivityLogValidation
{
    /**
     * @return array<string, array<string, string>>
     */
    public function getListRules(): array
    {
        return [
            'actor_user_id' => [
                'label' => 'Actor pengguna',
                'rules' => 'permit_empty|is_natural_no_zero',
            ],
            'event' => [
                'label' => 'Event',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'module' => [
                'label' => 'Modul',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'entity_type' => [
                'label' => 'Tipe entitas',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'entity_id' => [
                'label' => 'ID entitas',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'http_method' => [
                'label' => 'Metode HTTP',
                'rules' => 'permit_empty|in_list[GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS]',
            ],
            'status_code' => [
                'label' => 'Kode status',
                'rules' => 'permit_empty|is_natural_no_zero|less_than[600]',
            ],
            'request_id' => [
                'label' => 'ID request',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'date_from' => [
                'label' => 'Tanggal mulai',
                'rules' => 'permit_empty|valid_date[Y-m-d]',
            ],
            'date_to' => [
                'label' => 'Tanggal selesai',
                'rules' => 'permit_empty|valid_date[Y-m-d]',
            ],
            'search_keyword' => [
                'label' => 'Kata kunci',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'page' => [
                'label' => 'Halaman',
                'rules' => 'permit_empty|is_natural_no_zero',
            ],
            'per_page' => [
                'label' => 'Baris per halaman',
                'rules' => 'permit_empty|is_natural_no_zero|less_than_equal_to[100]',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getIdentifierRules(): array
    {
        return [
            'uuid' => [
                'label' => 'UUID activity log',
                'rules' => 'required|regex_match[/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/]',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getExportRules(): array
    {
        $exportRules = $this->getListRules();
        unset($exportRules['page'], $exportRules['per_page']);

        $exportRules['date_from'] = [
            'label' => 'Tanggal mulai',
            'rules' => 'required|valid_date[Y-m-d]',
        ];
        $exportRules['date_to'] = [
            'label' => 'Tanggal selesai',
            'rules' => 'required|valid_date[Y-m-d]',
        ];

        return $exportRules;
    }
}
