<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use MyVendor\BeMart\Support\Html\HtmlLinkAuditLoggerInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\Unbound;

/**
 * The default HTML context does not bind an audit logger at all - #132 moved the html-link-audit
 * scan (and everything it needs) out of {@see \MyVendor\BeMart\Support\Html\LinkHeaderModule},
 * into the test-only {@see \MyVendor\BeMart\Support\Html\AuditedLinkHeaderModule}. A page render
 * through the standard context cannot warn anywhere, because nothing in that graph can even ask
 * for a logger to warn through - not because a bound logger happens to be silent.
 */
final class HtmlLinkAuditSuppressionTest extends TestCase
{
    public function testHtmlContextHasNoAuditLoggerBinding(): void
    {
        $this->expectException(Unbound::class);

        HtmlTestInjector::getInstance()->getInstance(HtmlLinkAuditLoggerInterface::class);
    }
}
