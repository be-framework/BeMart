---
layout: default
title: "Delivery"
---

# Delivery

Delivery は EC-CUBE の配送方法マスタを表す。ALPS の `Delivery` descriptor は、`dtb_delivery` の配送方法本体を `DeliveryEntity` として投影し、送料・時間帯・発送日目安とは境界を分ける。

## Meaning

`Delivery` は admin が配送方法名と表示状態を保守するためのマスタである。購入フローでは、商品種別や支払方法との組み合わせにより利用可能な配送方法を決める土台になる。

## EC-CUBE Schema Projection

BeMart の `DeliveryEntity` は厳密移植の整合のため、次の 3 項目のみを保持する。

- `deliveryId` → `dtb_delivery.id`
- `deliveryName` → `dtb_delivery.name`
- `visible` → `dtb_delivery.visible`

`dtb_delivery` には送料列が存在しない。地域別の基本送料は `dtb_delivery_fee`、送料無料閾値はグローバルな `dtb_base_info.delivery_free_amount` にある。そのため `SqlDeliveryStorage` は送料列を一切触らない。

## Scope Boundary

`DeliveryFee`、`DeliveryTime`、`DeliveryDuration` は `Delivery` の子 descriptor として残すが、実体は別モデルである。地域別送料、配送時間帯、発送日目安のモデル化時に、`dtb_delivery_fee` などの template フレーバとして参照する。

## Migration Decisions

`creator_id` は `dtb_member`、`sale_type_id` は `mtb_sale_type` を参照する。structure-only ダンプでは参照先マスタが空のため固定 NULL とする。非 NULL 値を書き込むと FK 1452 が発生する。

`name` は EC-CUBE 上 nullable だが、`DeliveryEntity` 上は non-null である。read 時に NULL を空文字列へ coalesce して投影形を維持する。`visible` は tinyint(1) NOT NULL DEFAULT 1 として扱う。

## Persistence Behavior

`doUpdateDelivery` 遷移が存在するため、UPDATE 経路は実運用で踏まれる。

remove は単純 DELETE とする。BeMart slice は子の `dtb_delivery_fee` / `dtb_delivery_time` / `dtb_payment_option` 行を INSERT しないため、防御的カスケードはこの段階では不要である。

## Implementation References

- Phase 2b
- `DeliveryEntity`
- `SqlDeliveryStorage`
- `DeliveryFee`
- `DeliveryTime`
- `DeliveryDuration`
