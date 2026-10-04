<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Uploads\Models;

use App\Core\FMSModel;

/**
 * Persistence gateway for stored image metadata.
 * Logical table t_uploads maps to physical fms_t_uploads through DBPrefix.
 */
final class FMSUploadModel extends FMSModel
{
    protected $table = 't_file_uploads';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'uuid',
        'public_identifier',
        'owner_user_id',
        'module_name',
        'entity_type',
        'entity_identifier',
        'storage_driver',
        'bucket_name',
        'object_key',
        'original_mime_type',
        'output_mime_type',
        'original_byte_size',
        'output_byte_size',
        'image_width',
        'image_height',
        'content_hash',
        'status',
        'created_by',
    ];

    public function tableName(): string
    {
        return 't_file_uploads';
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
    public function findByStoredFileName(string $storedFileName): ?array
    {
        $foundUploadRow = $this->where('object_key', $storedFileName)->first();

        return is_array($foundUploadRow) ? $foundUploadRow : null;
    }
}
