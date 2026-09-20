<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\RegisteredProductClassEntity;
use MyVendor\BeMart\Be\Reason\Provider\ProductClassRegisterIdProvider;
use MyVendor\BeMart\Be\Reason\Query\ProductClassStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Product class registered — Final, proof a new 規格 (SKU) row was
 * persisted by an admin operation (product-class-write).
 *
 *   RegisterProductClassInput → ProductClassRegistered (Direct, admin
 *                                                       AUTHZ)
 *
 * AUTHZ: the admin firewall is the FIRST statement — an admin-supplied
 * productCode can never be persisted unauthenticated. stock is nulled
 * when stockUnlimited is true, mirroring dtb_product_class semantics.
 */
final readonly class ProductClassRegistered
{
    public string $productClassId;
    public string $productCode;

    public function __construct(
        #[Input] string $productCode,
        #[Input] int $price02,
        #[Input] int $stock,
        #[Input] bool $stockUnlimited,
        #[Input] int $deliveryFee,
        #[Inject] AdminSession $adminSession,
        #[Inject] ProductClassStorageInterface $productClasses,
        #[Inject] ProductClassRegisterIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new RegisteredProductClassEntity(
            productClassId: $ids->get(),
            productCode: $productCode,
            price02: $price02,
            stock: $stockUnlimited ? null : $stock,
            stockUnlimited: $stockUnlimited,
            deliveryFee: $deliveryFee,
        );

        $productClasses->put($entity);

        $this->productClassId = $entity->productClassId;
        $this->productCode = $entity->productCode;
    }
}
