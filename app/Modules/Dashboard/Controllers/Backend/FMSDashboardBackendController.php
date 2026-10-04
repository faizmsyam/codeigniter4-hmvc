<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Dashboard\Controllers\Backend;

use App\Core\FMSBackendController;

class FMSDashboardBackendController extends FMSBackendController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $this->requireBackendPermission('dashboard.read');

        return $this->fmsLayout('index');
    }
}
