<?php

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Brand\Controllers\Api\FMSBrandApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$registeredFilters = config(Config\Filters::class)->aliases;
$brandApiFilter = array_key_exists('fms-api-authentication', $registeredFilters)
    ? 'fms-api-authentication'
    : FMSApiAuthenticationFilter::class;

$routes->group('brand', ['filter' => $brandApiFilter], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSBrandApiController::class, 'show'], ['as' => 'fms.api.brand.show']);
    $routes->post('/', [FMSBrandApiController::class, 'store'], ['as' => 'fms.api.brand.store']);
    $routes->post('images', [FMSBrandApiController::class, 'uploadImages'], ['as' => 'fms.api.brand.images.upload']);
    $routes->put('/', [FMSBrandApiController::class, 'update'], ['as' => 'fms.api.brand.update']);
    $routes->patch('/', [FMSBrandApiController::class, 'update'], ['as' => 'fms.api.brand.patch']);
});
