<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

final class BulkStatusUpdateResult
{
    public function __construct(private readonly int $changedCount) {}

    public function changedCount(): int
    {
        return $this->changedCount;
    }
}
