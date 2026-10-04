<?php

namespace App\Libraries;

use App\Config\FMSApi;

/**
 * @phpstan-type ApiSignature array{algorithm: string, key_id: string, payload_hash: string, value: string}
 * @phpstan-type ApiEnvelope array{code: int, status: bool, message: string, data: mixed, signature: ApiSignature}
 */
final class FMSApiResponse
{
    public function __construct(
        private readonly FMSResponseSigner $responseSigner = new FMSResponseSigner(),
        private readonly ?FMSApi $configuration = null,
    ) {
    }

    /**
     * @return ApiEnvelope
     */
    public function success(
        int $code,
        string $message,
        mixed $responseData = null,
        ?string $requestIdentifier = null,
        ?string $responseTimestamp = null,
    ): array {
        return $this->buildEnvelope(true, $code, $message, $responseData, $requestIdentifier, $responseTimestamp);
    }

    /**
     * @return ApiEnvelope
     */
    public function error(
        int $code,
        string $message,
        mixed $responseData = null,
        ?string $requestIdentifier = null,
        ?string $responseTimestamp = null,
    ): array {
        return $this->buildEnvelope(false, $code, $message, $responseData, $requestIdentifier, $responseTimestamp);
    }

    /**
     * @param array<string, array<int, string>> $validationErrors
     * @return ApiEnvelope
     */
    public function validationError(
        int $code,
        string $message,
        array $validationErrors,
        ?string $requestIdentifier = null,
        ?string $responseTimestamp = null,
    ): array {
        return $this->buildEnvelope(false, $code, $message, ['errors' => $validationErrors], $requestIdentifier, $responseTimestamp);
    }

    /**
     * @return ApiEnvelope
     */
    private function buildEnvelope(
        bool $responseStatus,
        int $responseCode,
        string $responseMessage,
        mixed $responseData,
        ?string $requestIdentifier,
        ?string $responseTimestamp,
    ): array {
        $unsignedEnvelope = [
            'code' => $responseCode,
            'status' => $responseStatus,
            'message' => $responseMessage,
            'data' => $responseData ?? new \stdClass(),
        ];

        if ($requestIdentifier !== null && $requestIdentifier !== '') {
            $unsignedEnvelope['request_id'] = $requestIdentifier;
        }

        if ($responseTimestamp !== null && $responseTimestamp !== '') {
            $unsignedEnvelope['timestamp'] = $responseTimestamp;
        }

        $unsignedEnvelope['signature'] = [
            'algorithm' => $this->configuration()?->responseSigningAlgorithm ?? 'Ed25519',
            'key_id' => $this->configuration()?->responseSigningKeyId ?? 'fms-response-default',
        ];

        $signature = $this->responseSigner->sign($unsignedEnvelope);
        $unsignedEnvelope['signature'] = $signature;

        return $unsignedEnvelope;
    }

    private function configuration(): ?FMSApi
    {
        return $this->configuration ?? config(FMSApi::class);
    }
}
