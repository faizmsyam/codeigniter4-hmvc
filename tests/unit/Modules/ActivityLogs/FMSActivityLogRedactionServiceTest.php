<?php

namespace Tests\Unit\Modules\ActivityLogs;

use App\Modules\ActivityLogs\Services\FMSActivityLogRedactionService;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSActivityLogRedactionServiceTest extends CIUnitTestCase
{
    private FMSActivityLogRedactionService $redactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactionService = new FMSActivityLogRedactionService();
    }

    public function testNestedSensitivePayloadKeysAreRedactedRecursively(): void
    {
        $redactedPayload = $this->redactionService->redactPayload([
            'event'    => 'user.login',
            'password' => 'plaintext-secret',
            'nested'   => [
                'api_key' => 'raw-key-value',
                'safe'    => 'visible-value',
            ],
        ]);

        $this->assertSame('user.login', $redactedPayload['event']);
        $this->assertSame(FMSActivityLogRedactionService::REDACTED_PLACEHOLDER, $redactedPayload['password']);
        $this->assertSame(FMSActivityLogRedactionService::REDACTED_PLACEHOLDER, $redactedPayload['nested']['api_key']);
        $this->assertSame('visible-value', $redactedPayload['nested']['safe']);
    }

    public function testNetworkIdentifiersAreHashedAndNeverStoredRaw(): void
    {
        $ipHash = $this->redactionService->hashNetworkIdentifier('192.0.2.10');
        $userAgentHash = $this->redactionService->hashNetworkIdentifier('Mozilla/5.0 Test Agent');

        $this->assertSame(hash('sha256', '192.0.2.10'), $ipHash);
        $this->assertSame(hash('sha256', 'Mozilla/5.0 Test Agent'), $userAgentHash);
        $this->assertNull($this->redactionService->hashNetworkIdentifier(null));
        $this->assertNull($this->redactionService->hashNetworkIdentifier('   '));
    }

    public function testHashChainVerificationAcceptsOrderedRowsAndRejectsTampering(): void
    {
        $firstCanonicalData = ['event' => 'user.login', 'actor' => 7];
        $firstEntryHash = $this->redactionService->computeEntryHash($firstCanonicalData, null);

        $secondCanonicalData = ['event' => 'user.logout', 'actor' => 7];
        $secondEntryHash = $this->redactionService->computeEntryHash($secondCanonicalData, $firstEntryHash);

        $orderedRows = [
            array_merge($firstCanonicalData, ['id' => 1, 'previous_hash' => null, 'entry_hash' => $firstEntryHash]),
            array_merge($secondCanonicalData, ['id' => 2, 'previous_hash' => $firstEntryHash, 'entry_hash' => $secondEntryHash]),
        ];

        $this->assertTrue($this->redactionService->verifyHashChain($orderedRows));

        $tamperedRows = $orderedRows;
        $tamperedRows[1]['event'] = 'privilege.escalated';

        $this->assertFalse($this->redactionService->verifyHashChain($tamperedRows));
    }

    public function testCsvFormulaInjectionCellsAreNeutralized(): void
    {
        $this->assertSame("'=cmd|'/c calc'!A0", $this->redactionService->neutralizeCsvCell("=cmd|'/c calc'!A0"));
        $this->assertSame("'+SUM(1+1)", $this->redactionService->neutralizeCsvCell('+SUM(1+1)'));
        $this->assertSame("'-2+3", $this->redactionService->neutralizeCsvCell('-2+3'));
        $this->assertSame("'@evil", $this->redactionService->neutralizeCsvCell('@evil'));
        $this->assertSame('ordinary text', $this->redactionService->neutralizeCsvCell('ordinary text'));
        $this->assertSame('', $this->redactionService->neutralizeCsvCell(''));
    }
}
