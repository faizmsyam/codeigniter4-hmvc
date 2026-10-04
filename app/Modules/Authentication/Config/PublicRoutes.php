<?php

use App\Modules\Authentication\Controllers\FMSAuthenticationController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('fms-auth/in', [FMSAuthenticationController::class, 'login'], ['as' => 'fms.auth.login']);

/* Legacy alias — tetap dukung path lama /in */
$routes->get('in', [FMSAuthenticationController::class, 'login'], ['as' => 'fms.auth.login.legacy']);
