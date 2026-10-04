<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Brand\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSBrandController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('brand.read');

        return $this->fmsLayout('index');
    }
}
