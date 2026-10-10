<?php

use App\Modules\Authentication\Controllers\FMSAuthenticationController;
use App\Modules\Authentication\Controllers\Api\FMSEmailVerificationApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('fms-auth/in', [FMSAuthenticationController::class, 'login'], ['as' => 'fms.auth.login']);
$routes->get('fms-auth/out', [FMSAuthenticationController::class, 'logout'], ['as' => 'fms.auth.logout']);
$routes->get('fms-auth/change-password', [FMSAuthenticationController::class, 'changePassword'], ['as' => 'fms.auth.change-password']);
$routes->get('fms-auth/verify-email', [FMSAuthenticationController::class, 'verifyEmail'], ['as' => 'fms.auth.verify-email']);

/* Legacy alias — tetap dukung path lama /in */
$routes->get('in', [FMSAuthenticationController::class, 'login'], ['as' => 'fms.auth.login.legacy']);
