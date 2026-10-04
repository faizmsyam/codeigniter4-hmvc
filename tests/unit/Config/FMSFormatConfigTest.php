<?php

namespace Tests\Unit\Config;

use Config\Format;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guards the Bruno fatal error: the application Format config overrides the
 * framework one, so it must always expose the properties the framework's
 * JSONFormatter reads (`formatterOptions['application/json']` and
 * `jsonEncodeDepth`). Dropping one makes every API response fatal.
 */
final class FMSFormatConfigTest extends CIUnitTestCase
{
    public function testApplicationFormatConfigExposesJsonEncodingProperties(): void
    {
        $applicationFormatConfig = new Format();

        $this->assertSame(
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            $applicationFormatConfig->formatterOptions['application/json'],
        );
        $this->assertSame(512, $applicationFormatConfig->jsonEncodeDepth);
        $this->assertGreaterThan(0, $applicationFormatConfig->jsonEncodeDepth);
    }

    public function testApplicationFormatConfigKeepsFrameworkFormatters(): void
    {
        $applicationFormatConfig = new Format();

        $this->assertContains('application/json', $applicationFormatConfig->supportedResponseFormats);
        $this->assertArrayHasKey('application/json', $applicationFormatConfig->formatters);
    }
}
