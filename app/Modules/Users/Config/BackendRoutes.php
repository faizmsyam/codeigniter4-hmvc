<?php

use App\Modules\Users\Controllers\Backend\FMSUsersBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('users', [FMSUsersBackendController::class, 'index'], ['as' => 'fms.admin.users']);
$routes->get('profile', [\App\Modules\Users\Controllers\Backend\FMSProfileBackendController::class, 'index'], ['as' => 'fms.admin.profile']);
