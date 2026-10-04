<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\UserGroups\Controllers\Backend;

use App\Core\FMSBackendController;

class FMSUserGroupsBackendController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('user_groups.read');

        return $this->fmsLayout('index');
    }
}
