<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;

final class ProductClassFactory
{
    public function factory(
        string|null $productCode,
        string|null $productName,
        int|string|null $stock,
        int|string|bool|null $stockUnlimited,
        int|string|null $saleLimit,
        int|string $price02,
        int|string|null $deliveryFee,
        int|string|null $saleTypeId,
        string|null $saleTypeName,
    ): ProductClassEntity {
        return new ProductClassEntity(
            productCode: (string) $productCode,
            productName: (string) $productName,
            stock: $stock === null ? null : (int) $stock,
            stockUnlimited: (bool) $stockUnlimited,
            saleLimit: $saleLimit === null ? null : (int) $saleLimit,
            price02: (int) $price02,
            deliveryFee: $deliveryFee === null ? 0 : (int) $deliveryFee,
            saleTypeName: $saleTypeName ?? '',
            saleTypeId: $saleTypeId === null ? 0 : (int) $saleTypeId,
        );
    }
}
