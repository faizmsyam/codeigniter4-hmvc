<?php

namespace App\Modules\Settings\Models;

use App\Core\FMSModel;

final class FMSEmailSettingModel extends FMSModel
{
    protected $table            = 'c_email_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'enabled', 'protocol', 'smtp_host', 'smtp_port', 'smtp_user',
        'smtp_password_encrypted', 'smtp_crypto', 'from_email', 'from_name',
        'reply_to', 'timeout_seconds', 'version', 'updated_by', 'updated_at',
    ];

    public function currentSettings(): array
    {
        $row = $this->orderBy('id', 'DESC')->first();
        if (is_array($row)) {
            return $row;
        }

        $default = [
            'enabled' => 0,
            'protocol' => 'smtp',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_password_encrypted' => null,
            'smtp_crypto' => 'tls',
            'from_email' => '',
            'from_name' => '',
            'reply_to' => null,
            'timeout_seconds' => 10,
            'version' => 1,
            'updated_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $default['id'] = $this->insert($default, true);

        return $default;
    }
}
