<?php
namespace App\Modules\Storage\Config;

use CodeIgniter\Modules\Modules;

final class FMSModule
{
    public static string $namespace = 'App\\Modules\\Storage';
    public static string $viewPath = APPPATH . 'Modules/Storage/Views/';

    public static function init(): void
    {
        /** @var Modules $modules */
        service('modules')->addNamespace(self::$namespace, APPPATH . 'Modules/Storage/');
    }
}
