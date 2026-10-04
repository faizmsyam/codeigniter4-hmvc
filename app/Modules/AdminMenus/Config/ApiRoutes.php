<?php

use App\Modules\AdminMenus\Controllers\Api\FMSAdminMenusApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('menus', ['filter' => 'fms-api-authentication'], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSAdminMenusApiController::class, 'index'], ['as' => 'fms.api.v1.menus.index']);
    $routes->post('/', [FMSAdminMenusApiController::class, 'create'], ['as' => 'fms.api.v1.menus.create']);
    $routes->patch('reorder', [FMSAdminMenusApiController::class, 'reorder'], ['as' => 'fms.api.v1.menus.reorder']);
    $routes->get('(:segment)/actions', [FMSAdminMenusApiController::class, 'menuActions'], ['as' => 'fms.api.v1.menus.actions.index']);
    $routes->post('(:segment)/actions', [FMSAdminMenusApiController::class, 'createMenuAction'], ['as' => 'fms.api.v1.menus.actions.create']);
    $routes->delete('(:segment)/actions/(:segment)', [FMSAdminMenusApiController::class, 'deleteMenuAction'], ['as' => 'fms.api.v1.menus.actions.delete']);
    $routes->get('(:segment)', [FMSAdminMenusApiController::class, 'show'], ['as' => 'fms.api.v1.menus.show']);
    $routes->patch('(:segment)', [FMSAdminMenusApiController::class, 'update'], ['as' => 'fms.api.v1.menus.update']);
    $routes->delete('(:segment)', [FMSAdminMenusApiController::class, 'delete'], ['as' => 'fms.api.v1.menus.delete']);
});

$routes->group('navigation', ['filter' => 'fms-api-authentication'], static function (RouteCollection $routes): void {
    $routes->get('sidebar', [FMSAdminMenusApiController::class, 'sidebar'], ['as' => 'fms.api.v1.navigation.sidebar']);
});
