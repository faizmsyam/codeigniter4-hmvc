<?php

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Privileges\Controllers\Api\FMSPrivilegesApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$privilegesApiFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->group('groups', ['filter' => $privilegesApiFilter], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSPrivilegesApiController::class, 'groups'], ['as' => 'fms.api.v1.groups.index']);
    $routes->post('/', [FMSPrivilegesApiController::class, 'createGroup'], ['as' => 'fms.api.v1.groups.create']);
    $routes->get('(:segment)', [FMSPrivilegesApiController::class, 'group'], ['as' => 'fms.api.v1.groups.show']);
    $routes->patch('(:segment)', [FMSPrivilegesApiController::class, 'updateGroup'], ['as' => 'fms.api.v1.groups.update']);
    $routes->delete('(:segment)', [FMSPrivilegesApiController::class, 'deleteGroup'], ['as' => 'fms.api.v1.groups.delete']);
    $routes->get('(:segment)/permissions', [FMSPrivilegesApiController::class, 'groupPermissions'], ['as' => 'fms.api.v1.groups.permissions.index']);
    $routes->put('(:segment)/permissions', [FMSPrivilegesApiController::class, 'replaceGroupPermissions'], ['as' => 'fms.api.v1.groups.permissions.replace']);
});

$routes->group('permissions', ['filter' => $privilegesApiFilter], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSPrivilegesApiController::class, 'permissions'], ['as' => 'fms.api.v1.permissions.index']);
    $routes->post('/', [FMSPrivilegesApiController::class, 'createPermission'], ['as' => 'fms.api.v1.permissions.create']);
    $routes->get('(:segment)', [FMSPrivilegesApiController::class, 'permission'], ['as' => 'fms.api.v1.permissions.show']);
    $routes->patch('(:segment)', [FMSPrivilegesApiController::class, 'updatePermission'], ['as' => 'fms.api.v1.permissions.update']);
    $routes->delete('(:segment)', [FMSPrivilegesApiController::class, 'deletePermission'], ['as' => 'fms.api.v1.permissions.delete']);
});

$routes->group('menus', ['filter' => $privilegesApiFilter], static function (RouteCollection $routes): void {
    $routes->get('(:segment)/permissions', [FMSPrivilegesApiController::class, 'menuPermissions'], ['as' => 'fms.api.v1.menus.permissions.index']);
    $routes->put('(:segment)/permissions', [FMSPrivilegesApiController::class, 'replaceMenuPermissions'], ['as' => 'fms.api.v1.menus.permissions.replace']);
});

$routes->group('users', ['filter' => $privilegesApiFilter], static function (RouteCollection $routes): void {
    $routes->get('(:segment)/groups', [FMSPrivilegesApiController::class, 'userGroups'], ['as' => 'fms.api.v1.users.groups.index']);
    $routes->put('(:segment)/groups', [FMSPrivilegesApiController::class, 'replaceUserGroups'], ['as' => 'fms.api.v1.users.groups.replace']);
});

$routes->group('privileges', ['filter' => $privilegesApiFilter], static function (RouteCollection $routes): void {
    $routes->get('overview', [FMSPrivilegesApiController::class, 'overviewWithHashes'], ['as' => 'fms.api.v1.privileges.overview']);
    $routes->get('groups/(:segment)/permissions', [FMSPrivilegesApiController::class, 'groupPermissionsWithHash'], ['as' => 'fms.api.v1.privileges.group-permissions.index']);
    $routes->put('groups/(:segment)/permissions', [FMSPrivilegesApiController::class, 'replaceGroupPermissionsWithHash'], ['as' => 'fms.api.v1.privileges.group-permissions.replace']);
});
