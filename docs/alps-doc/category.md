---
layout: default
title: "Category"
---

# Category

Category は EC-CUBE の商品カテゴリを表す。ALPS の `Category` descriptor は、`dtb_category` のカテゴリ本体を BeMart の `CategoryEntity` として投影し、商品割当との結合表とは境界を分ける。

## Meaning

`Category` は商品一覧や管理画面の catalog taxonomy を構成する階層カテゴリである。親子関係と表示順を持つ。

## EC-CUBE Schema Projection

BeMart の `CategoryEntity` は次の 4 項目を保持する。

- `categoryId`
- `categoryName`
- `parentId`
- `sortNo`

EC-CUBE の `hierarchy` / `creator_id` / `create_date` / `update_date` は投影外である。

## Hierarchy

`hierarchy` は `int unsigned NOT NULL` の深さキャッシュである。INSERT / UPDATE 時には親から導出して書く。ルートは 1、子は `parent.hierarchy + 1` である。

投影は `hierarchy` を読まない。サブツリー再親付け時に孫へ cascade する処理は、平坦 admin slice のスコープ外である。

## Parent Constraint

`parent_category_id` は `dtb_category.id` への自己参照 FK である。子行は親の INSERT 後でなければ挿入できない。

Be 層の `CategoryCreated` は put 前に `getById(parentId)` で親存在を検証し、未知なら `CategoryNotFoundException` を返す。

## Persistence Behavior

`dtb_product_category` は商品割当の結合表で、`category_id` から `dtb_category.id` を参照する。Wave 7 admin slice では割当行を INSERT しないが、remove 時のみ防御的に `dtb_product_category` を先に DELETE して FK 1451 を避ける。

子を持つ親カテゴリの削除は、自己 FK 1451 を表面化させる。これは EC-CUBE と同じく、空でない親は削除できないという正しい挙動である。

## Implementation References

- Phase 2b
- `CategoryEntity`
- `CategoryCreated`
- `SqlCategoryStorage`
