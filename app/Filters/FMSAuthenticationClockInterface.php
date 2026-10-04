<?php

namespace App\Filters;

interface FMSAuthenticationClockInterface
{
    public function currentTimestamp(): int;
}
