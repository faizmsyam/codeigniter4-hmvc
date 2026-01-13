<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Authentication\Controllers;

use App\Core\FMSAuthController;

class AuthenticationController extends FMSAuthController
{

  function login()
  {
    $paramMeta = [
      'title' => 'Login',
    ];
    $this->fmsMeta($paramMeta);

    return $this->fmsLayout('login');
  }
}