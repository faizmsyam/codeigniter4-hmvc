<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$modules_path = APPPATH . 'Modules/';
$modules = scandir($modules_path);

foreach ($modules as $module) {
	if ($module === '.' || $module === '..') {
		continue;
	}

	if (is_dir($modules_path . $module)) {
		$routes_path = $modules_path . $module . '/Config/Routes.php';
		if (file_exists($routes_path)) {
			require $routes_path;
		}
	}
}
