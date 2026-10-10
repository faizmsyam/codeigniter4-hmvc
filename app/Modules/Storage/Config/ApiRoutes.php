<?php
use App\Filters\FMSStorageAuthenticationFilter;
use App\Modules\Storage\Controllers\Api\FMSStorageApiController;
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->post('storage/login', [FMSStorageApiController::class,'login']);
$routes->post('storage/logout', [FMSStorageApiController::class,'logout'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->get('storage/objects', [FMSStorageApiController::class,'objects'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->post('storage/objects', [FMSStorageApiController::class,'upload'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->delete('storage/objects', [FMSStorageApiController::class,'delete'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->post('storage/presign', [FMSStorageApiController::class,'presign'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->post('storage/share', [FMSStorageApiController::class,'createShare'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->get('storage/profile', [FMSStorageApiController::class,'profile'], ['filter'=>FMSStorageAuthenticationFilter::class]);
$routes->post('storage/profile/password', [FMSStorageApiController::class,'changePassword'], ['filter'=>FMSStorageAuthenticationFilter::class]);
