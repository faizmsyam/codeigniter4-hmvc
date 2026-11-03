<?php

namespace App\Libraries;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use RuntimeException;

/**
 * Layanan TOTP (Google Authenticator & kompatibel RFC 6238).
 * Secret disimpan terenkripsi (CI4 Encryption, key dari writable/keys/encryption.key);
 * plaintext hanya hidup di memori request enroll.
 */
final class FMSTotpService
{
    private TwoFactorAuth $tfa;

    public function __construct(?string $issuer = null)
    {
        $this->tfa = new TwoFactorAuth(
            new \RobThree\Auth\Providers\Qr\QRServerProvider(),
            $issuer ?? $this->issuer(),
            6,
            30,
        );
    }

    public function generateSecret(): string
    {
        return $this->tfa->createSecret(160);
    }

    public function qrDataUri(string $accountName, string $secret): string
    {
        try {
            return $this->tfa->getQRCodeImageAsDataUri($accountName, $secret);
        } catch (TwoFactorAuthException $e) {
            throw new RuntimeException('Gagal membuat QR 2FA: ' . $e->getMessage(), 0, $e);
        }
    }

    /** Toleransi ±1 step (30 dtk) untuk skew jam HP. */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return false;
        }

        try {
            return $this->tfa->verifyCode($secret, $code, 1);
        } catch (TwoFactorAuthException) {
            return false;
        }
    }

    public function encryptSecret(string $plainSecret): string
    {
        $cipher = service('encrypter')->encrypt($plainSecret);
        if ($cipher === false || $cipher === '') {
            throw new RuntimeException('Gagal mengenkripsi secret 2FA.');
        }

        return base64_encode($cipher);
    }

    public function decryptSecret(string $stored): string
    {
        $cipher = base64_decode($stored, true);
        if ($cipher === false) {
            throw new RuntimeException('Format secret 2FA tersimpan tidak valid.');
        }

        return (string) service('encrypter')->decrypt($cipher);
    }

    /** 8 backup code sekali tampil, panggil sekali saat enroll. */
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        }

        return $codes;
    }

    private function issuer(): string
    {
        try {
            $row = db_connect()->table('c_settings')
                ->select('value')
                ->where('key', 'security.totp_issuer')
                ->get(1)->getRowArray();
            if (is_array($row) && trim((string) ($row['value'] ?? '')) !== '') {
                return trim((string) $row['value']);
            }
        } catch (\Throwable) {
        }

        return 'FMS';
    }
}
