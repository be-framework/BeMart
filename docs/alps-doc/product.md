---
layout: default
title: "Product"
---

# Product

Product は EC-CUBE の商品販売単位を表す。ALPS の `Product` descriptor は、画面やリソースで扱う商品を `dtb_product` 単独ではなく、既定の `dtb_product_class` 1行と合わせた投影として定義する。

## Meaning

`Product` は storefront で表示され、admin で作成・編集され、cart / favorite / order の入口になる商品である。BeMart では `ProductEntity` として扱うが、意味としては「商品ヘッダ + 既定商品規格」の合成であり、単一テーブルの写像ではない。

## EC-CUBE Schema Projection

- `dtb_product`: `productName`, `productStatus`, `description`, `searchWord`, `productNote`
- 既定 `dtb_product_class`: `productCode`, `price02`, `stock`
- 既定商品規格の条件: `class_category_id1 IS NULL AND class_category_id2 IS NULL`
- この条件は `SqlProductClassQuery` / `SqlFavoriteStorage` と同じ規約で使う。

`product_code` 列は `dtb_product` 側には存在せず、`dtb_product_class` 側にある。そのため、自然キーとしての `productCode` は既定商品規格行を経由して解決する。

## Identity

`ProductEntity` は呼び出し元が供給する `productCode` 文字列でキーされる。`dtb_product.id` は EC-CUBE schema 側の autoinc 内部 ID として扱うため、BeMart 側の Product 用 ID generator は不要である。

## Migration Decisions

`ProductEntity::sortNo` は削除した。`dtb_product` には `sort_no` 列がなく、商品の並び順はカテゴリ別の `dtb_product_category.sort_no` にあるためである。`ProductEntity::sortNo` は EC-CUBE schema から乖離した BeMart 固有フィールドだった。

## Persistence Behavior

`create` / `copy` は、`dtb_product` のヘッダ行と既定 `dtb_product_class` 行を 1 つのアトミック単位で INSERT する。SAVEPOINT 対応の扱いは `SqlCsvColumnConfigStorage` と同形である。

`delete` は物理削除ではなく論理削除として扱う。具体的には `dtb_product.product_status_id` を `STATUS_WITHDRAWN = 3` に変更する。受注履歴は frozen copy のスナップショットを参照するため、商品本体を物理 DELETE しない。replay は冪等である。

## Master Data

`product_status_id` は `mtb_product_status` への nullable FK である。マスタが空のまま非 NULL 値を書き込むと FK 1452 が発生するため、`seedProductStatus` で商品ステータスマスタをシードする。

## Implementation References

- Phase 2b
- `ProductEntity`
- `SqlProductQuery`
- `SqlProductCommand`
