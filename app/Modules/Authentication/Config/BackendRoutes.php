<?php

use App\Modules\Authentication\Controllers\FMSAuthenticationController;
use CodeIgniter\Router\RouteCollection;

/**
 * Backend routes for Authentication module.
 * These are loaded INSIDE the ROUTE_ADMIN group (fms-admin).
 *
 * @var RouteCollection $routes
 */
$routes->get('change-password', [FMSAuthenticationController::class, 'changePassword'], ['as' => 'fms.admin.change-password']);
