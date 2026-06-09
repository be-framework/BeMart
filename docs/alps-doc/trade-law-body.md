---
layout: default
title: "tradeLawBody"
---

# tradeLawBody

`tradeLawBody` は特定商取引法ページ本文の単一ブロブ投影である。EC-CUBE の項目別行をそのまま表すのではなく、Wave 8 の管理画面ではページ全体を 1 本の本文として扱う。

## Meaning

`tradeLawBody` は特定商取引法ページの表示本文である。`TradeLawStorageInterface` は get で現在の全文を返し、update で全文を置換する。

## EC-CUBE Schema Projection

EC-CUBE 4.3 では `dtb_tradelaw` が項目ごとの行を持つ。各行は `tradeLawName` / `tradeLawDescription` / `sortNo` / `displayOrderScreen` を保持し、最大15行の項目として扱われる。

Wave 8 の BeMart 投影では、この行集合をページ全体の単一テキスト本文として扱う。SQL 実装では本文ブロブを `dtb_tradelaw.id = 1` の `description` 列に格納する。

## Validation

本文は非空で、上限は防御的に MySQL TEXT 相当の 65535 文字とする。

## Fake / SQL Behavior

`FakeTradeLawStorage` はインストーラ既定本文をシードする。内容は販売業者、所在地、連絡先の3行である。

`SqlTradeLawStorage` は単一キャリア行が存在しない場合、Fake と同じインストーラ既定本文へフォールバックする。これにより Fake と SQL のハイパーメディア契約は同形になる。

## Persistence Behavior

`TradeLawUpdated` の冪等判定は `get()->body !== newBody` のバイト単位比較である。改行やコロンを含む本文も、単一列格納でロスレスに往復する必要がある。

## Future Scope

Phase 2 で項目ごとの行へ分割する予定である。その時点で `tradeLawName`、`sortNo`、`displayOrderScreen` が独立して投影される。
