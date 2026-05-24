<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One row in dtb_cart_item, enriched with the display fields EC-CUBE's
 * cart-row (Cart/index.twig `ec-cartRow`) renders.
 *
 * The leading three fields are dtb_cart_item's real columns:
 *  - `productCode`  — the SKU's natural key (held on dtb_product_class);
 *  - `quantity`     — the post-adjustment value (after Stock and
 *                     SaleLimit caps);
 *  - `price`        — the per-unit price02 captured at add time
 *                     (a snapshot, not a live join).
 *
 * The remaining fields are READ-SIDE display projections, joined from
 * dtb_product_class → dtb_product / dtb_product_image / dtb_class_category
 * so the cart screen can render the product name, thumbnail, detail
 * link and variation axes — re-derived from EC-CUBE's cart template,
 * not from a deliberately-thin Entity:
 *  - `productClassId`     — dtb_product_class.id; the cart operation
 *                           links (remove / up / down) key on this SKU;
 *  - `productId`          — dtb_product.id; the product-detail link
 *                           target;
 *  - `productName`        — dtb_product.name; the displayed name;
 *  - `mainImage`          — dtb_product_image filename (lowest sort_no);
 *                           null when the product has no image;
 *  - `classCategoryName1` / `className1` — variation axis 1 value and
 *                           its axis name (e.g. "赤" / "色"); null when
 *                           the product has no variation;
 *  - `classCategoryName2` / `className2` — variation axis 2, optional.
 *
 * The display fields are nullable / default-empty: a write-side caller
 * (cart merge / quantity replace / reorder) constructs a CartItemEntity
 * only to persist dtb_cart_item's real columns and supplies none of
 * them; a read-side query (CartQueryInterface / JSON-backed fake cart handler) populates them
 * from the joins.
 */
final readonly class CartItemEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $productCode,
        public int $quantity,
        public int $price,
        public int $productClassId = 0,
        public int $productId = 0,
        public string $productName = '',
        public string|null $mainImage = null,
        public string|null $classCategoryName1 = null,
        public string|null $className1 = null,
        public string|null $classCategoryName2 = null,
        public string|null $className2 = null,
    ) {
    }
}
