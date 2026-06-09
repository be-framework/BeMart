---
layout: default
title: "MypageHistory"
---

# MypageHistory

MypageHistory は customer の注文履歴詳細画面を表す。ALPS の `MypageHistory` descriptor は、EC-CUBE の `Mypage/history.twig` が描画する画面投影として定義する。

## Meaning

`MypageHistory` は個別注文の詳細である。受注ヘッダ、金額サマリ、支払方法、注文メッセージ、配送ブロック、明細行、メール配信履歴を合成して表示する。

## Screen Projection

受注ヘッダには注文番号、注文日時、注文状況、ポイント増減を含む。

金額サマリには `subtotal`、`charge`、`deliveryFeeTotal`、`discount`、`tax`、`total`、`paymentTotal` を含む。

配送ブロックは `HistoryShipping` の配列として扱う。各要素はお届け先住所、配送方法、配送日時、その配送に紐づく明細行を持つ。

メール配信履歴は `HistoryMailHistory` の配列として扱う。

## SQL Projection

明細は `dtb_order_item` を `shipping_id` でグルーピングし、各 `HistoryShipping` 配下に配置する。これにより EC-CUBE の per-Shipping 描画に一致させる。

Phase 3 では `MypageHistoryFetched` 投影として実装され、`SqlOrderQuery` が `dtb_order` / `dtb_order_item` / `dtb_shipping` / `dtb_payment` / `dtb_mail_history` を JOIN して充填する。

## Implementation References

- Phase 3
- `MypageHistoryFetched`
- `SqlOrderQuery`
