<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Uploads\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSUploadsController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('uploads.create');

        return $this->fmsLayout('index');
    }
}
