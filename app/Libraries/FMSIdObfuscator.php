<?php

namespace App\Libraries;

use App\Config\FMSId;
use Hashids\Hashids;
use InvalidArgumentException;

/**
 * Service to obfuscate database auto-increment IDs into secure hashes and back.
 */
final class FMSIdObfuscator
{
    private readonly Hashids $hashids;

    public function __construct()
    {
        $config = config(FMSId::class);
        if (strlen($config->hashidsSalt) < 32) {
            throw new InvalidArgumentException('FMS_HASHIDS_SALT must contain at least 32 characters.');
        }

        $this->hashids = new Hashids(
            $config->hashidsSalt,
            $config->hashidsMinLength,
            $config->hashidsAlphabet
        );
    }

    /**
     * Obfuscate a database ID into a hash string.
     */
    public function encode(int $id): string
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer.');
        }

        return $this->hashids->encode($id);
    }

    /**
     * Decode a hash string back into a database ID.
     * Returns null if the hash is invalid or tampered with.
     */
    public function decode(string $hash): ?int
    {
        if (trim($hash) === '') {
            return null;
        }

        $decoded = $this->hashids->decode($hash);
        
        return isset($decoded[0]) ? (int) $decoded[0] : null;
    }

    /**
     * Decode a hash string, throwing an exception if invalid.
     */
    public function decodeOrFail(string $hash): int
    {
        $id = $this->decode($hash);
        if ($id === null) {
            throw new InvalidArgumentException('Invalid or malformed ID hash.');
        }

        return $id;
    }
}
