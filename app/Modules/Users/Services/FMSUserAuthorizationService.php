<?php

namespace App\Modules\Users\Services;

use App\Libraries\FMSPermissionAuthorizationService;

/**
 * Users-domain authorization facade.
 *
 * The permission logic itself is shared, not duplicated: this class only fixes
 * the namespace-aware rules to the Users domain so callers keep reading as
 * `users.*`. Renaming or extending the shared rules changes every domain at
 * once, which is the point of keeping one implementation.
 */
final class FMSUserAuthorizationService extends FMSPermissionAuthorizationService
{
}
