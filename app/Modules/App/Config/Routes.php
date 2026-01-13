<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Modules\App\Controllers\Backend\AppController;

$routes->get('/' . ROUTE_ADMIN, [AppController::class, 'index']);