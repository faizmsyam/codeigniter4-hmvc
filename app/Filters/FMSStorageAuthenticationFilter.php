<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class FMSStorageAuthenticationFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if ($session->get('fms_storage_authenticated') === true && (int)$session->get('fms_storage_user_id') > 0) return null;
        if (str_starts_with(trim($request->getUri()->getPath(), '/'), 'api/')) {
            return service('response')->setStatusCode(401)->setJSON(['status'=>false,'message'=>'Storage authentication required.']);
        }
        $session->set('fms_storage_intended_url', current_url());
        return redirect()->to(site_url('fms-storage/login'));
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) { return null; }
}
