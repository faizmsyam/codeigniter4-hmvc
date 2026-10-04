<?php

use App\Modules\App\Controllers\Backend\FMSAppController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', [FMSAppController::class, 'index'], ['as' => 'fms.admin.app']);
