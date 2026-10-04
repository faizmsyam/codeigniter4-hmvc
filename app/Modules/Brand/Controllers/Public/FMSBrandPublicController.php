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
        /* PENTING: Jangan panggil session() di controller aset statis/publik agar tidak merusak CSRF cookie session */
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

    private function serveBrandAsset(string $field, string $fallbackAsset, ?string $fallbackField = null): ResponseInterface
    {
        $this->sanitizeCacheHeaders();

        try {
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
                return $this->serveFallback($fallbackAsset);
            }

            $storage = FMSObjectStorageFactory::create();
            $storedObject = $storage->readObject($objectKey);
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

    private function serveFallback(string $relativeAsset): ResponseInterface
    {
        $path = rtrim(PUBLICPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeAsset);
        if (! is_file($path) || ! is_readable($path)) {
            return $this->response->setStatusCode(404)->setBody('');
        }

        $bytes = file_get_contents($path);
        if (! is_string($bytes)) {
            return $this->response->setStatusCode(404)->setBody('');
        }

        $contentType = str_ends_with(strtolower($path), '.ico') ? 'image/x-icon' : 'image/png';
        $etag = '"' . hash('sha256', $bytes) . '"';

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Length', (string) strlen($bytes))
            ->setHeader('Cache-Control', 'public, max-age=86400, must-revalidate')
            ->setHeader('ETag', $etag)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($bytes);
    }

    private function sanitizeCacheHeaders(): void
    {
        $this->response->removeHeader('Pragma');
        $this->response->removeHeader('Expires');
    }
}
