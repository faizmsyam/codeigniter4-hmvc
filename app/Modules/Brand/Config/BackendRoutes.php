<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Modules\Brand\Controllers\Backend\FMSBrandController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('brand', [FMSBrandController::class, 'index'], ['as' => 'fms.admin.brand']);
