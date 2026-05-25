<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a product class has zero stock and stockUnlimited=false.
 *
 * Note: requested > stock (with stock > 0) is NOT an error in EC-CUBE;
 * it auto-adjusts quantity down. This exception fires only on the hard
 * "no stock at all" case.
 */
#[Message([
    'en' => 'The product is out of stock.',
    'ja' => '商品の在庫がありません。',
])]
final class OutOfStockException extends DomainException
{
}
