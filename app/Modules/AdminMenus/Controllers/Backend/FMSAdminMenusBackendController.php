<?php

namespace App\Modules\AdminMenus\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSAdminMenusBackendController extends FMSBackendController
{
    public function index(): string
    {
        if (! $this->hasBackendPermission('menus.view')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->fmsLayout('index');
    }
}
