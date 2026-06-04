---
layout: default
title: "AdminCustomerEditPage — 管理会員編集画面"
---

# AdminCustomerEditPage — 管理会員編集画面

- ALPS ID: `AdminCustomerEditPage`
- EC-CUBE route: `admin_customer_edit`
- EC-CUBE template: `Admin/Customer/edit.twig`
- BeMart 現状: `admin_customer_edit` は `/admin/customer?customerId=...` に接続済み。従来の `email` 暫定指定も後方互換として受け付ける。

## 画面状態として表す範囲

`Customer` を管理者が確認・編集する画面状態です。会員エンティティ単体ではなく、管理者に必要な周辺文脈を束ねます。

- 会員基本情報、連絡先、住所、性別、職業、生年月日
- 会員ステータス、ポイント、購入回数、購入金額
- 購入履歴、お気に入り、配送先一覧への導線
- 削除、CSV、仮会員メール再送などの管理操作

## 実装済み

実装順は **Fake → スキーマ → SQL → Resource/Form → HTML/Browser** を維持しています。

1. 既存 Fake/SQL のCustomer query bodyを利用。
2. `GetAdminCustomerInput` はBecoming内部で `selector` / `selectorType` に統一し、Resourceが `customerId` と legacy `email` を正規化。
3. `CustomerList` の編集リンクを `email` ではなく EC-CUBE相当の `id` / `customerId` 指定へ変更。
4. `admin_customer_edit` はRouteMapで `id` → `customerId` aliasを持つ。
5. `AdminCustomerResourceTest` で `customerId` 指定の取得を検証。

## 残差

管理画面からの会員更新POST、配送先一覧の完全表示、購入履歴/お気に入りの深い管理操作、削除/CSV/メール再送は残差です。リンクは隠さず、安全な未対応説明または既存画面へ接続します。
