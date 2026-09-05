<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Cache;

use BEAR\QueryRepository\ResourceStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Resource\App\Products;
use Override;

/**
 * Drops every cached view of the product corpus by its shared surrogate key
 *
 * A URI tag would reach one query string: `app://self/products?nameKeyword=cube` and the same
 * resource without a keyword are separate entries. Both declare `product-corpus`, so one tag
 * invalidation reaches every variant, and `app://self/product` declares it too - an edited product
 * disappears from the list and from its own page in the same call.
 *
 * The call is recorded as a `manual_invalidate` scope: it is the application asking, not the
 * framework reacting to a resource method, and the log keeps that distinction.
 */
final class ProductCacheInvalidator implements ProductCacheInvalidatorInterface
{
    public function __construct(
        private readonly ResourceStorageInterface $storage,
    ) {
    }

    #[Override]
    public function invalidateCorpus(): void
    {
        $this->storage->invalidateTags([Products::SURROGATE_KEY]);
    }
}
