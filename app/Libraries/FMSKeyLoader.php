<?php

namespace App\Libraries;

use RuntimeException;

final class FMSKeyLoader
{
    public function loadPrivateKey(string $privateKeyPath): string
    {
        $privateKey = $this->loadEncodedKey($privateKeyPath, 'private');

        if (strlen($privateKey) === SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            $keyPair = sodium_crypto_sign_seed_keypair($privateKey);

            return sodium_crypto_sign_secretkey($keyPair);
        }

        if (strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('Signing private key length is invalid.');
        }

        return $privateKey;
    }

    public function loadPublicKey(string $publicKeyPath): string
    {
        $publicKey = $this->loadEncodedKey($publicKeyPath, 'public');

        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('Signing public key length is invalid.');
        }

        return $publicKey;
    }

    public function writeEncodedKey(string $keyPath, string $binaryKey, int $fileMode = 0600): void
    {
        $keyDirectory = dirname($keyPath);
        if (!is_dir($keyDirectory) && !mkdir($keyDirectory, 0700, true) && !is_dir($keyDirectory)) {
            throw new RuntimeException('Signing key directory cannot be created.');
        }

        if (file_put_contents($keyPath, $this->base64UrlEncode($binaryKey), LOCK_EX) === false) {
            throw new RuntimeException('Signing key cannot be written.');
        }

        chmod($keyPath, $fileMode);
    }

    public function generateKeyPair(): array
    {
        $keyPair = sodium_crypto_sign_keypair();

        return [
            'private_key' => sodium_crypto_sign_secretkey($keyPair),
            'public_key' => sodium_crypto_sign_publickey($keyPair),
        ];
    }

    public function base64UrlEncode(string $binaryValue): string
    {
        return rtrim(strtr(base64_encode($binaryValue), '+/', '-_'), '=');
    }

    public function base64UrlDecode(string $encodedValue): string
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

    private function loadEncodedKey(string $keyPath, string $keyType): string
    {
        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new RuntimeException(sprintf('Signing %s key is unavailable.', $keyType));
        }

        $encodedKey = trim((string) file_get_contents($keyPath));
        if ($encodedKey === '') {
            throw new RuntimeException(sprintf('Signing %s key is empty.', $keyType));
        }

        return $this->base64UrlDecode($encodedKey);
    }
}
