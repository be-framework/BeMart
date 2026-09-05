<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Category deleted — Final, proof one category was removed (Wave 7).
 *
 *   DeleteCategoryInput → CategoryDeleted (Direct, idempotent)
 *
 * AUTHZ + existence ladder, same shape as
 * {@see CustomerAddressDeleted}: a re-delete after the row is gone
 * returns 404 (not silent 200) — the legitimate admin caller deserves
 * to learn the id is stale. Idempotency holds: the persisted state
 * (row absent) is reached identically regardless of repetition.
 *
 * Cascade behaviour (children promoted, product references untied) is
 * the EC-CUBE production semantic per ALPS doc. The flat in-memory
 * store does not implement the cascade in the first iteration — it
 * just drops the row. Phase 2 will add explicit reparenting and
 * product detachment once a real consumer (catalog migrations,
 * product-list filtering) is on the table.
 */
final readonly class CategoryDeleted
{
    public string $categoryId;

    public function __construct(
        #[Input] string $categoryId,
        #[Inject] AdminSession $adminSession,
        #[Inject] CategoryStorageInterface $categories,
        #[Inject] ProductCacheInvalidatorInterface $cacheInvalidator,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($categories->item($categoryId) === null) {
            throw new CategoryNotFoundException();
        }

        $categories->delete($categoryId);

        $cacheInvalidator->invalidateCorpus();

        $this->categoryId = $categoryId;
    }
}
