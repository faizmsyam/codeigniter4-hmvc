<?php

namespace App\Libraries;

use Root23\JsonCanonicalizer\JsonCanonicalizer;

final class FMSCanonicalJson
{
    public function __construct(private readonly JsonCanonicalizer $jsonCanonicalizer = new JsonCanonicalizer())
    {
    }

    public function encode(mixed $value): string
    {
        return $this->jsonCanonicalizer->canonicalize($value);
    }
}
