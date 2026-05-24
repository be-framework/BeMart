<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Category updated — Final, proof one category row was edited in place
 * (Wave 7).
 *
 *   UpdateCategoryInput → CategoryUpdated (Direct, idempotent)
 *
 * AUTHZ + existence ladder, same shape as {@see AdminOrderUpdated}:
 *   1. No admin session         → UnauthorizedAdminAccessException (403)
 *   2. Unknown categoryId       → CategoryNotFoundException        (404)
 *   3. parentId set but unknown → CategoryNotFoundException        (404)
 *
 * Note: passing `parentId = null` in the body is INDISTINGUISHABLE
 * from "field not present" because the Input declares it nullable
 * with a default. To explicitly demote a node to root-level requires
 * a future "reparentToRoot" affordance — Phase 2 scope.
 */
final readonly class CategoryUpdated
{
    public string $categoryId;
    public string $categoryName;
    public string|null $parentId;
    public int $sortNo;

    public function __construct(
        #[Input] string $categoryId,
        #[Input] string|null $categoryName,
        #[Input] int|null $sortNo,
        #[Input] string|null $parentId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] CategoryStorageInterface $categories,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $categories->item($categoryId);
        if ($current === null) {
            throw new CategoryNotFoundException();
        }

        if ($parentId !== null && $categories->item($parentId) === null) {
            throw new CategoryNotFoundException();
        }

        $merged = new CategoryEntity(
            categoryId: $current->categoryId,
            categoryName: $categoryName ?? $current->categoryName,
            parentId: $parentId ?? $current->parentId,
            sortNo: $sortNo ?? $current->sortNo,
        );

        $categories->put($merged);

        $this->categoryId = $merged->categoryId;
        $this->categoryName = $merged->categoryName;
        $this->parentId = $merged->parentId;
        $this->sortNo = $merged->sortNo;
    }
}
