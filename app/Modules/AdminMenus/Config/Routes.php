<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use CodeIgniter\Router\RouteCollection;
use App\Modules\AdminMenus\Controllers\Backend\FMSAdminMenusBackendController;

/** @var RouteCollection $routes */

$routes->get('admin-menus', [FMSAdminMenusBackendController::class, 'index']);