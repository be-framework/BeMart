---
layout: default
title: "layoutId"
---

# layoutId

`layoutId` は EC-CUBE の CMS レイアウトを識別する不透明な文字列ハンドルである。ALPS では `dtb_layout.id` を直接露出するのではなく、Fake と SQL の差を隠す semantic identifier として扱う。

## Meaning

`layoutId` は `Layout` の取得・更新で使われる。レイアウトはインストーラまたは fixture が seed した行から読み出すもので、通常の admin 操作では新規作成・削除しない。

## ID Shape

Fake 実装は `lo-` プレフィックス付きのシードハンドルを持つ。代表値は `lo-pc-default` と `lo-sp-default` である。

SQL 実装は `dtb_layout.id` の `int unsigned AUTO_INCREMENT` を文字列化して使う。同じ interface の下で Fake と SQL の ID 形状が異なる。

## Scope Boundary

`Layout` には作成・削除アフォーダンスがない。ALPS 上の中心は `goLayoutList` と `doUpdateLayout` であり、ID generator は存在しない。

## SQL Behavior

非数値 ID は `SqlLayoutStorage` では miss として扱われ、getById / put のどちらも 404 経路を踏む。Fake の `lo-pc-default` や `nonexistent` を SQL backend に渡した場合も Fake と SQL で同形に失敗する。

## Related ID Pattern

この扱いは `blockId`、`categoryId` と同じ Fake / SQL 二重性である。
