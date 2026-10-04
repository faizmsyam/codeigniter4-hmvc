<?php

use App\Modules\AdminMenus\Controllers\Backend\FMSAdminMenusBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('admin-menus', [FMSAdminMenusBackendController::class, 'index'], ['as' => 'fms.admin.menus']);
