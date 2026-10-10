<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Controllers\Backend;

use App\Core\FMSBackendController;
use App\Libraries\FMSAuditLogger;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

final class FMSSettingsBackendController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireSuperAdministrator();

        return $this->fmsLayout('index');
    }

    private function requireSuperAdministrator(): void
    {
        $userId = (int) $this->session->get('fms_backend_user_id');
        $permissions = $this->refreshActiveGroupPermissions($userId);
        $isSuperAdmin = in_array('*', $permissions, true);

        if (! $isSuperAdmin) {
            try {
                FMSAuditLogger::record(
                    event: 'access.denied',
                    module: 'settings',
                    actorId: $userId > 0 ? $userId : null,
                    entityType: 'module',
                    entityId: 'settings',
                    description: 'Akses halaman backend konfigurasi ditolak (403): Membutuhkan hak akses Super Administrator.',
                    httpMethod: $this->request->getMethod(),
                    statusCode: 403,
                );
            } catch (Throwable $auditException) {
                log_message('error', 'Audit log failed on access.denied: {msg}', ['msg' => $auditException->getMessage()]);
            }

            throw PageNotFoundException::forPageNotFound();
        }
    }
}
