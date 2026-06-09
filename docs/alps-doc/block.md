---
layout: default
title: "Block"
---

# Block

Block は EC-CUBE 管理画面 CMS の画面ブロックを表す。ALPS の `Block` descriptor は、`dtb_block` のブロック本体を BeMart の `BlockEntity` として投影し、レイアウト上の配置情報とは境界を分ける。

## Meaning

`Block` は admin がブロック名、ファイル名、削除可否を保守するための CMS エントリである。storefront では Twig ブロックとしてページレイアウトに配置される。

## EC-CUBE Schema Projection

BeMart の `BlockEntity` は次の 4 項目を保持する。

- `blockId`
- `blockName`
- `blockFileName`
- `blockDeletable`

EC-CUBE の `device_type_id` / `use_controller` / `create_date` / `update_date` は Phase 2 スコープ外であり、書き込み時は NULL または 0 を使う。

## Scope Boundary

`dtb_block_position` はブロックの配置を表す別スコープである。Wave 9 admin slice では placement 行を INSERT しない。`Block` descriptor はブロック本体を扱い、配置編集は別モデルとして扱う。

## Migration Decisions

`device_type_id` は EC-CUBE のデバイス別バリアント機構である。BeMart 管理 UI では非対応であり、structure-only ダンプでは `mtb_device_type` が空のため固定 NULL とする。非 NULL 値を書き込むと FK 1452 が発生する。

`use_controller` は 0 固定とする。これはプレーンテンプレートとして扱い、管理 UI では controller-backed block を扱わないためである。

`block_name` は EC-CUBE 上 nullable だが、`BlockEntity` 上は non-null である。read 時に NULL を空文字列へ coalesce して投影形を維持する。

`deletable` は `tinyint(1) NOT NULL DEFAULT 1` である。`BlockCreated` は true=1 を書き、システム seed は 0=false を使う。

## Persistence Behavior

`doUpdateBlock` 遷移が存在するため、UPDATE 経路は実運用で踏まれる。

remove は `dtb_block_position` の placement 行を先に削除してから `dtb_block` を削除する。外部投入された placement 行がある場合でも FK 1451 を避けるためである。この扱いは `SqlPageStorage` が `dtb_page_layout` に対して行うものと同形である。

## Implementation References

- Phase 2b
- Wave 9
- `BlockEntity`
- `BlockCreated`
- `SqlBlockStorage`
