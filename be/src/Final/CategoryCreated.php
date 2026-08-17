<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Exception\CategoryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\CategoryIdProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Category created — Final, proof a new catalog category was persisted
 * by an admin operation (Wave 7).
 *
 *   CreateCategoryInput → CategoryCreated (Direct, admin AUTHZ)
 *
 * AUTHZ + referential integrity ladder:
 *
 *   1. No admin session             → UnauthorizedAdminAccessException (403)
 *   2. parentId set but unknown     → CategoryNotFoundException        (404)
 *
 * The parent-resolution probe runs after the firewall check, same
 * shape as {@see AdminCustomerFetched}.
 *
 * The new categoryId is server-generated via
 * {@see CategoryIdProvider} so the body cannot collide with
 * an existing row.
 */
final readonly class CategoryCreated
{
    public string $categoryId;
    public string $categoryName;
    public string|null $parentId;
    public int $sortNo;

    public function __construct(
        #[Input] string $categoryName,
        #[Input] int $sortNo,
        #[Input] string|null $parentId,
        #[Inject] AdminSession $adminSession,
        #[Inject] CategoryStorageInterface $categories,
        #[Inject] ProductCacheInvalidatorInterface $cacheInvalidator,
        #[Inject] CategoryIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($parentId !== null && $categories->item($parentId) === null) {
            throw new CategoryNotFoundException();
        }

        $entity = new CategoryEntity(
            categoryId: $ids->get(),
            categoryName: $categoryName,
            parentId: $parentId,
            sortNo: $sortNo,
        );

        $categories->put($entity);

        $cacheInvalidator->invalidateCorpus();

        $this->categoryId = $entity->categoryId;
        $this->categoryName = $entity->categoryName;
        $this->parentId = $entity->parentId;
        $this->sortNo = $entity->sortNo;
    }
}
