<?php

use App\Modules\Brand\Controllers\Public\FMSBrandPublicController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('brand/logo', [FMSBrandPublicController::class, 'logo'], ['as' => 'fms.brand.logo']);
$routes->get('brand/logo-light', [FMSBrandPublicController::class, 'logoLight'], ['as' => 'fms.brand.logo.light']);
$routes->get('brand/favicon', [FMSBrandPublicController::class, 'favicon'], ['as' => 'fms.brand.favicon']);
