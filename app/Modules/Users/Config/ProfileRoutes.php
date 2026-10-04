<?php

use App\Modules\Users\Controllers\Backend\FMSProfileBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('profile', [FMSProfileBackendController::class, 'index'], ['as' => 'fms.admin.profile']);
