<?php

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Users\Controllers\Api\FMSProfileApiController;
use App\Modules\Users\Controllers\Api\FMSUsersApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$usersApiFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->group('users', ['filter' => $usersApiFilter], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSUsersApiController::class, 'index'], ['as' => 'fms.api.v1.users.index']);
    $routes->post('/', [FMSUsersApiController::class, 'create'], ['as' => 'fms.api.v1.users.create']);
    $routes->get('(:segment)', [FMSUsersApiController::class, 'show'], ['as' => 'fms.api.v1.users.show']);
    $routes->patch('(:segment)', [FMSUsersApiController::class, 'update'], ['as' => 'fms.api.v1.users.update']);
    $routes->delete('(:segment)', [FMSUsersApiController::class, 'destroy'], ['as' => 'fms.api.v1.users.delete']);
    $routes->post('(:segment)/restore', [FMSUsersApiController::class, 'restore']);
    $routes->post('(:segment)/unlock', [FMSUsersApiController::class, 'unlock']);
    $routes->post('(:segment)/reset-password', [FMSUsersApiController::class, 'resetPassword']);
    $routes->post('(:segment)/verify-email', [FMSUsersApiController::class, 'verifyEmail']);
    $routes->post('(:segment)/unverify-email', [FMSUsersApiController::class, 'unverifyEmail']);
    $routes->patch('(:segment)/status', [FMSUsersApiController::class, 'changeStatus']);
    $routes->get('(:segment)/sessions', [FMSUsersApiController::class, 'sessions']);
    $routes->delete('(:segment)/sessions', [FMSUsersApiController::class, 'revokeSessions']);
    $routes->get('(:segment)/groups', [FMSUsersApiController::class, 'groups']);
    $routes->put('(:segment)/groups', [FMSUsersApiController::class, 'replaceGroups']);
});

$routes->group('profile', ['filter' => $usersApiFilter], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSProfileApiController::class, 'show'], ['as' => 'fms.api.v1.profile.show']);
    $routes->get('activity-logs', [FMSProfileApiController::class, 'activityLogs'], ['as' => 'fms.api.v1.profile.activity-logs']);
    $routes->get('activity-logs/(:segment)', [FMSProfileApiController::class, 'activityLogDetail'], ['as' => 'fms.api.v1.profile.activity-log-detail']);
    $routes->post('switch-group', [FMSProfileApiController::class, 'switchGroup'], ['as' => 'fms.api.v1.profile.switch-group']);
    $routes->patch('/', [FMSProfileApiController::class, 'updateProfile'], ['as' => 'fms.api.v1.profile.update']);
    $routes->post('avatar', [FMSProfileApiController::class, 'updateAvatar'], ['as' => 'fms.api.v1.profile.avatar']);
    $routes->post('change-password', [FMSProfileApiController::class, 'changePassword'], ['as' => 'fms.api.v1.profile.change-password']);
    $routes->post('force-change-password', [FMSProfileApiController::class, 'forceChangePassword'], ['as' => 'fms.api.v1.profile.force-change-password']);
});
