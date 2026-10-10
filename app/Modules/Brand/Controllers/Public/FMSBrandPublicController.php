<?php

namespace App\Modules\Brand\Controllers\Public;

use App\Core\FMSController;
use App\Modules\Brand\Models\FMSBrandModel;
use App\Modules\Uploads\Services\FMSObjectStorageFactory;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSBrandPublicController extends FMSController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        /* Jangan membuka session untuk aset publik: aman bagi cookie CSRF/session. */
        $this->request  = $request;
        $this->response = $response;
    }

    public function logo(): ResponseInterface
    {
        return $this->serveBrandAsset('logo_path', 'assets/fms/img/media/logo.png');
    }

    public function logoLight(): ResponseInterface
    {
        return $this->serveBrandAsset('logo_light_path', 'assets/fms/img/media/logo.png', 'logo_path');
    }

    public function favicon(): ResponseInterface
    {
        return $this->serveBrandAsset('favicon_path', 'assets/fms/img/media/favicon.ico');
    }

    public function manifest(): ResponseInterface
    {
        $brand = (new \App\Modules\Brand\Services\FMSBrandIdentityService())->get();
        $brandName = trim((string) ($brand['name'] ?? '')) ?: 'FMS App Starter';
        $shortName = function_exists('mb_substr') ? mb_substr($brandName, 0, 12) : substr($brandName, 0, 12);

        $manifest = [
            'id' => base_url('/'),
            'name' => $brandName,
            'short_name' => $shortName,
            'description' => (string) ($brand['description'] ?? 'FMS application platform'),
            'start_url' => site_url('fms-auth/in?source=pwa'),
            'scope' => base_url('/'),
            'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'orientation' => 'any',
            'background_color' => '#0f172a',
            'theme_color' => '#625ffd',
            'categories' => ['business', 'productivity', 'utilities'],
            'lang' => 'id-ID',
            'dir' => 'ltr',
            'icons' => [
                ['src' => site_url('brand/pwa-icon/192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => site_url('brand/pwa-icon/512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => site_url('brand/pwa-icon/192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => site_url('brand/pwa-icon/512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'Dashboard', 'short_name' => 'Dashboard', 'url' => site_url(ROUTE_ADMIN . '/dashboard?source=pwa-shortcut')],
                ['name' => 'Profil', 'short_name' => 'Profil', 'url' => site_url(ROUTE_ADMIN . '/profile?source=pwa-shortcut')],
            ],
        ];

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->setHeader('Cache-Control', 'public, max-age=300, must-revalidate')
            ->setBody(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    public function pwaIcon(string $size = '192'): ResponseInterface
    {
        $pixelSize = $size === '512' ? 512 : 192;
        $this->sanitizeCacheHeaders();

        try {
            $source = $this->readBrandAsset('logo_path');
            if ($source === null) {
                $source = $this->readFallbackAsset('assets/fms/img/media/logo.png');
            }

            $png = $this->resizePng($source['body'], $pixelSize);
            $etag = '"' . hash('sha256', $png) . '"';

            if (trim($this->request->getHeaderLine('If-None-Match')) === $etag) {
                return $this->response
                    ->setStatusCode(304)
                    ->setHeader('ETag', $etag)
                    ->setHeader('Cache-Control', 'public, max-age=3600, must-revalidate');
            }

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'image/png')
                ->setHeader('Content-Length', (string) strlen($png))
                ->setHeader('Cache-Control', 'public, max-age=3600, must-revalidate')
                ->setHeader('ETag', $etag)
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($png);
        } catch (Throwable $exception) {
            log_message('error', 'Public PWA brand icon failed: {message}', ['message' => $exception->getMessage()]);

            return $this->serveFallback("assets/fms/pwa/icon-{$pixelSize}.png");
        }
    }

    private function serveBrandAsset(string $field, string $fallbackAsset, ?string $fallbackField = null): ResponseInterface
    {
        $this->sanitizeCacheHeaders();

        try {
            $storedObject = $this->readBrandAsset($field, $fallbackField);
            if ($storedObject === null) {
                return $this->serveFallback($fallbackAsset);
            }

            $etag = '"' . $storedObject['sha256'] . '"';
            if (trim($this->request->getHeaderLine('If-None-Match')) === $etag) {
                return $this->response
                    ->setStatusCode(304)
                    ->setHeader('ETag', $etag)
                    ->setHeader('Cache-Control', 'public, max-age=86400, must-revalidate');
            }

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', $storedObject['content_type'])
                ->setHeader('Content-Length', (string) $storedObject['byte_size'])
                ->setHeader('Cache-Control', 'public, max-age=86400, must-revalidate')
                ->setHeader('ETag', $etag)
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($storedObject['body']);
        } catch (Throwable $exception) {
            log_message('error', 'Public brand asset failed: {message}', ['message' => $exception->getMessage()]);

            return $this->serveFallback($fallbackAsset);
        }
    }

    /** @return array{body: string, content_type: string, byte_size: int, sha256: string}|null */
    private function readBrandAsset(string $field, ?string $fallbackField = null): ?array
    {
        $fields = ['id', $field];
        if ($fallbackField !== null) {
            $fields[] = $fallbackField;
        }

        $row = (new FMSBrandModel())
            ->select(implode(', ', array_unique($fields)))
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->first();

        $objectKey = is_array($row) ? trim((string) ($row[$field] ?? '')) : '';
        if ($objectKey === '' && $fallbackField !== null && is_array($row)) {
            $objectKey = trim((string) ($row[$fallbackField] ?? ''));
        }

        if ($objectKey === '' || str_starts_with($objectKey, 'http://') || str_starts_with($objectKey, 'https://')) {
            return null;
        }

        return FMSObjectStorageFactory::create()->readObject($objectKey);
    }

    /** @return array{body: string, content_type: string, byte_size: int, sha256: string} */
    private function readFallbackAsset(string $relativeAsset): array
    {
        $path = rtrim(PUBLICPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeAsset);
        $bytes = is_file($path) && is_readable($path) ? file_get_contents($path) : false;
        if (! is_string($bytes)) {
            throw new \RuntimeException('Aset brand fallback tidak tersedia.');
        }

        return [
            'body' => $bytes,
            'content_type' => str_ends_with(strtolower($path), '.ico') ? 'image/x-icon' : 'image/png',
            'byte_size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
        ];
    }

    private function resizePng(string $sourceBytes, int $pixelSize): string
    {
        $source = @imagecreatefromstring($sourceBytes);
        if ($source === false) {
            throw new \RuntimeException('Logo brand tidak dapat dibaca sebagai gambar.');
        }

        $target = imagecreatetruecolor($pixelSize, $pixelSize);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $pixelSize, $pixelSize, $transparent);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($pixelSize / $sourceWidth, $pixelSize / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetX = (int) floor(($pixelSize - $targetWidth) / 2);
        $targetY = (int) floor(($pixelSize - $targetHeight) / 2);

        imagecopyresampled($target, $source, $targetX, $targetY, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        ob_start();
        imagepng($target, null, 9);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if (! is_string($png)) {
            throw new \RuntimeException('Icon PWA gagal dibuat.');
        }

        return $png;
    }

    private function serveFallback(string $relativeAsset): ResponseInterface
    {
        try {
            $asset = $this->readFallbackAsset($relativeAsset);
        } catch (Throwable) {
            return $this->response->setStatusCode(404)->setBody('');
        }

        $etag = '"' . $asset['sha256'] . '"';

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', $asset['content_type'])
            ->setHeader('Content-Length', (string) $asset['byte_size'])
            ->setHeader('Cache-Control', 'public, max-age=86400, must-revalidate')
            ->setHeader('ETag', $etag)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($asset['body']);
    }

    private function sanitizeCacheHeaders(): void
    {
        $this->response->removeHeader('Pragma');
        $this->response->removeHeader('Expires');
    }
}
