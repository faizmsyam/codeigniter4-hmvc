<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\UserGroups\Validation;

use InvalidArgumentException;

final class FMSUserGroupValidation
{
    public static function validateName(string $name, ?int $ignoreId = null): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Nama grup wajib diisi.');
        }

        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            throw new InvalidArgumentException('Nama grup harus antara 3 sampai 100 karakter.');
        }

        if (preg_match('/^[a-zA-Z0-9\s\-_\.]+$/u', $name) !== 1) {
            throw new InvalidArgumentException('Nama grup hanya boleh mengandung huruf, angka, spasi, titik, hyphen, atau underscore.');
        }

        return $name;
    }

    public static function validateStatus(int|string|bool $status): int
    {
        if (is_bool($status)) {
            return $status ? 1 : 0;
        }

        $s = (int) $status;
        return ($s === 1 || $s === 0) ? $s : 1;
    }
}
