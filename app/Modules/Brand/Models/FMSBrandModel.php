<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Brand\Models;

use App\Core\FMSModel;

/**
 * Persistence gateway for the Brand configuration slice.
 * Logical table c_brands maps to physical fms_c_brands through DBPrefix.
 */
final class FMSBrandModel extends FMSModel
{
    protected $table = 'c_brands';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'uuid',
        'name',
        'tagline',
        'description',
        'email',
        'email_normalized',
        'address',
        'phone',
        'website_url',
        'whatsapp',
        'social_links',
        'logo_path',
        'logo_light_path',
        'favicon_path',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function tableName(): string
    {
        return 'c_brands';
    }

    /**
     * @return array<int, string>
     */
    public function allowedFieldNames(): array
    {
        return $this->allowedFields;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $brandUuid): ?array
    {
        $foundBrandRow = $this->where('uuid', $brandUuid)->first();

        return is_array($foundBrandRow) ? $foundBrandRow : null;
    }
}
