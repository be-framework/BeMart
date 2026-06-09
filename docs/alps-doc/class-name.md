---
layout: default
title: "ClassName"
---

# ClassName

ClassName は EC-CUBE の商品規格軸を表す。ALPS の `ClassName` descriptor は、`dtb_class_name` の規格軸本体を BeMart の `ClassNameEntity` として投影する。

## Meaning

`ClassName` は商品バリエーションの軸である。例としてカラー、サイズなどがある。EC-CUBE の "class" は OOP のクラスではなく、商品規格を意味する。

## EC-CUBE Schema Projection

BeMart の `ClassNameEntity` は次の 2 項目を保持する。

- `classNameId`
- `name`

EC-CUBE の `backend_name` / `sort_no` / `creator_id` / `create_date` / `update_date` は投影外である。

## Migration Decisions

`sort_no` は `int unsigned NOT NULL` で表示順を表す。DEFAULT はない。INSERT 時は `MAX(sort_no) + 1` で末尾追加スロットを導出して書くが、投影では読まない。UPDATE 時も触らず、リネームしても表示位置を保持する。

`backend_name` は nullable であり、admin slice に編集 UI がないため固定 NULL とする。

`creator_id` は `dtb_member` への FK だが、structure-only ダンプでは `dtb_member` が空のため固定 NULL とする。非 NULL を書くと FK 1452 が発生する。

## Persistence Behavior

`dtb_class_category` は `class_name_id` から `dtb_class_name.id` を参照する子行である。Wave 7 admin slice では子行を INSERT しないが、remove 時のみ防御的に子行を先に DELETE して FK 1451 を避ける。

## Implementation References

- Phase 2b
- `ClassNameEntity`
- `SqlClassNameStorage`
