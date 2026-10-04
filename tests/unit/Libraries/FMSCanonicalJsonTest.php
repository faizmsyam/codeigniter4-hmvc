<?php

namespace Tests\Unit\Libraries;

use App\Libraries\FMSCanonicalJson;
use CodeIgniter\Test\CIUnitTestCase;
use Root23\JsonCanonicalizer\JsonCanonicalizer;

final class FMSCanonicalJsonTest extends CIUnitTestCase
{
    public function testDifferentObjectKeyOrderProducesIdenticalCanonicalJson(): void
    {
        $canonicalJson = new FMSCanonicalJson(new JsonCanonicalizer());

        $firstPayload = ['z' => 1, 'a' => ['b' => true, 'a' => 'teks']];
        $secondPayload = ['a' => ['a' => 'teks', 'b' => true], 'z' => 1];

        $this->assertSame(
            $canonicalJson->encode($firstPayload),
            $canonicalJson->encode($secondPayload),
        );
        $this->assertSame('{"a":{"a":"teks","b":true},"z":1}', $canonicalJson->encode($firstPayload));
    }

    public function testUnicodeAndNumbersAreCanonicalizedUsingRfc8785Library(): void
    {
        $canonicalJson = new FMSCanonicalJson(new JsonCanonicalizer());

        $this->assertSame(
            '{"number":333333333.3333333,"text":"Indonesia 🎉"}',
            $canonicalJson->encode(['text' => 'Indonesia 🎉', 'number' => 333333333.33333329]),
        );
    }
}
