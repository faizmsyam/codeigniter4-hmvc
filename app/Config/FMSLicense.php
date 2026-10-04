<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

final class FMSLicense extends BaseConfig
{
    /** Identitas pemilik lisensi. Hanya dipakai internal dan tidak pernah diproyeksikan ke response/JWT/log. */
    public const LICENSEE_NAME = 'Faiz Muhammad Syam, S.Kom., M.TI., CH';

    public string $licenseManifestPath = ROOTPATH . 'licenses/fms-license.json';
    public string $licenseSignaturePath = ROOTPATH . 'licenses/fms-license.sig';
    public string $licensePublicKeyPath = ROOTPATH . 'licenses/keys/fms-license-public.key';

    public string $integrityManifestPath = ROOTPATH . 'release/fms-integrity-manifest.json';
    public string $integritySignaturePath = ROOTPATH . 'release/fms-integrity-manifest.sig';
    public string $integrityPublicKeyPath = ROOTPATH . 'licenses/keys/fms-release-public.key';

    public bool $enforceInProduction = true;

    /** Direktori yang hash-nya dipantau pada release manifest. */
    public array $monitoredPaths = [
        'app/Config',
        'app/Core',
        'app/Filters',
        'app/Libraries',
        'app/Services',
        'app/Models',
        'app/Commands',
        'app/Helpers',
    ];

    /** Path runtime yang boleh berubah dan dikecualikan dari pemeriksaan integritas. */
    public array $excludedPaths = [
        'writable',
        'vendor',
        'templates-admin',
        'licenses',
        'release',
        'node_modules',
        '.git',
        'build',
    ];
}
