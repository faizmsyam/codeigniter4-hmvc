<?php

namespace App\Filters;

final class FMSSystemClock implements FMSAuthenticationClockInterface
{
    public function currentTimestamp(): int
    {
        return time();
    }
}
