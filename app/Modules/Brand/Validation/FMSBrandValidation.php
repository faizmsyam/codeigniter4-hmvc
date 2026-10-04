<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Brand\Validation;

final class FMSBrandValidation
{
    private const BRAND_UUID_PATTERN = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i';

    private const STORED_IMAGE_PATTERN = '#\Afms-brand/fms-[A-Za-z0-9]{16,64}\.webp\z#';

    /**
     * @param array<string, mixed> $brandData
     * @return array<string, array<int, string>>
     */
    public static function validateCreate(array $brandData): array
    {
        return self::validatePayload($brandData, true);
    }

    /**
     * @param array<string, mixed> $brandData
     * @return array<string, array<int, string>>
     */
    public static function validateUpdate(array $brandData): array
    {
        return self::validatePayload($brandData, false);
    }

    public static function isValidUuid(string $brandUuid): bool
    {
        return preg_match(self::BRAND_UUID_PATTERN, $brandUuid) === 1;
    }

    public static function isValidStoredImagePath(?string $storedImagePath): bool
    {
        if ($storedImagePath === null || $storedImagePath === '') {
            return true;
        }

        if (str_contains($storedImagePath, '\\') || str_contains($storedImagePath, '..') || str_contains($storedImagePath, "\0")) {
            return false;
        }

        if (substr_count($storedImagePath, '/') !== 1) {
            return false;
        }

        return preg_match(self::STORED_IMAGE_PATTERN, $storedImagePath) === 1;
    }

    /**
     * @param array<string, mixed> $brandData
     * @return array<string, array<int, string>>
     */
    private static function validatePayload(array $brandData, bool $nameIsRequired): array
    {
        $validationErrors = [];
        $brandName = trim((string) ($brandData['name'] ?? ''));

        if ($nameIsRequired && $brandName === '') {
            $validationErrors['name'] = ['Nama brand wajib diisi.'];
        } elseif (array_key_exists('name', $brandData) && ($brandName === '' || mb_strlen($brandName) > 100)) {
            $validationErrors['name'] = ['Nama brand wajib 1-100 karakter.'];
        }

        if (array_key_exists('tagline', $brandData) && mb_strlen(trim((string) $brandData['tagline'])) > 160) {
            $validationErrors['tagline'] = ['Tagline brand maksimal 160 karakter.'];
        }

        if (array_key_exists('description', $brandData) && mb_strlen(trim((string) $brandData['description'])) > 5000) {
            $validationErrors['description'] = ['Deskripsi brand maksimal 5000 karakter.'];
        }

        if (array_key_exists('email', $brandData)) {
            $brandEmail = trim((string) $brandData['email']);
            if ($brandEmail !== '' && (mb_strlen($brandEmail) > 190 || filter_var($brandEmail, FILTER_VALIDATE_EMAIL) === false)) {
                $validationErrors['email'] = ['Email brand tidak valid.'];
            }
        }

        if (array_key_exists('phone', $brandData)) {
            $brandPhone = trim((string) $brandData['phone']);
            if ($brandPhone !== '' && (mb_strlen($brandPhone) > 40 || preg_match('/\A[0-9+().\s-]+\z/', $brandPhone) !== 1)) {
                $validationErrors['phone'] = ['Nomor telepon brand tidak valid.'];
            }
        }

        if (array_key_exists('whatsapp', $brandData)) {
            $brandWa = trim((string) $brandData['whatsapp']);
            if ($brandWa !== '' && (mb_strlen($brandWa) > 40 || preg_match('/\A[0-9+().\s-]+\z/', $brandWa) !== 1)) {
                $validationErrors['whatsapp'] = ['Nomor WhatsApp brand tidak valid.'];
            }
        }

        if (array_key_exists('website_url', $brandData)) {
            $siteUrl = trim((string) $brandData['website_url']);
            if ($siteUrl !== '' && (mb_strlen($siteUrl) > 255 || filter_var($siteUrl, FILTER_VALIDATE_URL) === false)) {
                $validationErrors['website_url'] = ['URL website brand tidak valid (harus menyertakan http:// atau https://).'];
            }
        }

        if (array_key_exists('address', $brandData) && mb_strlen(trim((string) $brandData['address'])) > 1000) {
            $validationErrors['address'] = ['Alamat brand maksimal 1000 karakter.'];
        }

        if (array_key_exists('is_active', $brandData) && ! in_array($brandData['is_active'], [0, 1, '0', '1', true, false], true)) {
            $validationErrors['is_active'] = ['Status aktif harus bernilai 0 atau 1.'];
        }

        if (array_key_exists('uuid', $brandData) && ! self::isValidUuid((string) $brandData['uuid'])) {
            $validationErrors['uuid'] = ['UUID brand tidak valid.'];
        }

        foreach (['logo_path', 'logo_light_path', 'favicon_path'] as $imagePathFieldName) {
            if (array_key_exists($imagePathFieldName, $brandData) && ! self::isValidStoredImagePath(self::nullableString($brandData[$imagePathFieldName]))) {
                $validationErrors[$imagePathFieldName] = ['Nama file gambar brand tidak valid.'];
            }
        }

        return $validationErrors;
    }

    private static function nullableString(mixed $fieldValue): ?string
    {
        if ($fieldValue === null) {
            return null;
        }

        return trim((string) $fieldValue);
    }
}
