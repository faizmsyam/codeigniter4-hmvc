<?php 

use App\Modules\Home\Controllers\Frontend\HomeController;

$routes->get('/', [HomeController::class, 'index']);