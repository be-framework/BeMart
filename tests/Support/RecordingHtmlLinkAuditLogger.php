<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use BEAR\Dev\Html\HtmlLinkAuditLoggerInterface;
use BEAR\Dev\Html\LinkHeader;
use Override;

/** @psalm-type Warning = array{rel: string, method: string, href: string, reason: string} */
final class RecordingHtmlLinkAuditLogger implements HtmlLinkAuditLoggerInterface
{
    /** @var list<Warning> */
    private array $warnings = [];

    #[Override]
    public function warning(LinkHeader $link, string $reason): void
    {
        $this->warnings[] = [
            'rel' => $link->rel,
            'method' => $link->method,
            'href' => $link->href,
            'reason' => $reason,
        ];
    }

    /** @return list<Warning> */
    public function drain(): array
    {
        $warnings = $this->warnings;
        $this->warnings = [];

        return $warnings;
    }
}
