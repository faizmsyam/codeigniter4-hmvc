<?php

namespace Tests\Unit\Modules\Authentication;

use App\Modules\Authentication\Services\FMSAuthenticationFailureMessage;
use App\Modules\Authentication\Services\FMSLoginAttemptService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSAuthenticationFailureMessageTest extends CIUnitTestCase
{
    public function testEveryAuthenticationFailureStatusHasSpecificInformation(): void
    {
        $this->assertSame(
            'Username/email atau password tidak valid.',
            FMSAuthenticationFailureMessage::forStatus(FMSLoginAttemptService::STATUS_INVALID_CREDENTIALS),
        );
        $this->assertSame(
            'Akun ini sedang tidak aktif.',
            FMSAuthenticationFailureMessage::forStatus(FMSLoginAttemptService::STATUS_ACCOUNT_DISABLED, 'inactive'),
        );
        $this->assertSame(
            'Akun ini telah diblokir.',
            FMSAuthenticationFailureMessage::forStatus(FMSLoginAttemptService::STATUS_ACCOUNT_DISABLED, 'banned'),
        );
        $this->assertSame(
            'Akun terkunci sampai 02 Oktober 2026 11:30 WIB.',
            FMSAuthenticationFailureMessage::forStatus(FMSLoginAttemptService::STATUS_ACCOUNT_LOCKED, '2026-10-02 11:30:00'),
        );
        $this->assertSame(
            'Email akun belum diverifikasi.',
            FMSAuthenticationFailureMessage::forStatus(FMSLoginAttemptService::STATUS_EMAIL_UNVERIFIED),
        );
        $this->assertSame(
            'Terlalu banyak percobaan login. Coba lagi setelah 15 menit.',
            FMSAuthenticationFailureMessage::forStatus(FMSLoginAttemptService::STATUS_RATE_LIMITED),
        );
    }
}
