<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin product created — Final, proof a new product was persisted by
 * an admin operation.
 *
 * Cascade:
 *   AdminCreateProductInput
 *     → AdminProductCreating (Multi-Reason Being + admin AUTHZ)
 *     → AdminProductCreated  (this stage — persistence)
 *
 * Existence of this object proves ProductCommand::create() ran
 * without raising. Public surface mirrors the doCreateProduct
 * response shape: identity + the editable fields from the form.
 *
 * Pattern: Wave 5O AdminCustomerCreated. The Being decides whether
 * to proceed (AUTHZ + uniqueness); this Final only persists.
 */
final readonly class AdminProductCreated
{
    public string $productCode;
    public string $productName;
    public int $price02;
    public int|null $stock;
    public int $productStatus;
    public string|null $description;

    public function __construct(
        #[Input] string $productCode,
        #[Input] string $productName,
        #[Input] int $price02,
        #[Input] int|null $stock,
        #[Input] int $productStatus,
        #[Input] string|null $description,
        #[Input] string|null $searchWord,
        #[Input] string|null $note,
        #[Input] int|null $sortNo,
        #[Inject] ProductCommandInterface $command,
    ) {
        $command->create(new ProductEntity(
            productCode: $productCode,
            productName: $productName,
            price02: $price02,
            stock: $stock,
            productStatus: $productStatus,
            description: $description,
            searchWord: $searchWord,
            note: $note,
            sortNo: $sortNo,
        ));

        $this->productCode = $productCode;
        $this->productName = $productName;
        $this->price02 = $price02;
        $this->stock = $stock;
        $this->productStatus = $productStatus;
        $this->description = $description;
    }
}
