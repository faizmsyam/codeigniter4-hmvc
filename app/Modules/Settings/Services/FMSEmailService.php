<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\FMSEmailSettingModel;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class FMSEmailService
{
    public function __construct(private readonly FMSEmailSettingModel $model = new FMSEmailSettingModel())
    {
    }

    public function publicSettings(): array
    {
        $settings = $this->model->currentSettings();

        return [
            'id' => (int) ($settings['id'] ?? 0),
            'enabled' => (int) ($settings['enabled'] ?? 0),
            'protocol' => (string) ($settings['protocol'] ?? 'smtp'),
            'smtp_host' => (string) ($settings['smtp_host'] ?? ''),
            'smtp_port' => (int) ($settings['smtp_port'] ?? 587),
            'smtp_user' => (string) ($settings['smtp_user'] ?? ''),
            'smtp_password_configured' => ! empty($settings['smtp_password_encrypted']),
            'smtp_crypto' => (string) ($settings['smtp_crypto'] ?? 'tls'),
            'from_email' => (string) ($settings['from_email'] ?? ''),
            'from_name' => (string) ($settings['from_name'] ?? ''),
            'reply_to' => (string) ($settings['reply_to'] ?? ''),
            'timeout_seconds' => (int) ($settings['timeout_seconds'] ?? 10),
            'version' => (int) ($settings['version'] ?? 1),
            'updated_at' => $settings['updated_at'] ?? null,
        ];
    }

    public function updateSettings(array $payload, int $actorIdentifier): array
    {
        $current = $this->model->currentSettings();
        $protocol = strtolower(trim((string) ($payload['protocol'] ?? 'smtp')));
        $host = trim((string) ($payload['smtp_host'] ?? ''));
        $user = trim((string) ($payload['smtp_user'] ?? ''));
        $fromEmail = trim((string) ($payload['from_email'] ?? ''));
        $fromName = trim((string) ($payload['from_name'] ?? ''));
        $replyTo = trim((string) ($payload['reply_to'] ?? ''));
        $crypto = strtolower(trim((string) ($payload['smtp_crypto'] ?? 'tls')));
        $port = (int) ($payload['smtp_port'] ?? 587);
        $timeout = (int) ($payload['timeout_seconds'] ?? 10);
        $enabled = filter_var($payload['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        if (! in_array($protocol, ['smtp', 'mail', 'sendmail'], true)) {
            throw new InvalidArgumentException('Protocol email tidak valid.');
        }
        if (! in_array($crypto, ['', 'tls', 'ssl'], true)) {
            throw new InvalidArgumentException('Enkripsi SMTP tidak valid.');
        }

        // CodeIgniter 4 menangani port 465 sebagai implicit TLS. Membiarkan
        // SMTPCrypto=tls pada port ini akan mencoba STARTTLS untuk kedua kali.
        if ($protocol === 'smtp' && $port === 465 && in_array($crypto, ['tls', 'ssl'], true)) {
            $crypto = '';
        }

        if ($port < 1 || $port > 65535 || $timeout < 1 || $timeout > 120) {
            throw new InvalidArgumentException('Port atau timeout SMTP tidak valid.');
        }
        if ($fromEmail !== '' && filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email pengirim tidak valid.');
        }
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Reply-To tidak valid.');
        }
        if ($enabled && ($fromEmail === '' || ($protocol === 'smtp' && $host === ''))) {
            throw new InvalidArgumentException('Host SMTP dan email pengirim wajib diisi ketika pengiriman email diaktifkan.');
        }

        $data = [
            'enabled' => $enabled,
            'protocol' => $protocol,
            'smtp_host' => $host,
            'smtp_port' => $port,
            'smtp_user' => $user,
            'smtp_crypto' => $crypto,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'reply_to' => $replyTo !== '' ? $replyTo : null,
            'timeout_seconds' => $timeout,
            'version' => ((int) ($current['version'] ?? 1)) + 1,
            'updated_by' => $actorIdentifier > 0 ? $actorIdentifier : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $password = (string) ($payload['smtp_password'] ?? '');
        if ($password !== '') {
            $data['smtp_password_encrypted'] = $this->encryptSecret($password);
        }

        $this->model->update((int) $current['id'], $data);

        return $this->publicSettings();
    }

    public function send(string $recipientEmail, string $recipientName, string $subject, string $htmlMessage): bool
    {
        if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Alamat email tujuan tidak valid.');
        }

        $settings = $this->model->currentSettings();
        if (empty($settings['enabled'])) {
            throw new RuntimeException('Layanan pengiriman email belum diaktifkan.');
        }

        $smtpPort = (int) ($settings['smtp_port'] ?? 587);
        $smtpCrypto = (string) ($settings['smtp_crypto'] ?? 'tls');
        if ($smtpPort === 465 && in_array($smtpCrypto, ['tls', 'ssl'], true)) {
            $smtpCrypto = '';
        }

        $config = [
            'protocol' => (string) ($settings['protocol'] ?? 'smtp'),
            'SMTPHost' => (string) ($settings['smtp_host'] ?? ''),
            'SMTPPort' => $smtpPort,
            'SMTPUser' => (string) ($settings['smtp_user'] ?? ''),
            'SMTPPass' => $this->decryptSecret((string) ($settings['smtp_password_encrypted'] ?? '')),
            'SMTPCrypto' => $smtpCrypto,
            'SMTPTimeout' => (int) ($settings['timeout_seconds'] ?? 10),
            'mailType' => 'html',
            'charset' => 'UTF-8',
            'CRLF' => "\r\n",
            'newline' => "\r\n",
        ];

        $mailer = service('email', $config, false);
        $mailer->clear(true);
        $mailer->setFrom((string) $settings['from_email'], (string) ($settings['from_name'] ?? ''));
        $mailer->setTo($recipientEmail, $recipientName);
        if (! empty($settings['reply_to'])) {
            $mailer->setReplyTo((string) $settings['reply_to']);
        }
        $mailer->setSubject($subject);
        $mailer->setMessage($htmlMessage);

        if (! $mailer->send()) {
            log_message('error', 'Email send failed: {debug}', ['debug' => $mailer->printDebugger(['headers'])]);
            throw new RuntimeException('Server email menolak pengiriman pesan.');
        }

        return true;
    }

    public function sendVerification(string $recipientEmail, string $recipientName, string $verificationUrl, string $expiresAt): bool
    {
        $brandName = (string) (config(\Config\App::class)->appName ?: 'FMS');
        $message = view('App\\Modules\\Authentication\\Views\\emails\\verify-email', [
            'recipientName' => $recipientName,
            'verificationUrl' => $verificationUrl,
            'expiresAt' => $expiresAt,
            'brandName' => $brandName,
        ]);

        return $this->send($recipientEmail, $recipientName, 'Verifikasi Email - ' . $brandName, $message);
    }

    public function testConnection(string $recipientEmail): bool
    {
        return $this->send(
            $recipientEmail,
            'Administrator',
            'Tes Konfigurasi Email',
            '<p>Konfigurasi email berhasil digunakan oleh aplikasi.</p>',
        );
    }

    private function encryptSecret(string $plainText): string
    {
        try {
            return base64_encode(service('encrypter')->encrypt($plainText));
        } catch (Throwable $exception) {
            throw new RuntimeException('Encryption key aplikasi belum valid untuk menyimpan password SMTP.', 0, $exception);
        }
    }

    private function decryptSecret(string $cipherText): string
    {
        if ($cipherText === '') {
            return '';
        }

        try {
            $decoded = base64_decode($cipherText, true);
            if ($decoded === false) {
                throw new RuntimeException('Format password SMTP terenkripsi rusak.');
            }

            return service('encrypter')->decrypt($decoded);
        } catch (Throwable $exception) {
            throw new RuntimeException('Password SMTP tidak dapat didekripsi.', 0, $exception);
        }
    }
}
