<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\Dev\Html\HtmlLinkAuditLoggerInterface;
use MyVendor\BeMart\Support\Html\SilentHtmlLinkAuditLogger;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

final class HtmlLinkAuditSuppressionTest extends TestCase
{
    public function testHtmlContextSuppressesDevHtmlLinkAuditWarnings(): void
    {
        $logger = HtmlTestInjector::getInstance()->getInstance(HtmlLinkAuditLoggerInterface::class);

        $this->assertInstanceOf(SilentHtmlLinkAuditLogger::class, $logger);
    }
}
