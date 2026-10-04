<?php

namespace App\Modules\Uploads\Services;

use App\Modules\Uploads\Contracts\FMSObjectStorageInterface;
use InvalidArgumentException;
use RuntimeException;

/** S3-compatible object storage using AWS Signature Version 4, including bucket lifecycle. */
final class FMSS3ObjectStorage implements FMSObjectStorageInterface
{
    private const ALGORITHM_NAME = 'AWS4-HMAC-SHA256';

    public function __construct(
        private readonly string $bucketName,
        private readonly string $regionName,
        private readonly string $accessKeyIdentifier,
        private readonly string $secretAccessKey,
        private readonly string $endpointHost,
        private readonly bool $pathStyleEndpoint = true,
        private readonly bool $useTls = true,
        private readonly string $objectKeyPrefix = 'fms_uploads',
        bool $createBucketAutomatically = true,
    ) {
        foreach ([$bucketName, $regionName, $accessKeyIdentifier, $secretAccessKey, $endpointHost] as $configurationValue) {
            if (trim($configurationValue) === '') {
                throw new InvalidArgumentException('S3 storage configuration is incomplete.');
            }
        }
        if (str_contains($endpointHost, '/') || str_contains($endpointHost, ' ') || str_contains($endpointHost, "\0")) {
            throw new InvalidArgumentException('S3 endpoint host is invalid.');
        }
        if ($createBucketAutomatically && ! $this->bucketExists()) {
            $this->createBucket();
        }
    }

    public function driverName(): string { return 's3'; }
    public function bucketName(): string { return $this->bucketName; }

    public function createBucket(): void
    {
        $response = $this->sendSignedRequest('PUT', '', [], '', false);
        if (! in_array($response['status'], [200, 204, 409], true)) {
            throw new RuntimeException('S3 bucket creation failed with status ' . $response['status'] . '.');
        }
    }

    public function bucketExists(): bool
    {
        return $this->sendSignedRequest('HEAD', '', [], '', false)['status'] === 200;
    }

    public function write(string $objectKey, string $sourceFilePath, string $contentType): array
    {
        if (! is_file($sourceFilePath)) {
            throw new InvalidArgumentException('Source file for object write does not exist.');
        }
        $sourceBytes = (string) file_get_contents($sourceFilePath);
        $response = $this->sendSignedRequest('PUT', $this->prefixedObjectKey($objectKey), [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($sourceBytes),
        ], $sourceBytes);
        if ($response['status'] < 200 || $response['status'] > 299) {
            throw new RuntimeException('S3 object write failed with status ' . $response['status'] . '.');
        }

        return ['byte_size' => strlen($sourceBytes), 'sha256' => hash('sha256', $sourceBytes)];
    }

    public function delete(string $objectKey): bool
    {
        $response = $this->sendSignedRequest('DELETE', $this->prefixedObjectKey($objectKey));

        return in_array($response['status'], [200, 204, 404], true);
    }

    public function exists(string $objectKey): bool
    {
        return $this->sendSignedRequest('HEAD', $this->prefixedObjectKey($objectKey))['status'] === 200;
    }

    public function createPresignedDownloadUrl(string $objectKey, int $expiresAtUnixTimestamp): string
    {
        $expirySeconds = $expiresAtUnixTimestamp - time();
        if ($expirySeconds < 1 || $expirySeconds > 604800) {
            throw new InvalidArgumentException('Presigned URL expiry must be between 1 second and 7 days.');
        }

        $requestTimestamp = gmdate('Ymd\THis\Z');
        $shortDate = substr($requestTimestamp, 0, 8);
        $scope = $shortDate . '/' . $this->regionName . '/s3/aws4_request';
        $signedObjectKey = $this->prefixedObjectKey($objectKey);
        $canonicalRequest = $this->canonicalRequest('GET', $signedObjectKey, $this->presignParameters($scope, $requestTimestamp, $expirySeconds));
        $signature = hash_hmac('sha256', $this->stringToSign($requestTimestamp, $scope, $canonicalRequest), $this->signingKey($shortDate));
        $queryString = http_build_query(array_merge($this->presignParameters($scope, $requestTimestamp, $expirySeconds), ['X-Amz-Signature' => $signature]), '', '&', PHP_QUERY_RFC3986);

        return $this->requestScheme() . '://' . $this->endpointHost . $this->canonicalUri($signedObjectKey) . '?' . $queryString;
    }

    public function verifyPresignedRequest(string $objectKey, int $expiresAtUnixTimestamp, string $keyIdentifier, string $providedSignature): bool
    {
        unset($objectKey, $expiresAtUnixTimestamp, $keyIdentifier, $providedSignature);

        // S3 validates SigV4 at the storage boundary; the application never proxies it.
        return false;
    }

    public function writeAny(string $objectKey, string $sourceFilePath, string $contentType): array
    {
        if (! is_file($sourceFilePath)) {
            throw new InvalidArgumentException('Source file for object write does not exist.');
        }

        /* Whitelist content-type yang diizinkan di S3 layer */
        $allowedContentTypes = ['image/webp', 'application/pdf'];
        if (! in_array($contentType, $allowedContentTypes, true)) {
            throw new InvalidArgumentException('Content type "' . $contentType . '" is not allowed for S3 storage.');
        }

        $sourceBytes = (string) file_get_contents($sourceFilePath);
        $response    = $this->sendSignedRequest('PUT', $this->prefixedObjectKey($objectKey), [
            'Content-Type'   => $contentType,
            'Content-Length' => (string) strlen($sourceBytes),
        ], $sourceBytes);

        if ($response['status'] < 200 || $response['status'] > 299) {
            throw new RuntimeException('S3 object write failed with status ' . $response['status'] . '.');
        }

        return ['byte_size' => strlen($sourceBytes), 'sha256' => hash('sha256', $sourceBytes)];
    }

    public function readObject(string $objectKey): array
    {
        unset($objectKey);
        throw new RuntimeException('S3 objects are read directly through S3 presigned URLs.');
    }

    /** @return array<string, string> */
    private function presignParameters(string $scope, string $requestTimestamp, int $expirySeconds): array
    {
        return [
            'X-Amz-Algorithm' => self::ALGORITHM_NAME,
            'X-Amz-Credential' => $this->accessKeyIdentifier . '/' . $scope,
            'X-Amz-Date' => $requestTimestamp,
            'X-Amz-Expires' => (string) $expirySeconds,
            'X-Amz-SignedHeaders' => 'host',
        ];
    }

    /**
     * @param array<string, string> $additionalHeaders
     * @return array{status: int, body: string}
     */
    private function sendSignedRequest(string $httpMethod, string $objectKey, array $additionalHeaders = [], string $requestBody = '', bool $useObjectKeyPrefix = true): array
    {
        $requestTimestamp = gmdate('Ymd\THis\Z');
        $shortDate = substr($requestTimestamp, 0, 8);
        $scope = $shortDate . '/' . $this->regionName . '/s3/aws4_request';
        $payloadHash = hash('sha256', $requestBody);
        $headersToSign = array_merge($additionalHeaders, [
            'Host' => $this->endpointHost,
            'X-Amz-Content-Sha256' => $payloadHash,
            'X-Amz-Date' => $requestTimestamp,
        ]);
        ksort($headersToSign, SORT_STRING);

        $canonicalHeaderLines = '';
        foreach ($headersToSign as $headerName => $headerValue) {
            $canonicalHeaderLines .= strtolower($headerName) . ':' . trim($headerValue) . "\n";
        }

        $canonicalRequest = implode("\n", [
            $httpMethod,
            $this->canonicalUri($objectKey),
            '',
            $canonicalHeaderLines,
            implode(';', array_map('strtolower', array_keys($headersToSign))),
            $payloadHash,
        ]);
        $signature = hash_hmac('sha256', $this->stringToSign($requestTimestamp, $scope, $canonicalRequest), $this->signingKey($shortDate));

        $curlHandle = curl_init($this->requestScheme() . '://' . $this->endpointHost . $this->canonicalUri($objectKey));
        if ($curlHandle === false) {
            throw new RuntimeException('S3 request could not be initialised.');
        }

        $requestHeaders = ['Authorization: ' . self::ALGORITHM_NAME . ' Credential=' . $this->accessKeyIdentifier . '/' . $scope . ', SignedHeaders=' . implode(';', array_map('strtolower', array_keys($headersToSign))) . ', Signature=' . $signature];
        foreach ($headersToSign as $headerName => $headerValue) {
            $requestHeaders[] = $headerName . ': ' . $headerValue;
        }

        curl_setopt_array($curlHandle, [
            CURLOPT_CUSTOMREQUEST => $httpMethod,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => $this->useTls,
            CURLOPT_SSL_VERIFYHOST => $this->useTls ? 2 : 0,
        ]);

        if ($httpMethod === 'PUT') {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $requestBody);
        }
        if ($httpMethod === 'HEAD') {
            curl_setopt($curlHandle, CURLOPT_NOBODY, true);
        }

        $responseBody = curl_exec($curlHandle);
        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);
        $transportError = curl_error($curlHandle);
        curl_close($curlHandle);

        if ($responseBody === false && $transportError !== '') {
            throw new RuntimeException('S3 transport failure: ' . $transportError);
        }

        return ['status' => $statusCode, 'body' => is_string($responseBody) ? $responseBody : ''];
    }

    /** @param array<string, string> $queryParameters */
    private function canonicalRequest(string $httpMethod, string $objectKey, array $queryParameters): string
    {
        ksort($queryParameters, SORT_STRING);
        $canonicalQueryString = http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);

        return implode("\n", [
            $httpMethod,
            $this->canonicalUri($objectKey),
            $canonicalQueryString,
            'host:' . $this->endpointHost . "\n",
            'host',
            'UNSIGNED-PAYLOAD',
        ]);
    }

    private function stringToSign(string $requestTimestamp, string $scope, string $canonicalRequest): string
    {
        return implode("\n", [self::ALGORITHM_NAME, $requestTimestamp, $scope, hash('sha256', $canonicalRequest)]);
    }

    private function signingKey(string $shortDate): string
    {
        return hash_hmac(
            'sha256',
            'aws4_request',
            hash_hmac(
                'sha256',
                's3',
                hash_hmac('sha256', $this->regionName, hash_hmac('sha256', $shortDate, 'AWS4' . $this->secretAccessKey, true), true),
                true,
            ),
            true,
        );
    }

    private function requestScheme(): string { return $this->useTls ? 'https' : 'http'; }

    private function prefixedObjectKey(string $objectKey): string
    {
        $normalizedObjectKey = trim($objectKey, '/');
        if ($normalizedObjectKey === '' || str_contains($normalizedObjectKey, '..') || str_contains($normalizedObjectKey, "\0")) {
            throw new InvalidArgumentException('S3 object key is invalid.');
        }

        return trim($this->objectKeyPrefix, '/') . '/' . $normalizedObjectKey;
    }

    private function canonicalUri(string $objectKey): string
    {
        if ($objectKey === '') {
            return $this->pathStyleEndpoint ? '/' . rawurlencode($this->bucketName) : '/';
        }

        $encodedObjectPath = implode('/', array_map('rawurlencode', explode('/', trim($objectKey, '/'))));

        return $this->pathStyleEndpoint
            ? '/' . rawurlencode($this->bucketName) . '/' . $encodedObjectPath
            : '/' . $encodedObjectPath;
    }
}
