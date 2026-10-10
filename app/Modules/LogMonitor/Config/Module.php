<?php

namespace App\Modules\LogMonitor\Config;

use CodeIgniter\Modules\Modules;

class Module
{
    public static $namespace = 'App\Modules\LogMonitor';
    public static $viewPath = APPPATH . 'Modules/LogMonitor/Views/';
    public static $configPath = APPPATH . 'Modules/LogMonitor/Config/';

    public static function init(): void
    {
        /** @var Modules $modules */
        $modules = service('modules');
        $modules->addNamespace(self::$namespace, APPPATH . 'Modules/LogMonitor/');
    }
}
