<?php

namespace App\Libraries;

use App\Config\FMSApi;
use RuntimeException;

final class FMSResponseSigner
{
    public function __construct(
        private readonly FMSCanonicalJson $canonicalJson = new FMSCanonicalJson(),
        private readonly ?FMSApi $configuration = null,
    ) {
    }

    /**
     * @param array<string, mixed> $unsignedEnvelope
     * @return array{algorithm: string, key_id: string, payload_hash: string, value: string}
     */
    public function sign(array $unsignedEnvelope): array
    {
        $configuration = $this->configuration ?? config(FMSApi::class);
        $privateKey = $this->loadPrivateKey($configuration->responseSigningPrivateKeyPath);
        $canonicalPayload = $this->canonicalJson->encode($unsignedEnvelope);
        $detachedSignature = sodium_crypto_sign_detached($canonicalPayload, $privateKey);

        return [
            'algorithm' => $configuration->responseSigningAlgorithm,
            'key_id' => $configuration->responseSigningKeyId,
            'payload_hash' => $this->base64UrlEncode(hash('sha256', $canonicalPayload, true)),
            'value' => $this->base64UrlEncode($detachedSignature),
        ];
    }

    /**
     * @param array<string, mixed> $unsignedEnvelope
     */
    public function verify(array $unsignedEnvelope, string $encodedSignature, string $publicKeyPath): bool
    {
        $publicKey = $this->loadPublicKey($publicKeyPath);
        $detachedSignature = $this->base64UrlDecode($encodedSignature);
        $canonicalPayload = $this->canonicalJson->encode($unsignedEnvelope);

        return sodium_crypto_sign_verify_detached($detachedSignature, $canonicalPayload, $publicKey);
    }

    private function loadPrivateKey(string $privateKeyPath): string
    {
        $privateKey = $this->loadEncodedKey($privateKeyPath, 'private');

        if (strlen($privateKey) === SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            return sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($privateKey));
        }

        if (strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('Response signing private key length is invalid.');
        }

        return $privateKey;
    }

    private function loadPublicKey(string $publicKeyPath): string
    {
        $publicKey = $this->loadEncodedKey($publicKeyPath, 'public');

        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('Response signing public key length is invalid.');
        }

        return $publicKey;
    }

    private function loadEncodedKey(string $keyPath, string $keyType): string
    {
        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new RuntimeException(sprintf('Response signing %s key is unavailable.', $keyType));
        }

        $encodedKey = trim((string) file_get_contents($keyPath));
        if ($encodedKey === '') {
            throw new RuntimeException(sprintf('Response signing %s key is empty.', $keyType));
        }

        return $this->base64UrlDecode($encodedKey);
    }

    private function base64UrlEncode(string $binaryValue): string
    {
        return rtrim(strtr(base64_encode($binaryValue), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $encodedValue): string
    {
        if ($encodedValue === '' || preg_match('/[^A-Za-z0-9_-]/', $encodedValue) === 1) {
            throw new RuntimeException('Encoded key or signature format is invalid.');
        }

        $paddingLength = (4 - strlen($encodedValue) % 4) % 4;
        $decodedValue = base64_decode(strtr($encodedValue . str_repeat('=', $paddingLength), '-_', '+/'), true);

        if ($decodedValue === false) {
            throw new RuntimeException('Encoded key or signature cannot be decoded.');
        }

        return $decodedValue;
    }
}
