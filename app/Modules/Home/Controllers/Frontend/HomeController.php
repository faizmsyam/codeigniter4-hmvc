<?php 

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Home\Controllers\Frontend;

use App\Core\FMSFrontendController;

class HomeController extends FMSFrontendController
{

	function index()
	{
		return $this->fmsLayout('index');
	}
}