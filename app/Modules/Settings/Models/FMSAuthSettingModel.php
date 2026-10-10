<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Models;

use App\Core\FMSModel;

final class FMSAuthSettingModel extends FMSModel
{
    protected $table            = 'c_auth_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'public_registration_enabled',
        'public_email_verification_required',
        'admin_created_email_verification_required',
        'admin_must_change_password',
        'login_rate_limit_enabled',
        'login_max_failures',
        'login_failure_window_seconds',
        'login_lockout_seconds',
        'verification_ttl_minutes',
        'resend_cooldown_seconds',
        'version',
        'updated_by',
        'updated_at',
    ];

    public function currentSettings(): array
    {
        $row = $this->orderBy('id', 'DESC')->first();
        if (is_array($row)) {
            return $row;
        }

        // Default fallback if table is empty
        $default = [
            'public_registration_enabled'               => 0,
            'public_email_verification_required'        => 1,
            'admin_created_email_verification_required' => 0,
            'admin_must_change_password'                => 0,
            'login_rate_limit_enabled'                  => 1,
            'login_max_failures'                        => 5,
            'login_failure_window_seconds'              => 900,
            'login_lockout_seconds'                     => 900,
            'verification_ttl_minutes'                  => 1440,
            'resend_cooldown_seconds'                   => 120,
            'version'                                   => 1,
            'updated_by'                                => null,
            'updated_at'                                => date('Y-m-d H:i:s'),
        ];
        $id = $this->insert($default, true);
        $default['id'] = $id;

        return $default;
    }
}
