<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin category fetched — Final, the back-office detail view of one
 * catalog category (Wave 7).
 *
 *   GetAdminCategoryInput → AdminCategoryFetched (Direct, safe read)
 *
 * AUTHZ ladder, same as {@see AdminCustomerFetched}:
 *   1. No admin session     → UnauthorizedAdminAccessException (403)
 *   2. Unknown categoryId   → CategoryNotFoundException        (404)
 *
 * Firewall first so anonymous-as-admin callers never learn whether an
 * id resolves.
 */
final readonly class AdminCategoryFetched
{
    public string $categoryId;
    public string $categoryName;
    public string|null $parentId;
    public int $sortNo;

    public function __construct(
        #[Input] string $categoryId,
        #[Inject] AdminSession $adminSession,
        #[Inject] CategoryStorageInterface $categories,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $categories->item($categoryId);
        if ($row === null) {
            throw new CategoryNotFoundException();
        }

        $this->categoryId = $row->categoryId;
        $this->categoryName = $row->categoryName;
        $this->parentId = $row->parentId;
        $this->sortNo = $row->sortNo;
    }
}
