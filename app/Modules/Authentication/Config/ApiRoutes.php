<?php

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Authentication\Controllers\Api\FMSAuthenticationApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->post('auth/login', [FMSAuthenticationApiController::class, 'login'], ['as' => 'fms.api.v1.auth.login']);
$routes->post('auth/refresh', [FMSAuthenticationApiController::class, 'refresh'], ['as' => 'fms.api.v1.auth.refresh']);

$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$guardedFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->post('auth/logout', [FMSAuthenticationApiController::class, 'logout'], ['as' => 'fms.api.v1.auth.logout']);
$routes->get('auth/me', [FMSAuthenticationApiController::class, 'me'], ['as' => 'fms.api.v1.auth.me', 'filter' => $guardedFilter]);
