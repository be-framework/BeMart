<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin category list fetched — Final, the back-office view of every
 * catalog category as a flat list (Wave 7).
 *
 *   GetAdminCategoryListInput → AdminCategoryListFetched (Direct, safe read)
 *
 * AUTHZ: refuses non-admin requests via
 * {@see UnauthorizedAdminAccessException} (mapped to 403 by the
 * Resource layer). Mirrors {@see CustomerListFetched}'s firewall
 * shape — no enumeration risk because the resource itself is
 * admin-only.
 *
 * Rows are projected (not the entity itself) to mirror the
 * {@see CustomerAddressListFetched} convention; downstream UI consumes
 * the projection without touching CategoryEntity directly.
 */
final readonly class AdminCategoryListFetched
{
    public int $count;

    /** @var list<array{categoryId: string, categoryName: string, parentId: string|null, sortNo: int}> */
    public array $categories;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] CategoryStorageInterface $categories,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $categories->list();

        $this->count = count($rows);
        $this->categories = array_map(
            static fn (CategoryEntity $row): array => [
                'categoryId' => $row->categoryId,
                'categoryName' => $row->categoryName,
                'parentId' => $row->parentId,
                'sortNo' => $row->sortNo,
            ],
            $rows,
        );
    }
}
