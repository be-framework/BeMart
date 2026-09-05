<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\PreOrderAlreadyClaimedException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

use function is_array;

/**
 * The order number that won the pre-order claim.
 *
 * Read back right after the conditional claim so the caller can tell
 * whether its own number is the one now on the row. Two concurrent
 * checkouts both see the pre-order as PROCESSING; only the claim
 * decides, and only this read-back reports the verdict.
 */
final readonly class ClaimedOrderNo implements PostQueryInterface
{
    public function __construct(public string $orderNo)
    {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];

        return new static(is_array($row) ? (string) ($row['order_no'] ?? '') : '');
    }

    /**
     * @throws PreOrderAlreadyClaimedException when another request holds the claim.
     */
    public function assertHeldBy(string $orderNo): void
    {
        if ($this->orderNo === $orderNo) {
            return;
        }

        throw new PreOrderAlreadyClaimedException();
    }
}
