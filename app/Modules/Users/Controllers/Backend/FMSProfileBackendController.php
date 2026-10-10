<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Users\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSProfileBackendController extends FMSBackendController
{
    /**
     * Backend hanya merender shell. Seluruh data dan operasi Profile via API.
     */
    public function index(): string
    {
        $this->requireBackendPermission('profile.read');

        $this->fmsMeta(['title' => 'Profil Saya']);

        return $this->fmsLayout('profile', [
            'profileApiUrl'     => site_url('api/v1/profile'),
            'switchGroupUrl'    => site_url('api/v1/profile/switch-group'),
            'updateUrl'         => site_url('api/v1/profile'),
            'avatarUrlUpload'   => site_url('api/v1/profile/avatar'),
            'changePasswordUrl' => site_url('api/v1/profile/change-password'),
            'activityLogsUrl'       => site_url('api/v1/profile/activity-logs'),
            'activityLogDetailUrl'  => site_url('api/v1/profile/activity-logs'),
        ]);
    }
}
