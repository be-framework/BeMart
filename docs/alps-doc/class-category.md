---
layout: default
title: "ClassCategory"
---

# ClassCategory

ClassCategory は EC-CUBE の商品規格軸に属する具体値を表す。ALPS の `ClassCategory` descriptor は、`dtb_class_category` を BeMart の `ClassCategoryEntity` として投影する。

## Meaning

`ClassCategory` は `ClassName` が表す軸の具体値である。例として「カラー」軸の「赤」「青」、「サイズ」軸の S/M/L がある。

## EC-CUBE Schema Projection

BeMart の `ClassCategoryEntity` は次の 3 項目を保持する。

- `classCategoryId`
- `classNameId`
- `name`

EC-CUBE の `backend_name` / `sort_no` / `visible` / `creator_id` / `create_date` / `update_date` は投影外である。

## Parent Constraint

`classNameId` は所属する規格軸を示す。SQL schema では `class_name_id` が `dtb_class_name.id` を参照する NOT NULL FK である。

SQL 実装の put は親 `dtb_class_name` 行の存在を前提とする。fixture は親 class_name を先に INSERT する。

## Migration Decisions

`sort_no` は `int unsigned NOT NULL` で表示順を表す。DEFAULT はない。INSERT 時は `MAX(sort_no) + 1` で末尾追加スロットを導出して書くが、投影では読まない。UPDATE 時も触らず、リネームしても表示位置を保持する。

`visible` は固定 1 とする。admin slice に表示切替 UI がないためである。`backend_name` は nullable のため固定 NULL とする。

`creator_id` は `dtb_member` への FK だが、structure-only ダンプでは `dtb_member` が空のため固定 NULL とする。非 NULL を書くと FK 1452 が発生する。

## Persistence Behavior

`dtb_product_class` は `class_category_id1` / `class_category_id2` で `dtb_class_category.id` を参照する。Wave 7 admin slice では `dtb_product_class` を INSERT しないため、remove はこの FK を防御的にケアしない。

外部投入された参照があれば FK 1451 が表面化する。契約テストはこのケースをシードしない。

## Implementation References

- Phase 2b
- `ClassCategoryEntity`
- `SqlClassCategoryStorage`
