---
layout: default
title: "ShippingAddress"
---

# ShippingAddress

ShippingAddress は受注に紐づく配送先住所を表す。ALPS の `ShippingAddress` descriptor は、`dtb_shipping` の単一配送先行を BeMart の `ShippingAddressEntity` として投影する。

## Meaning

`ShippingAddress` は受注単位の単一お届け先である。`orderNo` をキーに `doSelectShippingAddress` / `doUpdateShippingAddress` が put し、`goExportShipping` が listAll で全件取得する。

## EC-CUBE Schema Projection

BeMart の `ShippingAddressEntity` は次の 8 項目を保持する。

- `orderNo`
- `name01`
- `name02`
- `postalCode`
- `pref`
- `addr01`
- `addr02`
- `phoneNumber`

`dtb_shipping` は `order_id` で `dtb_order` を参照するため、SQL 層は `order_no` から `order_id` を解決して読み書きする。

## Scope Boundary

Order 配下のインライン `Shipping` descriptor は、EC-CUBE schema 全列を写すテンプレート視点である。`ShippingAddress` は storage source-of-truth の `src-entity` 視点であり、目的が異なる。

## Migration Decisions

`name01` / `name02` は column NOT NULL で直接扱う。

`postalCode` / `addr01` / `addr02` は Entity では non-null だが、schema column は NULL 可である。hydrator は NULL を空文字列に正規化する。

`pref` は Entity では int だが、`pref_id` は NULL 可 FK である。`pref = 0` は NULL として書き、read 時は NULL を 0 へ正規化する。

`phoneNumber` は 14 文字上限である。

## Persistence Behavior

EC-CUBE の `dtb_shipping` は単一受注に複数行、つまり複数配送先を許す。BeMart のこの投影では `order_id` 単一行を前提とする。put は `order_id` で既存行を探索し、あれば UPDATE、なければ INSERT する。

## Implementation References

- Phase 2b
- `ShippingAddressEntity`
- `SqlShippingAddressStorage`
