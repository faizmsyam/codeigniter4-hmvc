<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class FMSId extends BaseConfig
{
    /**
     * Salt for encrypting auto-increment IDs.
     * MUST NOT CHANGE once data is in production.
     */
    public string $hashidsSalt = '';

    public function __construct()
    {
        parent::__construct();
        $this->hashidsSalt = (string) env('FMS_HASHIDS_SALT', '');
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
