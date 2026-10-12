<?php
use App\Modules\Storage\Controllers\Auth\FMSStorageAuthController;
use App\Modules\Storage\Controllers\Backend\FMSStorageBackendController;
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get('fms-storage/login', [FMSStorageAuthController::class, 'login'], ['as' => 'fms.storage.login']);
$routes->post('fms-storage/login', [FMSStorageAuthController::class, 'authenticate'], ['as' => 'fms.storage.authenticate']);
$routes->get('fms-storage/logout', [FMSStorageAuthController::class, 'logout'], ['as' => 'fms.storage.logout']);
$routes->get('fms-storage/share/(:segment)', [FMSStorageAuthController::class, 'share'], ['as' => 'fms.storage.share']);
$routes->group('fms-storage', ['filter' => 'fms-storage-authentication'], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSStorageBackendController::class, 'index'], ['as' => 'fms.storage.index']);
    $routes->get('settings', [FMSStorageBackendController::class, 'settings'], ['as' => 'fms.storage.settings']);
    $routes->get('users', [FMSStorageBackendController::class, 'users'], ['as' => 'fms.storage.users']);
});
