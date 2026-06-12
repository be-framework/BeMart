<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Html;

use BEAR\Dev\Html\HtmlLinkAuditLoggerInterface;
use BEAR\Dev\Html\LinkHeader;
use Override;

/**
 * Suppresses BEAR.Dev HTML link audit diagnostics.
 *
 * Link headers are still rendered by BEAR.Dev's LinkHeaderRenderer; only the
 * noisy template affordance warnings are intentionally ignored in BeMart's
 * default HTML contexts.
 */
final class SilentHtmlLinkAuditLogger implements HtmlLinkAuditLoggerInterface
{
    #[Override]
    public function warning(LinkHeader $link, string $reason): void
    {
    }
}
