<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Modules\Authentication\Controllers\AuthenticationController;

$routes->get('/in', [AuthenticationController::class, 'login']);
$routes->get('/out', [AuthenticationController::class, 'logout']);