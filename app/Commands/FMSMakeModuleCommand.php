<?php

namespace App\Commands;

/**
 * Alias command fms:make:module untuk FMSModuleGenerate.
 */
class FMSMakeModuleCommand extends FMSModuleGenerate
{
    protected $group = 'FMS';
    protected $name = 'fms:make:module';
    protected $description = 'Generates new module MVC files with standard FMS prefix.';
    protected $usage = 'fms:make:module <name>';
}
