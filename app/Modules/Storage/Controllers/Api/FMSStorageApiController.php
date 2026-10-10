<?php
namespace App\Modules\Storage\Controllers\Api;

use App\Core\FMSController;
use App\Modules\Storage\Models\FMSStorageUserModel;
use App\Modules\Storage\Services\FMSStorageAuthorizationService;
use App\Modules\Storage\Services\FMSStorageObjectService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSStorageApiController extends FMSController
{
    private function user(): ?array
    {
        $id = (int)session()->get('fms_storage_user_id');
        if ($id < 1) return null;
        return (new FMSStorageUserModel())->find($id) ?: null;
    }
    private function allowed(string $permission): bool
    {
        return (new FMSStorageAuthorizationService())->allows($this->user(), $permission);
    }
    private function deny(): ResponseInterface { return $this->json(['status'=>false,'message'=>'Akses ditolak.'],403); }

    public function login(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $user = (new FMSStorageUserModel())->findByIdentifier(trim((string)($data['identifier'] ?? '')));
        if (!is_array($user) || !password_verify((string)($data['password'] ?? ''),(string)($user['password_hash'] ?? ''))) return $this->json(['status'=>false,'message'=>'Username atau password salah.'],401);
        session()->regenerate(true);
        session()->set(['fms_storage_authenticated'=>true,'fms_storage_user_id'=>(int)$user['id'],'fms_storage_username'=>$user['username'],'fms_storage_full_name'=>$user['full_name'],'fms_storage_super_admin'=>(int)$user['is_super_admin']===1,'fms_storage_logged_in_at'=>time()]);
        return $this->json(['status'=>true,'data'=>['username'=>$user['username'],'is_super_admin'=>(int)$user['is_super_admin']===1]]);
    }
    public function logout(): ResponseInterface
    {
        foreach (array_keys(session()->get()) as $key) if (str_starts_with((string)$key,'fms_storage_')) session()->remove($key);
        session()->regenerate(true);
        return $this->json(['status'=>true]);
    }
    public function objects(): ResponseInterface
    {
        if (!$this->allowed(FMSStorageAuthorizationService::READ)) return $this->deny();
        try { return $this->json(['status'=>true,'data'=>['objects'=>(new FMSStorageObjectService())->list((string)$this->request->getGet('prefix'))]]); }
        catch (Throwable $e) { return $this->json(['status'=>false,'message'=>$e->getMessage()],422); }
    }
    public function upload(): ResponseInterface
    {
        if (!$this->allowed(FMSStorageAuthorizationService::UPLOAD)) return $this->deny();
        $file = $this->request->getFile('file');
        if (!$file) return $this->json(['status'=>false,'message'=>'File wajib diisi.'],422);
        try { $result=(new FMSStorageObjectService())->upload($file,(string)$this->request->getPost('prefix')); return $this->json(['status'=>true,'data'=>$result],201); }
        catch (Throwable $e) { return $this->json(['status'=>false,'message'=>$e->getMessage()],422); }
    }
    public function delete(): ResponseInterface
    {
        if (!$this->allowed(FMSStorageAuthorizationService::DELETE)) return $this->deny();
        try { $ok=(new FMSStorageObjectService())->delete((string)($this->request->getJSON(true)['key'] ?? $this->request->getPost('key'))); return $this->json(['status'=>$ok]); }
        catch (Throwable $e) { return $this->json(['status'=>false,'message'=>$e->getMessage()],422); }
    }
    public function presign(): ResponseInterface
    {
        if (!$this->allowed(FMSStorageAuthorizationService::SHARE)) return $this->deny();
        $data=$this->request->getJSON(true) ?: $this->request->getPost();
        try { $url=(new FMSStorageObjectService())->presigned((string)($data['key']??''),(int)($data['ttl']??3600)); return $this->json(['status'=>true,'data'=>['url'=>$url]]); }
        catch (Throwable $e) { return $this->json(['status'=>false,'message'=>$e->getMessage()],422); }
    }
    public function createShare(): ResponseInterface
    {
        if (!$this->allowed(FMSStorageAuthorizationService::SHARE)) return $this->deny();
        $data=$this->request->getJSON(true) ?: $this->request->getPost(); $key=trim((string)($data['key']??'')); $ttl=max(60,min(604800,(int)($data['ttl']??3600)));
        try {
            (new FMSStorageObjectService())->readLocal($key); $token=bin2hex(random_bytes(32));
            db_connect()->table('storage_shares')->insert(['token_hash'=>hash('sha256',$token),'object_key'=>$key,'created_by'=>(int)session()->get('fms_storage_user_id'),'expires_at'=>date('Y-m-d H:i:s',time()+$ttl),'created_at'=>date('Y-m-d H:i:s')]);
            return $this->json(['status'=>true,'data'=>['url'=>site_url('fms-storage/share/'.$token),'expires_at'=>date(DATE_ATOM,time()+$ttl)]],201);
        } catch (Throwable $e) { return $this->json(['status'=>false,'message'=>$e->getMessage()],422); }
    }
    public function profile(): ResponseInterface
    {
        $u = $this->user();
        if (! $u) return $this->json(['status' => false], 401);
        unset($u['password_hash']);
        $storageConfig = config(\App\Modules\Storage\Config\FMSStorage::class);
        $uploadsConfig = config(\App\Config\FMSUploads::class);
        return $this->json(['status' => true, 'data' => [
            'user' => $u,
            'allowed_mime_types' => $storageConfig->allowedMimeTypes(),
            'maximum_upload_bytes' => $uploadsConfig->maximumInputBytes(),
        ]]);
    }
    public function changePassword(): ResponseInterface
    {
        $u=$this->user(); if(!$u)return $this->json(['status'=>false],401); $data=$this->request->getJSON(true) ?: $this->request->getPost();
        if (!password_verify((string)($data['current_password']??''),(string)$u['password_hash'])) return $this->json(['status'=>false,'message'=>'Password saat ini salah.'],422);
        $password=(string)($data['new_password']??''); if(strlen($password)<12)return $this->json(['status'=>false,'message'=>'Password minimal 12 karakter.'],422);
        (new FMSStorageUserModel())->update((int)$u['id'],['password_hash'=>password_hash($password,PASSWORD_DEFAULT),'password_changed_at'=>date('Y-m-d H:i:s')]); return $this->json(['status'=>true]);
    }
}
