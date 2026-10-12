<@php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use CodeIgniter\Router\RouteCollection;
use {controllerNamespace}\{controllerClass};

/** @var RouteCollection $routes */

$routes->get('{routeUri}', [{controllerClass}::class, 'index']);
