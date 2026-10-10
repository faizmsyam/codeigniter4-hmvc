<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Authentication\Controllers;

use App\Core\FMSAuthController;
use App\Modules\Authentication\Repositories\FMSDatabaseAuthenticationLifecycleRepository;
use App\Modules\Authentication\Services\FMSAuthenticationLifecycleService;

class FMSAuthenticationController extends FMSAuthController
{
    public function __construct()
    {
        parent::__construct();
        $this->location = 'auth';
    }

    public function login()
    {
        if (session()->get('fms_backend_authenticated') === true) {
            if (session()->get('fms_backend_must_change_password') === true) {
                return redirect()->to(site_url(ROUTE_ADMIN . '/change-password'));
            }

            return redirect()->to(site_url(ROUTE_ADMIN . '/dashboard'));
        }

        $this->fmsMeta(['title' => 'Login']);

        return $this->fmsLayout('login');
    }

    public function verifyEmail()
    {
        $selector = trim((string) $this->request->getGet('selector'));
        $validator = trim((string) $this->request->getGet('validator'));
        $status = FMSAuthenticationLifecycleService::STATUS_INVALID_TOKEN;

        if ($selector !== '' && $validator !== '') {
            try {
                $status = (new FMSAuthenticationLifecycleService(new FMSDatabaseAuthenticationLifecycleRepository()))
                    ->verifyEmail($selector, $validator, date('Y-m-d H:i:s'))['status'] ?? $status;
            } catch (\Throwable $exception) {
                log_message('error', 'Email verification page failed: {message}', ['message' => $exception->getMessage()]);
            }
        }

        $this->fmsMeta(['title' => 'Verifikasi Email']);

        return $this->fmsLayout('verify-email', ['verificationStatus' => $status]);
    }
    public function changePassword()
    {
        if (session()->get('fms_backend_authenticated') !== true) {
            return redirect()->to(site_url('fms-auth/in'));
        }

        if (session()->get('fms_backend_must_change_password') !== true) {
            return redirect()->to(site_url(ROUTE_ADMIN . '/dashboard'));
        }

        $this->fmsMeta(['title' => 'Ganti Password']);

        return $this->fmsLayout('change-password', [
            'changePasswordUrl' => site_url('api/v1/profile/force-change-password'),
            'dashboardUrl' => site_url(ROUTE_ADMIN . '/dashboard'),
        ]);
    }

    /**
     * Logout — clears the backend session and redirects to login.
     */
    public function logout()
    {
        $session = session();
        $session->remove('fms_backend_authenticated');
        $session->remove('fms_backend_user_id');
        $session->remove('fms_backend_user_email');
        $session->remove('fms_backend_user_name');
        $session->remove('fms_backend_token_family_id');
        $session->remove('fms_backend_must_change_password');
        $session->remove('fms_backend_intended_url');
        $session->destroy();

        return redirect()->to(site_url('fms-auth/in'))->with('logout_success', 'Anda telah keluar dari sistem.');
    }
}
