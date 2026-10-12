<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class FMSId extends BaseConfig
{
    /**
     * Salt for encrypting auto-increment IDs.
     * MUST NOT CHANGE once data is in production.
     */
    public string $hashidsSalt = 'FMS-2026-fmsprojects.my.id-ID-Obfuscation-9c7b4e2a';

    public function __construct()
    {
        parent::__construct();
        $envSalt = (string) env('FMS_HASHIDS_SALT', '');
        if ($envSalt !== '') {
            $this->hashidsSalt = $envSalt;
        }
        $this->hashidsMinLength = (int) env('FMS_HASHIDS_MIN_LENGTH', 12);
    }

    /**
     * Minimum length of the obfuscated hash.
     */
    public int $hashidsMinLength = 12;

    /**
     * Custom alphabet for Hashids.
     */
    public string $hashidsAlphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
}
