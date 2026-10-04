<?php

use App\Modules\Home\Controllers\Frontend\FMSHomeController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', [FMSHomeController::class, 'index'], ['as' => 'fms.home']);
