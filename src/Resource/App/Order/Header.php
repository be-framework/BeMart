<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App\Order;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;

/**
 * A finalized order's header, by order number
 *
 * The page that shows it used to run this query itself, which kept the read outside the resource
 * graph. It is here so a page can embed it - and so the decision not to cache it is written down
 * rather than implied: an order header belongs to one customer, and a cache entry keyed by order
 * number would be served to whoever guesses the number. No cache attribute, deliberately.
 */
class Header extends ResourceObject
{
    public function __construct(
        private readonly OrderQueryInterface $orderQuery,
    ) {
    }

    public function onGet(string $orderNo = ''): static
    {
        $order = $orderNo === '' ? null : $this->orderQuery->byOrderNo($orderNo);

        // An unknown or absent order number is not an error here: the screen that embeds this
        // renders its thank-you body either way, with the order-number block left empty.
        $this->code = Code::OK;
        $this->body = ['orderNo' => $order?->orderNo ?? ''];

        return $this;
    }
}
