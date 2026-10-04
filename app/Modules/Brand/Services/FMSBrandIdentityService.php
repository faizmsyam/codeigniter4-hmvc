<?php

namespace App\Modules\Brand\Services;

use App\Modules\Brand\Models\FMSBrandModel;
use Config\App;
use Throwable;

/**
 * Resolves the single active application brand once per request and keeps a
 * short shared cache. Layout partials consume the same immutable array, so no
 * partial performs an additional query or storage call.
 */
final class FMSBrandIdentityService
{
    public const CACHE_KEY = 'fms_active_brand';

    private const CACHE_TTL_SECONDS = 300;

    /** @var array<string, mixed>|null */
    private static ?array $requestCache = null;

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        if (self::$requestCache !== null) {
            return self::$requestCache;
        }

        try {
            $cached = cache()->get(self::CACHE_KEY);
            if (is_array($cached) && isset($cached['name'])) {
                return self::$requestCache = $cached;
            }
        } catch (Throwable) {
        }

        $identity = $this->fallbackIdentity();

        try {
            $row = (new FMSBrandModel())
                ->select('name, tagline, description, email, phone, whatsapp, address, website_url, social_links, logo_path, logo_light_path, favicon_path')
                ->where('is_active', 1)
                ->orderBy('id', 'ASC')
                ->first();

            if (is_array($row)) {
                $identity = $this->identityFromRow($row, $identity);
            }
        } catch (Throwable $exception) {
            log_message('error', 'Brand identity resolution failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            cache()->save(self::CACHE_KEY, $identity, self::CACHE_TTL_SECONDS);
        } catch (Throwable) {
        }

        return self::$requestCache = $identity;
    }

    public static function clearCache(): void
    {
        self::$requestCache = null;

        try {
            cache()->delete(self::CACHE_KEY);
        } catch (Throwable) {
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackIdentity(): array
    {
        $app = config(App::class);
        $fallbackLogo = base_url('assets/fms/img/media/logo.png');

        return [
            'name'           => $app->appName,
            'tagline'        => '',
            'description'    => APP_DESCRIPTION,
            'email'          => '',
            'phone'          => '',
            'whatsapp'       => '',
            'address'        => '',
            'website_url'    => base_url(),
            'social_links'   => [],
            'logo_url'       => $fallbackLogo,
            'logo_light_url' => $fallbackLogo,
            'favicon_url'    => base_url('assets/fms/img/media/favicon.ico'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $fallback
     * @return array<string, mixed>
     */
    private function identityFromRow(array $row, array $fallback): array
    {
        $socialLinks = json_decode((string) ($row['social_links'] ?? ''), true);
        if (! is_array($socialLinks)) {
            $socialLinks = [];
        }

        $logoPath = (string) ($row['logo_path'] ?? '');
        $logoLightPath = (string) ($row['logo_light_path'] ?? '');
        $faviconPath = (string) ($row['favicon_path'] ?? '');

        $logoUrl = $this->resolvePublicBrandUrl($logoPath, $fallback['logo_url'], 'brand/logo');
        $logoLightUrl = $this->resolvePublicBrandUrl(
            $logoLightPath !== '' ? $logoLightPath : $logoPath,
            $fallback['logo_light_url'],
            'brand/logo-light'
        );
        $faviconUrl = $this->resolvePublicBrandUrl($faviconPath, $fallback['favicon_url'], 'brand/favicon');

        return [
            'name'           => trim((string) ($row['name'] ?? '')) ?: $fallback['name'],
            'tagline'        => (string) ($row['tagline'] ?? ''),
            'description'    => (string) ($row['description'] ?? ''),
            'email'          => (string) ($row['email'] ?? ''),
            'phone'          => (string) ($row['phone'] ?? ''),
            'whatsapp'       => (string) ($row['whatsapp'] ?? ''),
            'address'        => (string) ($row['address'] ?? ''),
            'website_url'    => (string) ($row['website_url'] ?? ''),
            'social_links'   => $socialLinks,
            'logo_url'       => $logoUrl,
            'logo_light_url' => $logoLightUrl,
            'favicon_url'    => $faviconUrl,
        ];
    }

    private function resolvePublicBrandUrl(string $rawPath, string $fallbackUrl, string $publicRoute): string
    {
        $rawPath = trim($rawPath);
        if ($rawPath === '') {
            return $fallbackUrl;
        }

        if (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://')) {
            return $rawPath;
        }

        return site_url($publicRoute);
    }
}
