<?php

use App\Modules\Dashboard\Controllers\Backend\FMSDashboardBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('dashboard', [FMSDashboardBackendController::class, 'index'], ['as' => 'fms.admin.dashboard']);
