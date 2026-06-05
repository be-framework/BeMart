---
layout: default
title: "Layout"
---

# Layout

Layout は EC-CUBE 管理画面 CMS のページレイアウトを表す。ALPS の `Layout` descriptor は、`dtb_layout` のレイアウト本体を BeMart の `LayoutEntity` として投影する。

## Meaning

`Layout` は PC標準、スマホ標準などのレイアウト名とデバイス種別を管理する CMS エントリである。ページやブロックの配置先になるが、配置そのものは `dtb_page_layout` / `dtb_block_position` の別スコープで扱う。

## EC-CUBE Schema Projection

BeMart の `LayoutEntity` は次の 3 項目を保持する。

- `layoutId`
- `layoutName`
- `deviceType`

EC-CUBE の `create_date` / `update_date` / `discriminator_type` は投影外である。

## Scope Boundary

`LayoutStorageInterface` は list / getById / put のみを提供する。ALPS 上も `goLayoutList` と `doUpdateLayout` のみで、作成・削除アフォーダンスはない。そのため `LayoutIdProvider` と remove メソッドは存在しない。

レイアウトはこの interface から削除されないため、`dtb_block_position` / `dtb_page_layout` への cascade 問題は発生しない。

## Migration Decisions

`layout_name` は EC-CUBE 上 nullable だが、`LayoutEntity` 上は non-null である。read 時に NULL を空文字列へ coalesce して投影形を維持する。

`device_type_id` は `smallint(5) unsigned nullable` で、`mtb_device_type.id` を参照する。`LayoutEntity::deviceType` は non-null int として扱い、read 時に NULL を 0 へ coalesce する。EC-CUBE の代表値は 10=PC、2=モバイルである。

structure-only ダンプでは `mtb_device_type` が空のため、INSERT は常に `device_type_id = NULL` を書く。非 NULL 値を書き込むと FK 1452 が発生する。

fixture は `device_type_id` を直接 seed するため、実運用で踏まれる唯一の書き経路である `doUpdateLayout` 経由の UPDATE は `device_type_id` を触らない。`deviceType` 投影は EC-CUBE enum を round-trip する。

## Persistence Behavior

put は SELECT 1 で probe し、hit なら `layout_name` のみ UPDATE する。miss なら明示 id で INSERT するが、これは防御的な経路である。Layout 作成 Final は存在しないため通常は未到達である。

## Implementation References

- Phase 2b
- `LayoutEntity`
- `LayoutStorageInterface`
- `SqlLayoutStorage`
