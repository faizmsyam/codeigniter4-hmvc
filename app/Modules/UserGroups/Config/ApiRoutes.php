<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\UserGroups\Controllers\Api\FMSUserGroupsApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$userGroupsApiFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->group('user-groups', ['filter' => $userGroupsApiFilter], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSUserGroupsApiController::class, 'index'], ['as' => 'fms.api.v1.user-groups.index']);
    $routes->post('/', [FMSUserGroupsApiController::class, 'create'], ['as' => 'fms.api.v1.user-groups.create']);
    $routes->get('(:segment)', [FMSUserGroupsApiController::class, 'show'], ['as' => 'fms.api.v1.user-groups.show']);
    $routes->patch('(:segment)', [FMSUserGroupsApiController::class, 'update'], ['as' => 'fms.api.v1.user-groups.update']);
    $routes->delete('(:segment)', [FMSUserGroupsApiController::class, 'destroy'], ['as' => 'fms.api.v1.user-groups.delete']);
    $routes->post('(:segment)/restore', [FMSUserGroupsApiController::class, 'restore'], ['as' => 'fms.api.v1.user-groups.restore']);
    $routes->patch('(:segment)/status', [FMSUserGroupsApiController::class, 'changeStatus'], ['as' => 'fms.api.v1.user-groups.status']);
    $routes->get('(:segment)/members', [FMSUserGroupsApiController::class, 'members'], ['as' => 'fms.api.v1.user-groups.members']);
});
