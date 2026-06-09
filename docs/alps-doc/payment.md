---
layout: default
title: "Payment"
---

# Payment

Payment は EC-CUBE の支払方法マスタを表す。ALPS の `Payment` descriptor は、`dtb_payment` の管理画面向けスライスを `PaymentMethodAdminEntity` として投影する。

## Meaning

`Payment` は admin が支払方法名、手数料、利用条件、表示状態を保守するためのマスタである。storefront の購入フローでは配送方法との組み合わせ候補として参照される。

## EC-CUBE Schema Projection

BeMart の `PaymentMethodAdminEntity` は次の 6 項目を保持する。

- `paymentId` → `dtb_payment.id`
- `paymentMethodName` → `dtb_payment.payment_method`
- `charge` → `dtb_payment.charge`
- `ruleMin` → `dtb_payment.rule_min`
- `ruleMax` → `dtb_payment.rule_max`
- `visible` → `dtb_payment.visible`

`PaymentMethodAdminStorageInterface` は list / getById / put / remove の CRUD マスタとして扱う。

## Identity

`paymentId` は `PaymentMethodAdminIdQueryInterface` が `MAX(id) + 1` で先行採番する。`PaymentMethodAdminCreated` が Entity 構築前に `paymentId` を確定するためである。

非数値の `paymentId` は SQL では miss として扱い、404 経路に倒す。

## Migration Decisions

`creator_id` は `dtb_member` への FK だが、構造ダンプ上 `dtb_member` が空のため常に NULL を書く。NULL 以外を書き込むと FK 1452 が発生する。

`sort_no` / `payment_image` / `method_class` は BeMart の管理スライスに UI がないため常に NULL とする。`fixed` は NOT NULL DEFAULT 1 で、スキーマ既定値の 1 を書く。

`payment_method` は nullable だが Entity は non-null なので、read 時に NULL を空文字列へ coalesce する。`charge` は `decimal(12,2)` nullable だが JPY に小数部はなく、read 時に int へ truncate する。NULL は 0 として扱う。`rule_min` / `rule_max` は decimal nullable で、NULL は NULL のまま保持する。`visible` は tinyint と bool を双方向に cast する。

## Persistence Behavior

remove は `dtb_payment_option` の子行を先に削除してから `dtb_payment` を削除する。`dtb_payment_option.payment_id` は `dtb_payment.id` への FK であり、そのまま親を消すと FK 1451 が発生するためである。この防御的カスケードは Block と `dtb_block_position` の関係と同じ扱いである。

`create_date` / `update_date` は NOW() で維持する。`dtb_payment` にはタイムゾーン列がないため、サーバローカル時刻として扱う。`discriminator_type` は `payment` を書く。

## Implementation References

- Phase 2b
- `PaymentMethodAdminEntity`
- `PaymentMethodAdminStorageInterface`
- `PaymentMethodAdminIdQueryInterface`
- `PaymentMethodAdminCreated`
- `SqlPaymentMethodAdminStorage`
