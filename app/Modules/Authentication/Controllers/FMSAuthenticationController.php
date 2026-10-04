<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Authentication\Controllers;

use App\Core\FMSAuthController;

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
            return redirect()->to(site_url(ROUTE_ADMIN . '/dashboard'));
        }

        $this->fmsMeta(['title' => 'Login']);

        return $this->fmsLayout('login');
    }
}
