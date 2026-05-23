<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use Override;

final class SqlProductClassQuery implements ProductClassQueryInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    #[Override]
    public function item(string $productCode): ProductClassEntity|null
    {
        $row = $this->db->product_class_get(productCode: $productCode);
        return $row === null ? null : new ProductClassEntity(
            productCode: (string) $row['product_code'],
            productName: (string) $row['product_name'],
            stock: $row['stock'] === null ? null : (int) $row['stock'],
            stockUnlimited: (bool) $row['stock_unlimited'],
            saleLimit: $row['sale_limit'] === null ? null : (int) $row['sale_limit'],
            price02: (int) $row['price02'],
            deliveryFee: $row['delivery_fee'] === null ? 0 : (int) $row['delivery_fee'],
            saleTypeName: $row['sale_type_name'] === null ? '' : (string) $row['sale_type_name'],
            saleTypeId: $row['sale_type_id'] === null ? 0 : (int) $row['sale_type_id'],
        );
    }
}
