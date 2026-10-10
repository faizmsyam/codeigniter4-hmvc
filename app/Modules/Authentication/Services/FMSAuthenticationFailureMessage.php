<?php

namespace App\Modules\Authentication\Services;

use DateTimeImmutable;
use DateTimeZone;

final class FMSAuthenticationFailureMessage
{
    public static function forStatus(string $status, ?string $detail = null): string
    {
        return match ($status) {
            FMSLoginAttemptService::STATUS_INVALID_CREDENTIALS => 'Username/email atau password tidak valid.',
            FMSLoginAttemptService::STATUS_ACCOUNT_DISABLED => self::disabledAccountMessage($detail),
            FMSLoginAttemptService::STATUS_ACCOUNT_LOCKED => self::lockedAccountMessage($detail),
            FMSLoginAttemptService::STATUS_EMAIL_UNVERIFIED => 'Email akun belum diverifikasi.',
            FMSLoginAttemptService::STATUS_RATE_LIMITED => 'Terlalu banyak percobaan login. Coba lagi setelah 15 menit.',
            default => 'Autentikasi gagal.',
        };
    }

    private static function disabledAccountMessage(?string $accountStatus): string
    {
        return match (strtolower(trim((string) $accountStatus))) {
            'banned' => 'Akun ini telah diblokir.',
            'inactive' => 'Akun ini sedang tidak aktif.',
            'pending' => 'Akun ini masih menunggu aktivasi.',
            default => 'Akun ini tidak dapat digunakan.',
        };
    }

    private static function lockedAccountMessage(?string $lockedUntil): string
    {
        $lockedUntil = trim((string) $lockedUntil);
        if ($lockedUntil === '') {
            return 'Akun terkunci sementara karena terlalu banyak percobaan login.';
        }

        $timezoneName = function_exists('app_timezone')
            ? app_timezone()
            : (string) (config('App')->appTimezone ?? 'Asia/Jakarta');
        $timezone = new DateTimeZone($timezoneName);
        $timestamp = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $lockedUntil,
            $timezone,
        );
        if ($timestamp === false) {
            return 'Akun terkunci sampai ' . $lockedUntil . '.';
        }

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $zoneLabel = $timestamp->format('T');
        return sprintf(
            'Akun terkunci sampai %s %s %s %s %s.',
            $timestamp->format('d'),
            $monthNames[(int) $timestamp->format('n')],
            $timestamp->format('Y'),
            $timestamp->format('H:i'),
            $zoneLabel,
        );
    }
}
