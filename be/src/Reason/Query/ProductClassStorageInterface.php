<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\RegisteredProductClassEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin write storage for product 規格 (SKU) rows — Ray.MediaQuery
 * direct proxy (Wave: product-class-write).
 *
 *   - put(productClass): persist one dtb_product_class SKU row.
 *   - item(productClassId): single-row lookup (null on miss).
 *
 * Separate from the read-path {@see ProductClassQueryInterface} (cart
 * lookup by productCode). Fake context returns void/null with no JSON
 * fixture; the Phase-2 SQL files back dtb_product_class.
 */
interface ProductClassStorageInterface
{
    #[DbQuery('tproduct_class_put')]
    public function put(RegisteredProductClassEntity $productClass): void;

    #[DbQuery('tproduct_class_get')]
    public function item(string $productClassId): RegisteredProductClassEntity|null;
}
