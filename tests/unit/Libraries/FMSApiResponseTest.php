<?php

namespace Tests\Unit\Libraries;

use App\Config\FMSApi;
use App\Libraries\FMSApiResponse;
use App\Libraries\FMSCanonicalJson;
use App\Libraries\FMSResponseSigner;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

final class FMSApiResponseTest extends CIUnitTestCase
{
    private string $privateKeyPath;
    private string $publicKeyPath;
    private FMSApi $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $keyPair = sodium_crypto_sign_keypair();
        $this->privateKeyPath = WRITEPATH . 'tests/fms-response-private.key';
        $this->publicKeyPath = WRITEPATH . 'tests/fms-response-public.key';
        $this->writeEncodedKey($this->privateKeyPath, sodium_crypto_sign_secretkey($keyPair));
        $this->writeEncodedKey($this->publicKeyPath, sodium_crypto_sign_publickey($keyPair));

        $this->configuration = new FMSApi();
        $this->configuration->responseSigningKeyId = 'fms-response-test-01';
        $this->configuration->responseSigningPrivateKeyPath = $this->privateKeyPath;
        $this->configuration->responseSigningPublicKeyPath = $this->publicKeyPath;
    }

    protected function tearDown(): void
    {
        @unlink($this->privateKeyPath);
        @unlink($this->publicKeyPath);
        parent::tearDown();
    }

    public function testSuccessEnvelopeContainsRequiredFieldsAndValidSignature(): void
    {
        $responseSigner = new FMSResponseSigner(new FMSCanonicalJson(), $this->configuration);
        $apiResponse = new FMSApiResponse($responseSigner, $this->configuration);

        $envelope = $apiResponse->success(
            200,
            'Request berhasil.',
            ['user' => ['uuid' => 'user-01']],
            'request-01',
            '2026-09-28T10:00:00+00:00',
        );

        $this->assertSame(
            ['code', 'status', 'message', 'data', 'request_id', 'timestamp', 'signature'],
            array_keys($envelope),
        );
        $this->assertTrue($envelope['status']);
        $this->assertSame('Ed25519', $envelope['signature']['algorithm']);
        $this->assertSame('fms-response-test-01', $envelope['signature']['key_id']);

        $unsignedEnvelope = $this->unsignedEnvelope($envelope);
        $this->assertTrue($responseSigner->verify(
            $unsignedEnvelope,
            $envelope['signature']['value'],
            $this->publicKeyPath,
        ));
    }

    public function testEverySignedFieldMutationInvalidatesSignature(): void
    {
        $responseSigner = new FMSResponseSigner(new FMSCanonicalJson(), $this->configuration);
        $apiResponse = new FMSApiResponse($responseSigner, $this->configuration);
        $envelope = $apiResponse->success(
            200,
            'Request berhasil.',
            ['count' => 1],
            'request-01',
            '2026-09-28T10:00:00+00:00',
        );
        $signatureValue = $envelope['signature']['value'];
        $unsignedEnvelope = $this->unsignedEnvelope($envelope);

        $mutations = [
            static function (array $payload): array {
                $payload['code'] = 418;
                return $payload;
            },
            static function (array $payload): array {
                $payload['status'] = false;
                return $payload;
            },
            static function (array $payload): array {
                $payload['message'] = 'Berubah.';
                return $payload;
            },
            static function (array $payload): array {
                $payload['data']['count'] = 2;
                return $payload;
            },
            static function (array $payload): array {
                $payload['request_id'] = 'request-02';
                return $payload;
            },
            static function (array $payload): array {
                $payload['timestamp'] = '2026-09-28T10:00:01+00:00';
                return $payload;
            },
            static function (array $payload): array {
                $payload['signature']['key_id'] = 'other-key';
                return $payload;
            },
        ];

        foreach ($mutations as $mutatePayload) {
            $this->assertFalse($responseSigner->verify(
                $mutatePayload($unsignedEnvelope),
                $signatureValue,
                $this->publicKeyPath,
            ));
        }
    }

    public function testValidationErrorUsesSafeNestedErrorObject(): void
    {
        $responseSigner = new FMSResponseSigner(new FMSCanonicalJson(), $this->configuration);
        $apiResponse = new FMSApiResponse($responseSigner, $this->configuration);
        $envelope = $apiResponse->validationError(
            422,
            'Data yang dikirim tidak valid.',
            ['email' => ['Format email tidak valid.']],
        );

        $this->assertFalse($envelope['status']);
        $this->assertSame(
            ['email' => ['Format email tidak valid.']],
            $envelope['data']['errors'],
        );
    }

    public function testMissingPrivateKeyFailsClosed(): void
    {
        $this->configuration->responseSigningPrivateKeyPath = WRITEPATH . 'tests/missing-private.key';
        $responseSigner = new FMSResponseSigner(new FMSCanonicalJson(), $this->configuration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unavailable');
        $responseSigner->sign(['code' => 200]);
    }

    /**
     * @param array<string, mixed> $envelope
     * @return array<string, mixed>
     */
    private function unsignedEnvelope(array $envelope): array
    {
        $signature = $envelope['signature'];
        unset($signature['payload_hash'], $signature['value']);
        $envelope['signature'] = $signature;

        return $envelope;
    }

    private function writeEncodedKey(string $keyPath, string $binaryKey): void
    {
        $encodedKey = rtrim(strtr(base64_encode($binaryKey), '+/', '-_'), '=');
        if (!is_dir(dirname($keyPath))) {
            mkdir(dirname($keyPath), 0700, true);
        }
        file_put_contents($keyPath, $encodedKey, LOCK_EX);
        chmod($keyPath, 0600);
    }
}
