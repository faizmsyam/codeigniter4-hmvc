<?php

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Uploads\Controllers\Api\FMSUploadsApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$uploadsApiFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->post('uploads/images', [FMSUploadsApiController::class, 'store'], ['as' => 'fms.api.uploads.images.store', 'filter' => $uploadsApiFilter]);
$routes->get('uploads/buckets/(:segment)/objects/(:any)', [FMSUploadsApiController::class, 'download'], ['as' => 'fms.api.uploads.objects.download']);
