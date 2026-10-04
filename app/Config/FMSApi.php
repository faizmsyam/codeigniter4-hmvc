<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

final class FMSApi extends BaseConfig
{
    public string $responseSigningAlgorithm = 'Ed25519';
    public string $responseSigningKeyId = 'fms-response-default';
    public string $responseSigningPrivateKeyPath = WRITEPATH . 'keys/fms-response-private.key';
    public string $responseSigningPublicKeyPath = WRITEPATH . 'keys/fms-response-public.key';
    public bool $requireSignedResponses = true;
}
