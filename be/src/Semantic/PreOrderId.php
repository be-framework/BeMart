<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PreOrderIdFormatException;

use function preg_match;

/**
 * Pre-order identifier — 40-character lowercase hexadecimal token
 * issued when a cart transitions to a confirmable pre-order.
 *
 * Example: "deadbeefcafe1234567890abcdef01234567890a".
 */
final class PreOrderId
{
    #[Validate]
    public function validate(string $preOrderId): void
    {
        if (preg_match('/^[0-9a-f]{40}$/', $preOrderId) !== 1) {
            throw new PreOrderIdFormatException();
        }
    }
}
