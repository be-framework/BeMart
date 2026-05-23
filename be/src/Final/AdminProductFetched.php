<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin product fetched — Final, admin-side full-detail projection of
 * a single product.
 *
 *   GetAdminProductInput → AdminProductFetched  (Direct, safe read)
 *
 * ALPS `goProduct` has a single id that ALPS-tags BOTH customer-side
 * (`flow-browse`) AND admin-side (`flow-manage-product`). Pilot 1's
 * {@see ProductFetched} covers the customer-side projection (URL
 * `/product`, shallow body, no auth). This admin-side sibling
 * (URL `/admin/product`) requires an admin session and surfaces the
 * full ProductEntity — including the admin-only `note`, `searchWord`,
 * and `productStatus` columns the customer body intentionally hides.
 *
 * Be Framework G-17 (Pilot 10): the `#[Be]` chain destination is
 * fixed at the class level. Pilot 1's chain leads to ProductFetched
 * (the customer Final). The admin chain has a different return shape
 * (more columns, different AUTHZ ladder) and so gets its own Final.
 * Two sibling Finals, one ALPS id — same pattern as Wave 5O
 * {@see AdminCustomerCreated} sibling of Pilot 4 CustomerRegistered.
 *
 * AUTHZ — cross-firewall (Wave 4 / Wave 6 ladder):
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown productCode  → ProductNotFoundException          (404)
 *
 * The admin firewall check happens before existence is probed so an
 * admin-anonymous client has no business learning whether a given
 * productCode resolves.
 */
final readonly class AdminProductFetched
{
    public string $productCode;
    public string $productName;
    public int $price02;
    public int|null $stock;
    public int $productStatus;
    public string|null $description;
    public string|null $searchWord;
    public string|null $note;
    public string|null $imagePath;

    /** @var list<string> */
    public array $categoryNames;

    /** @var list<string> */
    public array $tagNames;

    /** @var list<string> */
    public array $classNames;

    public function __construct(
        #[Input] string $productCode,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = $productQuery->item($productCode);
        if (! $entity instanceof ProductEntity) {
            throw new ProductNotFoundException();
        }

        $this->productCode = $entity->productCode;
        $this->productName = $entity->productName;
        $this->price02 = $entity->price02;
        $this->stock = $entity->stock;
        $this->productStatus = $entity->productStatus;
        $this->description = $entity->description;
        $this->searchWord = $entity->searchWord;
        $this->note = $entity->note;
        $this->imagePath = $entity->imagePath;
        $this->categoryNames = $entity->categoryNames;
        $this->tagNames = $entity->tagNames;
        $this->classNames = $entity->classNames;
    }
}
