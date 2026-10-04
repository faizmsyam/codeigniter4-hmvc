<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Users\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSUsersBackendController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('users.read');

        return $this->fmsLayout('index');
    }
}