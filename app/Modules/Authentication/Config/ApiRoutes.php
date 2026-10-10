<?php

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Authentication\Controllers\Api\FMSAuthenticationApiController;
use App\Modules\Authentication\Controllers\Api\FMSEmailVerificationApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->post('auth/login', [FMSAuthenticationApiController::class, 'login'], ['as' => 'fms.api.v1.auth.login']);
$routes->post('auth/refresh', [FMSAuthenticationApiController::class, 'refresh'], ['as' => 'fms.api.v1.auth.refresh']);

/* Verifikasi email — endpoint publik tanpa filter autentikasi */
$routes->post('auth/email/resend', [FMSEmailVerificationApiController::class, 'resend'], ['as' => 'fms.api.v1.auth.email.resend']);
$routes->post('auth/email/verify', [FMSEmailVerificationApiController::class, 'verify'], ['as' => 'fms.api.v1.auth.email.verify']);

$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$guardedFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->post('auth/logout', [FMSAuthenticationApiController::class, 'logout'], ['as' => 'fms.api.v1.auth.logout']);
$routes->get('auth/session-status', [FMSAuthenticationApiController::class, 'sessionStatus'], ['as' => 'fms.api.v1.auth.session_status']);
$routes->post('auth/continue-session', [FMSAuthenticationApiController::class, 'continueSession'], ['as' => 'fms.api.v1.auth.continue_session']);
$routes->get('auth/me', [FMSAuthenticationApiController::class, 'me'], ['as' => 'fms.api.v1.auth.me', 'filter' => $guardedFilter]);
