<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use Override;

use function array_map;

/**
 * Fake-backed Cart query.
 *
 * Parity with {@see SqlCartQuery}: the SQL read joins dtb_cart_item →
 * dtb_product / dtb_product_image / dtb_class_category to surface the
 * cart-row display fields (product name, thumbnail, variation). The
 * Fake has no relational master to join, so it re-derives the same
 * fields on read from the product-class Fake fixture
 * (`var/fake/product_classes.json`, via {@see FakeProductClassQuery}).
 *
 * Stored cart items carry only dtb_cart_item's real columns
 * (productCode / quantity / price) — exactly what the write path
 * (CartMerged -> CartItemAdded -> FakeCartCommand) persists. The
 * display fields are a READ-SIDE projection here just as the JOIN is
 * read-side in SQL. The Fake product fixture carries no image or
 * variation rows, so `mainImage` / variation fields resolve to null —
 * the same shape a SQL product with no image / no variation produces.
 *
 * The productName lookup goes through {@see ProductClassQueryInterface}
 * (bound to {@see FakeProductClassQuery} in the Fake configs that use
 * FakeCartQuery) — not the concrete class — so no extra AppModule
 * binding is needed.
 */
final class FakeCartQuery implements CartQueryInterface
{
    public function __construct(
        private readonly FakeCartStorage $storage,
        private readonly ProductClassQueryInterface $productClasses,
    ) {
    }

    #[Override]
    public function byCartKey(string $cartKey): CartEntity|null
    {
        $cart = $this->storage->get($cartKey);

        return $cart === null ? null : $this->enrich($cart);
    }

    /** @return list<CartEntity> */
    #[Override]
    public function bySessionPrefix(string $sessionPrefix): array
    {
        return array_map(
            fn (CartEntity $cart): CartEntity => $this->enrich($cart),
            $this->storage->getBySessionPrefix($sessionPrefix),
        );
    }

    /**
     * Re-derive each item's display fields from the product-class Fake
     * fixture — the Fake mirror of SqlCartQuery's read-side JOIN.
     * Items whose code resolves keep the looked-up productName; image
     * and variation stay as stored (null unless the fixture supplied
     * them) — the Fake product fixture carries no image/variation rows.
     */
    private function enrich(CartEntity $cart): CartEntity
    {
        $items = array_map(
            function (CartItemEntity $item): CartItemEntity {
                $productClass = $this->productClasses->item($item->productCode);

                return new CartItemEntity(
                    productCode: $item->productCode,
                    quantity: $item->quantity,
                    price: $item->price,
                    productClassId: $item->productClassId,
                    productId: $item->productId,
                    productName: $productClass?->productName ?? $item->productName,
                    mainImage: $item->mainImage,
                    classCategoryName1: $item->classCategoryName1,
                    className1: $item->className1,
                    classCategoryName2: $item->classCategoryName2,
                    className2: $item->className2,
                );
            },
            $cart->items,
        );

        return new CartEntity(
            cartKey: $cart->cartKey,
            saleTypeId: $cart->saleTypeId,
            saleTypeName: $cart->saleTypeName,
            items: $items,
            totalPrice: $cart->totalPrice,
            deliveryFeeTotal: $cart->deliveryFeeTotal,
            preOrderId: $cart->preOrderId,
        );
    }
}
