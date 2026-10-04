<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$moduleRootPath = APPPATH . 'Modules' . DIRECTORY_SEPARATOR;
$moduleDirectoryNames = is_dir($moduleRootPath) ? scandir($moduleRootPath) : [];

if ($moduleDirectoryNames === false) {
    $moduleDirectoryNames = [];
}

sort($moduleDirectoryNames, SORT_STRING);

foreach ($moduleDirectoryNames as $moduleDirectoryName) {
    if ($moduleDirectoryName === '.' || $moduleDirectoryName === '..') {
        continue;
    }

    $moduleConfigPath = $moduleRootPath . $moduleDirectoryName . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
    if (!is_dir($moduleConfigPath)) {
        continue;
    }

    $publicRoutesPath = $moduleConfigPath . 'PublicRoutes.php';
    if (is_file($publicRoutesPath)) {
        require $publicRoutesPath;
    }
}

$routes->group('api/v1', static function (RouteCollection $routes) use ($moduleDirectoryNames, $moduleRootPath): void {
    foreach ($moduleDirectoryNames as $moduleDirectoryName) {
        if ($moduleDirectoryName === '.' || $moduleDirectoryName === '..') {
            continue;
        }

        $apiRoutesPath = $moduleRootPath . $moduleDirectoryName . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'ApiRoutes.php';
        if (is_file($apiRoutesPath)) {
            require $apiRoutesPath;
        }
    }
});

$routes->group(ROUTE_ADMIN, ['filter' => 'fms-backend-authentication'], static function (RouteCollection $routes) use ($moduleDirectoryNames, $moduleRootPath): void {
    foreach ($moduleDirectoryNames as $moduleDirectoryName) {
        if ($moduleDirectoryName === '.' || $moduleDirectoryName === '..') {
            continue;
        }

        $backendRoutesPath = $moduleRootPath . $moduleDirectoryName . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'BackendRoutes.php';
        if (is_file($backendRoutesPath)) {
            require $backendRoutesPath;
        }
    }
});
