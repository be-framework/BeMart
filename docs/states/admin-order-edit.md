# AdminOrderEditPage — 管理受注編集画面

- ALPS ID: `AdminOrderEditPage`
- EC-CUBE route: `admin_order_edit`
- EC-CUBE template: `Admin/Order/edit.twig`
- BeMart 現状: `admin_order_edit` は `/admin/order?orderNo=...` に接続済み。受注ヘッダ、顧客リンク、明細、金額編集フォームを HTML 画面として表示する。

## 画面状態として表す範囲

`Order` を管理者が編集するワークベンチです。単なる受注エンティティではなく、以下の関連状態を同じ画面で操作・確認します。

- 受注基本情報、購入者情報、対応状況
- 受注明細、値引き、送料、税計算、ポイント
- 配送先・出荷通知・PDF/CSV 系の周辺導線

## 実装済み

実装順は **Fake → スキーマ → SQL → Resource/Form → HTML/Browser** を維持しています。

1. 既存 Fake/SQL の `FinalizedOrderEntity` / `OrderItemEntity` body を再利用。
2. `Admin\Order::onGet()` に CSRF と対応状況表示用 options を追加。
3. `var/templates/Page/Admin/Order.html.twig` を追加し、`Admin/Order/edit.twig` 相当の主要カードを表示。
4. `admin_order_edit` は `/admin/order` へRouteMap接続済み。
5. `AdminOrderHtmlRenderTest` でHTML画面が表示され、501へ落ちないことを検証。

## 残差

PDF/CSV出力、出荷通知メール送信、配送先詳細編集、伝票番号更新は非画面アクションまたは別画面として残差です。リンクは隠さず、未対応のものは安全な説明に落とします。
