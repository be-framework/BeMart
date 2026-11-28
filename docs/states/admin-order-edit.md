---
layout: default
title: "AdminOrderEditPage — 管理受注編集画面"
---

# AdminOrderEditPage — 管理受注編集画面

- ALPS ID: `AdminOrderEditPage`
- EC-CUBE route: `admin_order_edit`
- EC-CUBE template: `Admin/Order/edit.twig`
- BeMart 現状: `/admin/order/edit`（`orderNo` なしは新規受注、ありは編集）が `var/templates/Page/Admin/Order/Edit.html.twig` で表示する。受注詳細は `Order` 状態（`/admin/order`、`Order.html.twig`）。

## 画面状態として表す範囲

`Order` を管理者が編集する画面です。受注基本情報・明細・値引き・送料・ポイントのフォームを持ちます。配送先・出荷通知・PDF/CSV 系の導線は受注詳細（`Order` 状態）にあります。

## 実装済み

実装順は **Fake → スキーマ → SQL → Resource/Form → HTML/Browser** を維持しています。

1. 既存 Fake/SQL の `FinalizedOrderEntity` / `OrderItemEntity` body を再利用。
2. `Admin\Order::onGet()` に CSRF と対応状況表示用 options を追加。
3. `var/templates/Page/Admin/Order/Edit.html.twig` を追加し、`Admin/Order/edit.twig` 相当の編集フォームを表示（`Admin\Order\Edit::onGet()`、ALPS `goOrderEdit`）。
4. 受注一覧の `goOrderEdit` から辿り、`doUpdateOrder`・`doCreateOrder`・`goOrderList` を宣言する。
5. `AdminOrderEditHtmlRenderTest` でHTML画面が表示されることを検証。

## 残差

PDF/CSV出力、出荷通知メール送信、配送先詳細編集、伝票番号更新は非画面アクションまたは別画面として残差です。リンクは隠さず、未対応のものは安全な説明に落とします。
