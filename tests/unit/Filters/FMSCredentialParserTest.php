<?php

namespace Tests\Unit\Filters;

use App\Filters\FMSApiKeyCredentialParser;
use App\Filters\FMSBasicAuthenticationCredentialParser;
use App\Filters\FMSBearerTokenParser;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FMSCredentialParserTest extends CIUnitTestCase
{
    public function testBearerTokenParserAcceptsOnlyOneStrictBearerToken(): void
    {
        $parser = new FMSBearerTokenParser();

        $result = $parser->parse(['Bearer header.payload.signature']);

        $this->assertTrue($result['valid']);
        $this->assertSame('header.payload.signature', $result['token']);
    }

    /**
     * @param list<string> $headerValues
     */
    #[DataProvider('invalidBearerHeaderProvider')]
    public function testBearerTokenParserRejectsAmbiguousAndMalformedValues(array $headerValues, string $expectedReason): void
    {
        $result = (new FMSBearerTokenParser(32))->parse($headerValues);

        $this->assertFalse($result['valid']);
        $this->assertSame($expectedReason, $result['reason']);
        $this->assertNull($result['token']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function invalidBearerHeaderProvider(): iterable
    {
        yield 'missing' => [[], FMSBearerTokenParser::REASON_MISSING];
        yield 'duplicate header lines' => [
            ['Bearer one.two.three', 'Bearer four.five.six'],
            FMSBearerTokenParser::REASON_DUPLICATE,
        ];
        yield 'comma joined duplicate' => [
            ['Bearer one.two.three, Bearer four.five.six'],
            FMSBearerTokenParser::REASON_DUPLICATE,
        ];
        yield 'oversized' => [
            ['Bearer ' . str_repeat('a', 10) . '.' . str_repeat('b', 10) . '.' . str_repeat('c', 10)],
            FMSBearerTokenParser::REASON_OVERSIZED,
        ];
        yield 'wrong scheme' => [['Basic abc'], FMSBearerTokenParser::REASON_MALFORMED];
        yield 'whitespace in token' => [['Bearer one.two. three'], FMSBearerTokenParser::REASON_MALFORMED];
        yield 'line injection' => [["Bearer one.two.three\r\nX"], FMSBearerTokenParser::REASON_MALFORMED];
    }

    public function testApiKeyParserAcceptsIdentifierAndSecret(): void
    {
        $result = (new FMSApiKeyCredentialParser())->parse([
            'ABCDEF1234567890.' . str_repeat('s', 32),
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('ABCDEF1234567890', $result['key_id']);
        $this->assertSame(str_repeat('s', 32), $result['key_secret']);
    }

    public function testApiKeyParserRejectsConflictingHeadersAndDuplicateValues(): void
    {
        $parser = new FMSApiKeyCredentialParser();
        $credential = 'ABCDEF1234567890.' . str_repeat('s', 32);

        $conflict = $parser->parse([$credential], ['ApiKey ' . $credential]);
        $duplicate = $parser->parse([$credential . ',' . $credential]);

        $this->assertSame(FMSApiKeyCredentialParser::REASON_HEADER_CONFLICT, $conflict['reason']);
        $this->assertSame(FMSApiKeyCredentialParser::REASON_DUPLICATE, $duplicate['reason']);
    }

    public function testApiKeyParserRejectsInvalidShapeAndOversizedCredential(): void
    {
        $parser = new FMSApiKeyCredentialParser(64);

        $malformed = $parser->parse(['too-short.secret']);
        $oversized = $parser->parse(['ABCDEF1234567890.' . str_repeat('x', 80)]);

        $this->assertSame(FMSApiKeyCredentialParser::REASON_MALFORMED, $malformed['reason']);
        $this->assertSame(FMSApiKeyCredentialParser::REASON_OVERSIZED, $oversized['reason']);
    }

    public function testBasicParserDecodesUsernameAndPasswordContainingColon(): void
    {
        $result = (new FMSBasicAuthenticationCredentialParser())->parse([
            'Basic ' . base64_encode('service-client:secret:with:colon'),
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('service-client', $result['username']);
        $this->assertSame('secret:with:colon', $result['password']);
    }

    /**
     * @param list<string> $headerValues
     */
    #[DataProvider('invalidBasicHeaderProvider')]
    public function testBasicParserRejectsMalformedAndAmbiguousValues(array $headerValues, string $expectedReason): void
    {
        $result = (new FMSBasicAuthenticationCredentialParser(64))->parse($headerValues);

        $this->assertFalse($result['valid']);
        $this->assertSame($expectedReason, $result['reason']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function invalidBasicHeaderProvider(): iterable
    {
        yield 'missing' => [[], FMSBasicAuthenticationCredentialParser::REASON_MISSING];
        yield 'duplicate' => [
            ['Basic ' . base64_encode('first:one'), 'Basic ' . base64_encode('second:two')],
            FMSBasicAuthenticationCredentialParser::REASON_DUPLICATE,
        ];
        yield 'invalid base64' => [['Basic not*base64'], FMSBasicAuthenticationCredentialParser::REASON_MALFORMED];
        yield 'no colon' => [['Basic ' . base64_encode('username-only')], FMSBasicAuthenticationCredentialParser::REASON_MALFORMED];
        yield 'empty username' => [['Basic ' . base64_encode(':password')], FMSBasicAuthenticationCredentialParser::REASON_MALFORMED];
        yield 'control byte' => [['Basic ' . base64_encode("user:pass\nword")], FMSBasicAuthenticationCredentialParser::REASON_MALFORMED];
        yield 'oversized' => [['Basic ' . str_repeat('A', 68)], FMSBasicAuthenticationCredentialParser::REASON_OVERSIZED];
    }
}
