<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One per-shipping delivery block inside an {@see OrderHistoryEntity}.
 *
 * Phase 3 enrichment — the order-history detail screen (goMypageHistory)
 * renders one `ec-orderDelivery__title` + address + delivery-date block
 * per `Order.Shippings` row in EC-CUBE's `Mypage/history.twig`. BeMart's
 * earlier thin `MypageHistoryFetched` projection carried none of this;
 * this entity restores the per-shipping fidelity.
 *
 * Maps onto `dtb_shipping` columns: name01/02, kana01/02, postal_code,
 * pref (resolved to a display name — `dtb_shipping.pref_id` is a FK to
 * the `mtb_pref` master, an empty table in the structure-only dump, so
 * `prefName` is the empty string until the master is seeded), addr01/02,
 * phone_number, delivery_name, delivery_date, delivery_time.
 *
 * `items` are the `dtb_order_item` rows whose `shipping_id` points at
 * this shipping row — the per-delivery line items the screen lists.
 */
final readonly class OrderHistoryShippingEntity
{
    /** @param list<OrderHistoryItemEntity> $items */
    public function __construct(
        public string $name01,
        public string $name02,
        public string $kana01,
        public string $kana02,
        public string $postalCode,
        public string $prefName,
        public string $addr01,
        public string $addr02,
        public string $phoneNumber,
        public string $deliveryName,
        public string $deliveryDate,
        public string $deliveryTime,
        public array $items,
    ) {
    }
}
