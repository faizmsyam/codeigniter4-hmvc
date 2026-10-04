<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Privileges\Exceptions;

use RuntimeException;

/**
 * Thrown when an operation is refused because it would remove or disable the
 * final effective super administrator of the installation.
 */
final class FMSLastSuperAdminProtectionException extends RuntimeException
{
}
