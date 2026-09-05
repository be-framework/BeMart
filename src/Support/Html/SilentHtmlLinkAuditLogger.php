<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Html;

use BEAR\Dev\Html\HtmlLinkAuditLoggerInterface;
use BEAR\Dev\Html\LinkHeader;
use Override;

/**
 * HTML contexts render Link headers without audit output.
 *
 * The audit itself is judged once, by tests/Html/HtmlLinkAuditLedgerTest,
 * against the ledger; see docs/html-link-audit.md.
 */
final class SilentHtmlLinkAuditLogger implements HtmlLinkAuditLoggerInterface
{
    #[Override]
    public function warning(LinkHeader $link, string $reason): void
    {
    }
}
