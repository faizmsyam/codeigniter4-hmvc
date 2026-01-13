<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\App\Controllers\Backend;

use App\Core\FMSBackendController;

class AppController extends FMSBackendController
{

  function index() 
  {
    return $this->fmsLayout('index');
  }
}
