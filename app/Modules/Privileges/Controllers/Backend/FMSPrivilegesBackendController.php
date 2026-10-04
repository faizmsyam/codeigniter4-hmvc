<?php

namespace App\Modules\Privileges\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSPrivilegesBackendController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('privileges.manage');

        return $this->fmsLayout('index');
    }
}
