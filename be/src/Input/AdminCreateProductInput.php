<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\AdminProductCreating;

/**
 * Input for doCreateProduct — admin creates a product (management screen).
 *
 *   AdminCreateProductInput
 *     → AdminProductCreating (Multi-Reason Being + admin AUTHZ)
 *     → AdminProductCreated  (Final — persistence proof)
 *
 * Pattern: Wave 5O AdminCreateCustomer. Two parallel Reasons checked
 * inside the single Being:
 *
 *   1. AdminSession                  — admin firewall (Wave 4 contract)
 *   2. ProductQuery::item            — duplicate productCode collision
 *
 * ALPS `doCreateProduct.descriptor[]` lists `productName` and
 * `productStatus` as the headline fields; the remaining columns
 * (productCode, price02, stock, description, searchWord, note)
 * are part of the EC-CUBE admin form and map 1:1 to ProductEntity.
 *
 * `productStatus` is optional on the wire — null defaults to
 * STATUS_VISIBLE (=1) per the EC-CUBE admin convention (new products
 * are published by default). The Being holds the default-application
 * logic; this Input passes the raw nullable through.
 *
 * @link https://schema.org/Product
 * @link https://schema.org/CreateAction
 */
#[Be(AdminProductCreating::class)]
final readonly class AdminCreateProductInput
{
    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $productName
     * @psalm-taint-source input $price02
     * @psalm-taint-source input $stock
     * @psalm-taint-source input $productStatus
     * @psalm-taint-source input $description
     * @psalm-taint-source input $searchWord
     * @psalm-taint-source input $note
     */
    public function __construct(
        public string $productCode,
        public string $productName,
        public int $price02,
        public int|null $stock = null,
        public int|null $productStatus = null,
        public string|null $description = null,
        public string|null $searchWord = null,
        public string|null $note = null,
    ) {
    }
}
