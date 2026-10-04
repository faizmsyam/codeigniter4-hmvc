<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\AdminMenus\Validation;

/**
 * Validation rule sets for the AdminMenus domain.
 * Rule arrays are consumed by CodeIgniter validation without global config edits.
 */
final class FMSAdminMenuValidation
{
    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    public function getCreateMenuRules(): array
    {
        return [
            'name' => [
                'label' => 'Nama menu',
                'rules' => 'required|string|min_length[2]|max_length[100]',
            ],
            'id_parent' => [
                'label' => 'Menu induk',
                'rules' => 'permit_empty|is_natural_no_zero',
            ],
            'url' => [
                'label' => 'URL menu',
                'rules' => 'permit_empty|string|max_length[100]',
            ],
            'icon' => [
                'label' => 'Ikon menu',
                'rules' => 'permit_empty|string',
            ],
            'position' => [
                'label' => 'Posisi menu',
                'rules' => 'permit_empty|is_natural',
            ],
            'is_active' => [
                'label' => 'Status aktif',
                'rules' => 'permit_empty|in_list[0,1]',
            ],
            'target_blank' => [
                'label' => 'Buka tab baru',
                'rules' => 'permit_empty|in_list[0,1]',
            ],
        ];
    }

    /**
     * PATCH semantics: every field is optional, but a supplied field is still
     * validated with the same rules as create.
     *
     * @return array<string, array<string, array<string, string>|string>>
     */
    public function getUpdateMenuRules(): array
    {
        $updateRules = $this->getCreateMenuRules();
        $updateRules['name']['rules'] = 'permit_empty|string|min_length[2]|max_length[100]';

        return $updateRules;
    }

    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    public function getReorderRules(): array
    {
        return [
            'ordering' => [
                'label' => 'Daftar urutan menu',
                'rules' => 'required',
            ],
        ];
    }
}
