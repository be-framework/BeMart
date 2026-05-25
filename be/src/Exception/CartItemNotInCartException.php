<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Pilot 10 (doUpdateCartItemQuantity) / Pilot 11 (doRemoveCartItem):
 * the productCode in the request is not in any of the current
 * session's carts. The Be layer cannot mutate an absent item;
 * BEAR maps this to 404.
 */
#[Message([
    'en' => 'The product is not in the cart.',
    'ja' => '指定された商品はカートにありません。',
])]
final class CartItemNotInCartException extends DomainException
{
}
