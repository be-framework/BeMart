---
layout: default
title: "Be-First Migration Method"
---

# Be-First Migration Method

## 目的

EC-CUBE を移植するとき、最初から HTTP や画面を作らず、まず Be で業務意味を固定する。ここでは新規設計ではなく移植なので、制約は fake から発明するのではなく、既存ソースと ALPS から抽出する。

## 基本方針

- 最初は `Be-only` で始める
- `BEAR.Sunday` は後で薄く載せる
- 最初の中心は `semantic variables`
- 制約は `source/ALPS first`
- `semantic-ex` は補助として使う

## なぜ Be-first か

最初に確かめたいのは HTTP 層ではなく、EC-CUBE の業務意味を `Semantic / Input / Final / Reason` に落とせるかどうかです。ここが固まる前に `page://` や `app://` を先に作ると、検証対象が増えすぎます。

## 何を一次資料にするか

制約の一次資料は次の順で扱う。

1. 移植元 PHP ソース
2. 既存 validator / form / entity / service / purchase flow
3. DB schema
4. `alps.json`
5. 既存テストと実挙動

制約が既にそこにあるなら、それをそのまま使う。`semantic-ex` は、制約が散っている、矛盾している、暗黙的で読みにくい、といった場合だけ使う。

## 最初の成果物

最初に作るのは画面ではなく `semantic catalog` です。各 variable について次を固定する。

- name
- meaning
- type
- constraints
- source
- boundary tests

最初の候補:

- `Email`
- `PostalCode`
- `Pref`
- `Quantity`
- `Price`
- `CurrencyCode`
- `ProductCode`
- `ProductStatus`
- `Stock`
- `SaleLimit`

## 進め方

1. source と ALPS から semantic variable を抽出する
2. variable ごとの制約を PHPUnit で固定する
3. `Input -> Final` を最小単位で作る
4. `Reason` を外部依存ごとに分離する
5. その後に `BEAR.Sunday` を薄く被せる

## 最初の packet

`ProductList` より先に、Be-only の価値が出やすい packet を置く。

- `Quantity`
- `ProductCode`
- `Email`
- `AddCartItemInput`
- `CartInput -> CartUpdated`

具体例は `/run migrate Quantity` → `/run migrate AddCartItemInput` の順で走らせる（`.claude/workflows/migrate.json`）。`AddCartItemInput` が `Quantity` semantic に依存する形にしている。

## skills の使い分け

- `be-semantic`
  - semantic variable の定義を始めるとき
- `be`
  - `Input / Final / Reason` に落とすとき
- `semantic-ex`
  - source だけでは制約が読み切れないとき
- `planning-with-files`
  - 長時間作業の復帰点を残すとき

## 位置づけ

この方法は「まず Be で意味を固定し、その後で BEAR.Sunday に接続する」ための前段です。最終的に storefront を置き換えるには `BEAR.Sunday` は必要ですが、最初の成功条件は `Be-only` で十分です。
