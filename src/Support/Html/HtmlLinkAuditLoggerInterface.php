<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Html;

interface HtmlLinkAuditLoggerInterface
{
    public function warning(LinkHeader $link, string $reason): void;
}
