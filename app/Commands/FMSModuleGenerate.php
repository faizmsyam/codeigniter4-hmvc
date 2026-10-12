<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;

/**
 * Generates skeleton module MVC files with standard FMS prefix.
 */
class FMSModuleGenerate extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Generators';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:module';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates new module MVC files with standard FMS prefix.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:module <name>';

    /**
     * The Command's Arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'name' => 'Nama modul yang akan dibuat (misal: Reports atau ProductCatalog)',
    ];

    /**
     * The Command's Options
     *
     * @var array<string, string>
     */
    protected $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params): int
    {
        helper('inflector');

        $rawName = array_shift($params);
        if (empty($rawName)) {
            $rawName = CLI::prompt('Masukkan nama modul', null, 'required');
        }

        $rawName = trim((string) $rawName);
        if ($rawName === '') {
            CLI::error('Nama modul wajib disertakan.');

            return EXIT_ERROR;
        }

        // Normalisasi nama modul: hilangkan prefix fms jika user sudah mengetikkannya agar tidak double
        $cleanName = preg_replace('/^fms[-_]?/i', '', $rawName);
        if ($cleanName === '') {
            $cleanName = $rawName;
        }

        $moduleName = pascalize($cleanName);
        $fmsPrefix = 'FMS' . $moduleName;

        $directoryMainFolder = 'Modules';
        $modulesPath = APPPATH . $directoryMainFolder;
        if (! is_dir($modulesPath)) {
            if (! mkdir($modulesPath, 0755, true)) {
                CLI::error('Gagal membuat direktori Modules di ' . APPPATH);

                return EXIT_ERROR;
            }
            CLI::write('Direktori Modules berhasil dibuat', 'green');
        }

        $directory = "$directoryMainFolder/$moduleName";
        $moduleFullPath = APPPATH . $directory;
        if (! is_dir($moduleFullPath)) {
            mkdir($moduleFullPath, 0755, true);
            CLI::write('Direktori modul dibuat: ' . $moduleFullPath, 'green');
        }

        $this->generateDirectories($directory);
        $this->generateFiles($directory, $moduleName, $fmsPrefix);

        CLI::write(sprintf('Modul "%s" berhasil dibuat dengan prefix seragam (%s).', $moduleName, $fmsPrefix), 'green');
        CLI::write(sprintf('  - Controller : App\Modules\%s\Controllers\%sController.php', $moduleName, $fmsPrefix), 'yellow');
        CLI::write(sprintf('  - Model      : App\Modules\%s\Models\%sModel.php', $moduleName, $fmsPrefix), 'yellow');
        CLI::write(sprintf('  - Routes     : App/Modules/%s/Config/Routes.php', $moduleName), 'yellow');
        CLI::write(sprintf('  - View       : App/Modules/%s/Views/index.php', $moduleName), 'yellow');

        return EXIT_SUCCESS;
    }

    protected function generateDirectories(string $directory): void
    {
        $directories = [
            'Config',
            'Controllers',
            'Models',
            'Views',
        ];

        foreach ($directories as $dir) {
            $folderRequire = APPPATH . $directory . '/' . $dir;
            if (! is_dir($folderRequire)) {
                mkdir($folderRequire, 0755, true);
            }
        }
    }

    protected function generateFiles(string $directory, string $moduleName, string $fmsPrefix): void
    {
        helper('inflector');
        $namespace = str_replace('/', '\\', $directory);
        $routeUri = strtolower(dasherize(underscore($moduleName)));
        $tableName = 'fms_' . strtolower(underscore($moduleName));

        $controllerClass = $fmsPrefix . 'Controller';
        $modelClass = $fmsPrefix . 'Model';

        $routesTemplate = $this->getTemplate(
            'routes.tpl.php',
            [
                '{controllerNamespace}' => "App\\$namespace\\Controllers",
                '{controllerClass}'     => $controllerClass,
                '{routeUri}'            => $routeUri,
                '{directoryName}'       => $moduleName,
            ]
        );

        $controllerTemplate = $this->getTemplate(
            'controller.tpl.php',
            [
                '{namespace}'    => "App\\$namespace\\Controllers",
                '{useStatement}' => 'App\Core\FMSController',
                '{class}'        => $controllerClass,
                '{extends}'      => 'FMSController',
                '{directoryName}'=> $moduleName,
            ]
        );

        $modelTemplate = $this->getTemplate(
            'model.tpl.php',
            [
                '{namespace}'    => "App\\$namespace\\Models",
                '{useStatement}' => 'App\Core\FMSModel',
                '{class}'        => $modelClass,
                '{extends}'      => 'FMSModel',
                '{table}'        => $tableName,
                '{directoryName}'=> $moduleName,
            ]
        );

        $viewTemplate = $this->getTemplate(
            'view.tpl.php',
            [
                '{moduleName}' => $moduleName,
            ]
        );

        file_put_contents(APPPATH . $directory . '/Config/Routes.php', $routesTemplate);
        file_put_contents(APPPATH . $directory . '/Controllers/' . $controllerClass . '.php', $controllerTemplate);
        file_put_contents(APPPATH . $directory . '/Models/' . $modelClass . '.php', $modelTemplate);
        file_put_contents(APPPATH . $directory . '/Views/index.php', $viewTemplate);
    }

    protected function getTemplate(string $templateFile, array $placeholders): string
    {
        $templatePath = APPPATH . 'Commands/Views/' . $templateFile;

        if (! file_exists($templatePath)) {
            CLI::write('Template file not found: ' . $templateFile, 'red');

            return '';
        }

        $templateContent = (string) file_get_contents($templatePath);

        foreach ($placeholders as $placeholder => $value) {
            $templateContent = str_replace($placeholder, $value, $templateContent);
        }

        return str_replace('<@php', '<?php', $templateContent);
    }
}
