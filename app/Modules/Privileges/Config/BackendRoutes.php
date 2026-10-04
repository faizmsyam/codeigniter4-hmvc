<?php

use App\Modules\Privileges\Controllers\Backend\FMSPrivilegesBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('privileges', [FMSPrivilegesBackendController::class, 'index'], ['as' => 'fms.admin.privileges']);
