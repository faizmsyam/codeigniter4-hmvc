<?php

namespace App\Modules\Authentication\Controllers\Api;

use App\Core\FMSApiController;
use App\Libraries\FMSAuditLogger;
use App\Modules\Authentication\Repositories\FMSDatabaseAuthenticationLifecycleRepository;
use App\Modules\Authentication\Services\FMSAuthenticationLifecycleService;
use App\Modules\Settings\Services\FMSEmailService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSEmailVerificationApiController extends FMSApiController
{
    public function resend(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost() ?: [];
        }

        $identity = trim((string) ($payload['identity'] ?? $payload['email'] ?? ''));
        if ($identity === '' || mb_strlen($identity) > 190) {
            return $this->respondValidationError(['identity' => ['Email atau username wajib diisi.']]);
        }

        try {
            $service = new FMSAuthenticationLifecycleService(new FMSDatabaseAuthenticationLifecycleRepository());
            $result = $service->resendVerification($identity, date('Y-m-d H:i:s'), $this->request->getIPAddress());

            if (isset($result['verification_token'], $result['user'])
                && is_array($result['verification_token'])
                && is_array($result['user'])) {
                $token = $result['verification_token'];
                $user = $result['user'];
                $verificationUrl = site_url('fms-auth/verify-email') . '?' . http_build_query([
                    'selector' => $token['selector'],
                    'validator' => $token['validator'],
                ]);
                (new FMSEmailService())->sendVerification(
                    (string) $user['email'],
                    (string) ($user['full_name'] ?: $user['username']),
                    $verificationUrl,
                    (string) $token['expires_at'],
                );
            }

            return $this->respondOk('Jika akun ditemukan dan belum terverifikasi, email verifikasi telah dikirim.');
        } catch (Throwable $exception) {
            log_message('error', 'Resend verification email failed: {message}', ['message' => $exception->getMessage()]);

            return $this->respondServerError('Email verifikasi belum dapat dikirim. Periksa konfigurasi email.');
        }
    }

    public function verify(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost() ?: [];
        }

        $selector = trim((string) ($payload['selector'] ?? ''));
        $validator = trim((string) ($payload['validator'] ?? ''));
        if ($selector === '' || $validator === '') {
            return $this->respondValidationError(['token' => ['Token verifikasi wajib diisi.']]);
        }

        try {
            $result = (new FMSAuthenticationLifecycleService(new FMSDatabaseAuthenticationLifecycleRepository()))
                ->verifyEmail($selector, $validator, date('Y-m-d H:i:s'));

            if (($result['status'] ?? '') !== FMSAuthenticationLifecycleService::STATUS_VERIFIED) {
                return $this->respondError(400, 'Link verifikasi tidak valid, kedaluwarsa, atau sudah digunakan.', null, 400);
            }

            FMSAuditLogger::record(
                event: 'auth.email.verified',
                module: 'auth',
                entityType: 'email_verification',
                entityId: $selector,
                description: 'Alamat email berhasil diverifikasi.',
                httpMethod: $this->request->getMethod(),
                statusCode: 200,
            );

            return $this->respondOk('Email berhasil diverifikasi. Anda sekarang dapat masuk.');
        } catch (Throwable $exception) {
            log_message('error', 'Verify email failed: {message}', ['message' => $exception->getMessage()]);

            return $this->respondServerError('Verifikasi email gagal diproses.');
        }
    }
}
