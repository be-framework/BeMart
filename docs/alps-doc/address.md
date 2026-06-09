---
layout: default
title: "Address"
---

# Address

Address は会員の配送先住所帳の 1 行を表す。ALPS の `Address` descriptor は、`dtb_customer_address` を BeMart の `AddressEntity` として投影する。

## Meaning

`Address` は customer が保持する追加配送先である。所有者キーは `customerId` のみであり、更新・削除では認可確認が必要になる。

## EC-CUBE Schema Projection

BeMart の `AddressEntity` は次の 12 項目を保持する。

- `addressId`
- `customerId`
- `name01`
- `name02`
- `kana01`
- `kana02`
- `companyName`
- `phoneNumber`
- `postalCode`
- `pref`
- `addr01`
- `addr02`

## Authorization

`CustomerAddressUpdated` / `CustomerAddressDeleted` は、getById の後に所有者一致を確認する。`customerId` が一致しない住所は更新・削除できない。

## Migration Decisions

`postalCode` / `addr01` / `addr02` は Entity では non-null だが、schema column は NULL 可である。hydrator は NULL を空文字列に正規化する。

`pref` は Entity では int だが、`pref_id` は NULL 可である。hydrator は NULL を 0 に正規化する。

`addressId` は Entity では string だが、schema の `id` は int auto-increment である。SQL 実装では文字列へ cast して扱う。

## Implementation References

- Phase 2b
- `AddressEntity`
- `SqlAddressStorage`
