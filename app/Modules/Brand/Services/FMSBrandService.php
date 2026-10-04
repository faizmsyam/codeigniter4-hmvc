<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Brand\Services;

use App\Modules\Brand\Validation\FMSBrandValidation;
use InvalidArgumentException;

final class FMSBrandService
{
    private const TEXT_FIELDS = [
        'name'        => 100,
        'tagline'     => 160,
        'description' => 5000,
        'address'     => 1000,
        'phone'       => 40,
        'whatsapp'    => 40,
        'website_url' => 255,
    ];

    private const IMAGE_PATH_FIELDS = ['logo_path', 'logo_light_path', 'favicon_path'];

    /**
     * @param array<string, mixed> $requestData
     * @return array<string, mixed>
     */
    public function prepareCreateData(array $requestData, int $actorUserIdentifier): array
    {
        $this->requirePositiveActor($actorUserIdentifier);
        $validatedLabel = trim((string) ($requestData['name'] ?? ''));

        if ($validatedLabel === '') {
            throw new InvalidArgumentException('Brand name is required.');
        }

        $requestedBrandUuid = trim((string) ($requestData['uuid'] ?? ''));
        $uuid = ($requestedBrandUuid !== '' && FMSBrandValidation::isValidUuid($requestedBrandUuid))
            ? $requestedBrandUuid
            : $this->newUuid();

        $preparedBrandData = [
            'uuid'       => $uuid,
            'name'       => mb_substr($validatedLabel, 0, 100, 'UTF-8'),
            'is_active'  => $this->normalizeActiveFlag($requestData['is_active'] ?? 1),
            'created_by' => $actorUserIdentifier,
            'updated_by' => $actorUserIdentifier,
        ];

        foreach (self::TEXT_FIELDS as $field => $maxLen) {
            if ($field === 'name') {
                continue;
            }
            if (array_key_exists($field, $requestData)) {
                $preparedBrandData[$field] = $this->nullableText($requestData[$field], $maxLen);
            }
        }

        if (array_key_exists('email', $requestData)) {
            $brandEmail = $this->nullableText($requestData['email'], 190);
            $preparedBrandData['email'] = $brandEmail;
            $preparedBrandData['email_normalized'] = $brandEmail === null ? null : strtolower($brandEmail);
        }

        $preparedBrandData['social_links'] = $this->encodeSocialLinks($requestData['social_links'] ?? null);

        foreach (self::IMAGE_PATH_FIELDS as $imagePathFieldName) {
            if (! array_key_exists($imagePathFieldName, $requestData)) {
                continue;
            }

            $storedImagePath = $this->nullableText($requestData[$imagePathFieldName], 255);
            if (! FMSBrandValidation::isValidStoredImagePath($storedImagePath)) {
                throw new InvalidArgumentException('Stored brand image name ' . $imagePathFieldName . ' is invalid.');
            }

            $preparedBrandData[$imagePathFieldName] = $storedImagePath;
        }

        $validationErrors = FMSBrandValidation::validateCreate($preparedBrandData);
        if ($validationErrors !== []) {
            throw new InvalidArgumentException('Brand create payload is invalid.');
        }

        return $preparedBrandData;
    }

    /**
     * @param array<string, mixed> $requestData
     * @param array<string, mixed> $existingBrandRow
     * @return array<string, mixed>
     */
    public function prepareUpdateData(array $requestData, array $existingBrandRow, int $actorUserIdentifier): array
    {
        $this->requirePositiveActor($actorUserIdentifier);
        $preparedBrandData = ['updated_by' => $actorUserIdentifier];

        foreach (self::TEXT_FIELDS as $field => $maxLen) {
            if (array_key_exists($field, $requestData)) {
                $preparedBrandData[$field] = $this->nullableText($requestData[$field], $maxLen);
            }
        }

        if (array_key_exists('email', $requestData)) {
            $brandEmail = $this->nullableText($requestData['email'], 190);
            $preparedBrandData['email'] = $brandEmail;
            $preparedBrandData['email_normalized'] = $brandEmail === null ? null : strtolower($brandEmail);
        }

        if (array_key_exists('social_links', $requestData)) {
            $preparedBrandData['social_links'] = $this->encodeSocialLinks($requestData['social_links']);
        }

        if (array_key_exists('is_active', $requestData)) {
            $preparedBrandData['is_active'] = $this->normalizeActiveFlag($requestData['is_active']);
        }

        foreach (self::IMAGE_PATH_FIELDS as $imagePathFieldName) {
            if (! array_key_exists($imagePathFieldName, $requestData)) {
                continue;
            }

            $storedImagePath = $this->nullableText($requestData[$imagePathFieldName], 255);
            if (! FMSBrandValidation::isValidStoredImagePath($storedImagePath)) {
                throw new InvalidArgumentException('Stored brand image name ' . $imagePathFieldName . ' is invalid.');
            }

            $preparedBrandData[$imagePathFieldName] = $storedImagePath;
        }

        $validationErrors = FMSBrandValidation::validateUpdate(array_merge($existingBrandRow, $preparedBrandData));
        if (isset($validationErrors['uuid']) && ! isset($preparedBrandData['uuid'])) {
            unset($validationErrors['uuid']);
        }
        if ($validationErrors !== []) {
            throw new InvalidArgumentException('Brand update payload is invalid.');
        }

        return $preparedBrandData;
    }

    /**
     * Encode social_links to JSON string, accepting array or JSON string.
     */
    public function encodeSocialLinks(mixed $raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === [] || $raw === '{}') {
            return null;
        }

        if (is_array($raw)) {
            $filtered = array_filter($raw, static fn ($v): bool => is_string($v) && trim($v) !== '');
            return $filtered === [] ? null : json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $filtered = array_filter($decoded, static fn ($v): bool => is_string($v) && trim($v) !== '');
                return $filtered === [] ? null : json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return null;
    }

    /**
     * Decode social_links JSON from DB to array, null-safe.
     *
     * @return array<string, string>
     */
    public function decodeSocialLinks(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function nullableText(mixed $fieldValue, int $maximumLength): ?string
    {
        if ($fieldValue === null) {
            return null;
        }

        $trimmedText = trim((string) $fieldValue);
        if ($trimmedText === '') {
            return null;
        }

        return mb_substr($trimmedText, 0, $maximumLength, 'UTF-8');
    }

    private function normalizeActiveFlag(mixed $activeFlag): int
    {
        if (in_array($activeFlag, [true, 1, '1'], true)) {
            return 1;
        }

        if (in_array($activeFlag, [false, 0, '0'], true)) {
            return 0;
        }

        throw new InvalidArgumentException('Brand active flag must be 0 or 1.');
    }

    private function requirePositiveActor(int $actorUserIdentifier): void
    {
        if ($actorUserIdentifier <= 0) {
            throw new InvalidArgumentException('Actor user identifier must be positive.');
        }
    }

    private function newUuid(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0F) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3F) | 0x80);
        $hexadecimalValue = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimalValue, 0, 8),
            substr($hexadecimalValue, 8, 4),
            substr($hexadecimalValue, 12, 4),
            substr($hexadecimalValue, 16, 4),
            substr($hexadecimalValue, 20, 12),
        );
    }
}
