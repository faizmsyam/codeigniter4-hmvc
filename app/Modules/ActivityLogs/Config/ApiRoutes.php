<?php

use App\Modules\ActivityLogs\Controllers\Api\FMSActivityLogsApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('activity-logs', ['filter' => 'fms-api-authentication'], static function (RouteCollection $routes): void {
    $routes->get('/', [FMSActivityLogsApiController::class, 'index'], ['as' => 'api.v1.activity-logs.index']);
    $routes->get('export', [FMSActivityLogsApiController::class, 'export'], ['as' => 'api.v1.activity-logs.export']);
    $routes->get('(:segment)', [FMSActivityLogsApiController::class, 'show'], ['as' => 'api.v1.activity-logs.show']);
});
