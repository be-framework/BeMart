---
layout: default
title: "BeMart 全リンククリック監査レポート"
---

# BeMart 全リンククリック監査レポート

- 実施日: 2026-05-23 (Asia/Tokyo)
- 対象: `http://127.0.0.1:8080`
- 方法: 画面から収集したサイト内 `<a href>` を、ブラウザクリック相当の GET 遷移としてクロール。管理画面は `test-admin / admin-test-password-2026` でログイン後に実施。
- 除外: 外部リンク、`mailto:`/`tel:`/`javascript:`、ログアウト/削除/有効化/無効化/コピー/インストール等の状態変更が疑われるリンク。
- 判定: HTTP 404 / 501 / 401 / 5xx / raw Fatal / Unbound を問題として記録。

> 注: この監査は全リンククリック時点のスナップショットです。後続の本線復帰実装により、当時501だった
> `admin_order_edit` と `admin_customer_edit` は現在 `EccubeRouteMap` で実HTTP導線へ接続済みです。
> 最新のroute分類は `docs/html-screen-migration-matrix.md` を参照してください。

## 結論

- クリック相当で到達したURL数: **81**
- 404 Not Found: **0**
- 404以外の問題(401/501/5xx/Fatal等): **14**
- raw Fatal / Unbound: **0**
- 結論: 今回クロールした `<a href>` のGETリンクでは **404は出ませんでした**。ただし未実装501と未ログイン401は残っています。

## ステータス集計

| コンテキスト | 訪問URL数 | ステータス集計 |
|---|---:|---|
| `storefront-anonymous` | 26 | 200: 25, 401: 1 |
| `admin-authenticated` | 55 | 200: 42, 401: 1, 501: 12 |

## 問題リンク一覧

| 種別 | Status | URL | 最終URL | 発見元 | 備考 |
|---|---:|---|---|---|---|
| `storefront-anonymous` | 401 | `http://127.0.0.1:8080/mypage/favorite-list` | `http://127.0.0.1:8080/mypage/favorite-list` | `http://127.0.0.1:8080/` | 401認証  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_order` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_order` | `http://127.0.0.1:8080/admin/order-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_shipping` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_shipping` | `http://127.0.0.1:8080/admin/order-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_edit&id=past0000000000000000000000000001` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_edit&id=past0000000000000000000000000001` | `http://127.0.0.1:8080/admin/order-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_pdf?ids[]=past0000000000000000000000000001` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_pdf?ids[]=past0000000000000000000000000001` | `http://127.0.0.1:8080/admin/order-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_export` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_export` | `http://127.0.0.1:8080/admin/customer-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=0123456789abcdef0123456789abcdef` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=0123456789abcdef0123456789abcdef` | `http://127.0.0.1:8080/admin/customer-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=fedcba9876543210fedcba9876543210` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=fedcba9876543210fedcba9876543210` | `http://127.0.0.1:8080/admin/customer-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=aaaaaaaa00000000bbbbbbbb11111111` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=aaaaaaaa00000000bbbbbbbb11111111` | `http://127.0.0.1:8080/admin/customer-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=10000000aaaa1111bbbb2222cccc3333` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=10000000aaaa1111bbbb2222cccc3333` | `http://127.0.0.1:8080/admin/customer-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=20000000dddd2222eeee3333ffff4444` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=20000000dddd2222eeee3333ffff4444` | `http://127.0.0.1:8080/admin/customer-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-pc` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-pc` | `http://127.0.0.1:8080/admin/template/template-list` | 501未実装  |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-sp` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-sp` | `http://127.0.0.1:8080/admin/template/template-list` | 501未実装  |
| `admin-authenticated` | 401 | `http://127.0.0.1:8080/mypage/favorite-list` | `http://127.0.0.1:8080/mypage/favorite-list` | `http://127.0.0.1:8080/` | 401認証  |

### 後続修正で解消済み

上表のうち、HTML画面である以下は本線復帰実装で解消しました。

| 旧問題 | 現在の導線 | 現在の扱い |
|---|---|---|
| `__not-implemented?route=admin_order_edit&id=...` | `/admin/order?orderNo=...` | 受注編集HTML画面として接続済み |
| `__not-implemented?route=admin_customer_edit&id=...` | `/admin/customer?customerId=...` | 会員編集HTML画面として接続済み |

CSV/PDF/export/delete/plugin install等は非画面アクションとして残し、リンク非表示ではなく安全な未対応説明へ流します。

## 会員ログインPOST後の遷移確認

前回検出した `Location: /mypage/{customerId}` による404は修正済みです。現在はログイン成功時に `303 Location: /mypage?customerId=...` を返し、同じブラウザセッションのCookieで `/mypage?customerId=...` が200表示されます。`customerId` クエリは遷移先を説明する補助情報で、認証自体はPHPセッションの `customer_id` を使います。

| 操作 | 結果 | 最終URL | 備考 |
|---|---:|---|---|
| 会員ログインPOST `login-test@example.com / login-test-password-2026` | 200 | `http://127.0.0.1:8080/mypage?customerId=10000000aaaa1111bbbb2222cccc3333` | 404は解消。旧形式 `/mypage/10000000aaaa1111bbbb2222cccc3333` も互換的に `/mypage` Resourceへ正規化。 |

## 状態変更の可能性があるためクリック対象外にしたリンク

| コンテキスト | 発見元 | href | 解決URL | 理由 |
|---|---|---|---|---|
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=sample-001` | `http://127.0.0.1:8080/admin/product-copy?productCode=sample-001` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=sample-002` | `http://127.0.0.1:8080/admin/product-copy?productCode=sample-002` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=admin-active-001` | `http://127.0.0.1:8080/admin/product-copy?productCode=admin-active-001` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=admin-hidden-001` | `http://127.0.0.1:8080/admin/product-copy?productCode=admin-hidden-001` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=admin-withdrawn-001` | `http://127.0.0.1:8080/admin/product-copy?productCode=admin-withdrawn-001` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=api-persist-20260522-001` | `http://127.0.0.1:8080/admin/product-copy?productCode=api-persist-20260522-001` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `/admin/product-copy?productCode=ui-create-20260522-001` | `http://127.0.0.1:8080/admin/product-copy?productCode=ui-create-20260522-001` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `/__not-implemented?route=admin_customer_delete&id=0123456789abcdef0123456789abcdef` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_delete&id=0123456789abcdef0123456789abcdef` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `/__not-implemented?route=admin_customer_delete&id=fedcba9876543210fedcba9876543210` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_delete&id=fedcba9876543210fedcba9876543210` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `/__not-implemented?route=admin_customer_delete&id=aaaaaaaa00000000bbbbbbbb11111111` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_delete&id=aaaaaaaa00000000bbbbbbbb11111111` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `/__not-implemented?route=admin_customer_delete&id=10000000aaaa1111bbbb2222cccc3333` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_delete&id=10000000aaaa1111bbbb2222cccc3333` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `/__not-implemented?route=admin_customer_delete&id=20000000dddd2222eeee3333ffff4444` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_delete&id=20000000dddd2222eeee3333ffff4444` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `/__not-implemented?route=admin_store_plugin_install` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_plugin_install` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `/admin/plugin-enable?code=Sample%2FDisabledPlugin` | `http://127.0.0.1:8080/admin/plugin-enable?code=Sample%2FDisabledPlugin` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `/admin/plugin-disable?code=Sample%2FSamplePlugin` | `http://127.0.0.1:8080/admin/plugin-disable?code=Sample%2FSamplePlugin` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-list` | `/__not-implemented?route=admin_store_template_delete&id=tp-default-pc` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_delete&id=tp-default-pc` | 破壊的/状態変更の可能性があるためGETクリック対象外 |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-list` | `/__not-implemented?route=admin_store_template_delete&id=tp-default-sp` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_delete&id=tp-default-sp` | 破壊的/状態変更の可能性があるためGETクリック対象外 |

## 外部リンク/非HTTPリンク

| コンテキスト | 発見元 | href | 解決URL | 理由 |
|---|---|---|---|---|
| `storefront-anonymous` | `http://127.0.0.1:8080/entry` | `https://www.post.japanpost.jp/zipcode/` | `https://www.post.japanpost.jp/zipcode/` | 外部リンク |
| `storefront-anonymous` | `http://127.0.0.1:8080/shopping/non-member` | `https://www.post.japanpost.jp/zipcode/` | `https://www.post.japanpost.jp/zipcode/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/index` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/index` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/index` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/index` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product-list` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product/new` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product/new` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product/new` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product/new` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-001` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-001` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-001` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-001` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=api-persist-20260522-001` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=api-persist-20260522-001` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=api-persist-20260522-001` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=api-persist-20260522-001` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/order-list` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/order-list` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/order-list` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/order-list` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/order-list` | `javascript:;` | `javascript:;` | 非HTTPリンク(javascript) |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/customer-list` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/csv-config` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/csv-config` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/csv-config` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/csv-config` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/plugin-list` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-list` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-list` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-list` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-list` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-add` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-add` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-add` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/template/template-add` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-002` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-002` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-002` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=sample-002` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-active-001` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-active-001` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-active-001` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-active-001` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-hidden-001` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-hidden-001` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-hidden-001` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-hidden-001` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-withdrawn-001` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-withdrawn-001` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-withdrawn-001` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=admin-withdrawn-001` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=ui-create-20260522-001` | `https://www.ec-cube.net/` | `https://www.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=ui-create-20260522-001` | `https://xoo.ps/eccube/` | `https://xoo.ps/eccube/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=ui-create-20260522-001` | `https://doc4.ec-cube.net/` | `https://doc4.ec-cube.net/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/admin/product?productCode=ui-create-20260522-001` | `https://www.ec-cube.net/product/` | `https://www.ec-cube.net/product/` | 外部リンク |
| `admin-authenticated` | `http://127.0.0.1:8080/entry` | `https://www.post.japanpost.jp/zipcode/` | `https://www.post.japanpost.jp/zipcode/` | 外部リンク |

## 全クリック相当URL一覧

| コンテキスト | Status | URL | 最終URL | Title | 発見元 |
|---|---:|---|---|---|---|
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/` | `http://127.0.0.1:8080/` | BeMart | `http://127.0.0.1:8080/admin/index` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=0123456789abcdef0123456789abcdef` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=0123456789abcdef0123456789abcdef` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/customer-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=10000000aaaa1111bbbb2222cccc3333` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=10000000aaaa1111bbbb2222cccc3333` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/customer-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=20000000dddd2222eeee3333ffff4444` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=20000000dddd2222eeee3333ffff4444` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/customer-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=aaaaaaaa00000000bbbbbbbb11111111` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=aaaaaaaa00000000bbbbbbbb11111111` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/customer-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=fedcba9876543210fedcba9876543210` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_edit&id=fedcba9876543210fedcba9876543210` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/customer-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_export` | `http://127.0.0.1:8080/__not-implemented?route=admin_customer_export` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/customer-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_edit&id=past0000000000000000000000000001` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_edit&id=past0000000000000000000000000001` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/order-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_order` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_order` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/order-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_pdf?ids[]=past0000000000000000000000000001` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_pdf?ids[]=past0000000000000000000000000001` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/order-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_shipping` | `http://127.0.0.1:8080/__not-implemented?route=admin_order_export_shipping` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/order-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-pc` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-pc` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/template/template-list` |
| `admin-authenticated` | 501 | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-sp` | `http://127.0.0.1:8080/__not-implemented?route=admin_store_template_download&id=tp-default-sp` | 501 Not Implemented - BeMart | `http://127.0.0.1:8080/admin/template/template-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/csv-config` | `http://127.0.0.1:8080/admin/csv-config` | CSV出力項目設定 店舗設定 - BeMart | `http://127.0.0.1:8080/admin/index` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/customer-list` | `http://127.0.0.1:8080/admin/customer-list` | 会員一覧 会員管理 - BeMart | `http://127.0.0.1:8080/admin/index` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/index` | `http://127.0.0.1:8080/admin/index` | ホーム - BeMart | `(seed)` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/order-list` | `http://127.0.0.1:8080/admin/order-list` | 受注一覧 受注管理 - BeMart | `http://127.0.0.1:8080/admin/index` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/plugin-list` | `http://127.0.0.1:8080/admin/plugin-list` | インストールプラグイン一覧 オーナーズストア - BeMart | `http://127.0.0.1:8080/admin/index` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product-csv` | `http://127.0.0.1:8080/admin/product-csv` |  | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product-list` | `http://127.0.0.1:8080/admin/product-list` | 商品一覧 商品管理 - BeMart | `http://127.0.0.1:8080/admin/index` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product/new` | `http://127.0.0.1:8080/admin/product/new` | 商品登録 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=admin-active-001` | `http://127.0.0.1:8080/admin/product?productCode=admin-active-001` | 管理画面用 商品A 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=admin-hidden-001` | `http://127.0.0.1:8080/admin/product?productCode=admin-hidden-001` | 管理画面用 非公開商品B 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=admin-withdrawn-001` | `http://127.0.0.1:8080/admin/product?productCode=admin-withdrawn-001` | 管理画面用 廃止商品C 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=api-persist-20260522-001` | `http://127.0.0.1:8080/admin/product?productCode=api-persist-20260522-001` | 彩のジェラートセット 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=sample-001` | `http://127.0.0.1:8080/admin/product?productCode=sample-001` | サンプル商品 A 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=sample-002` | `http://127.0.0.1:8080/admin/product?productCode=sample-002` | Sample Product B 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/product?productCode=ui-create-20260522-001` | `http://127.0.0.1:8080/admin/product?productCode=ui-create-20260522-001` | UI商品登録テスト 商品管理 - BeMart | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/template/template-add` | `http://127.0.0.1:8080/admin/template/template-add` | テンプレートのアップロード オーナーズストア - BeMart | `http://127.0.0.1:8080/admin/template/template-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/template/template-list` | `http://127.0.0.1:8080/admin/template/template-list` | テンプレート一覧 オーナーズストア - BeMart | `(seed)` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/admin/two-factor-auth` | `http://127.0.0.1:8080/admin/two-factor-auth` | 2段階認証 - BeMart | `(seed)` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/cart` | `http://127.0.0.1:8080/cart` | BeMart / ショッピングカート | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/contact` | `http://127.0.0.1:8080/contact` | BeMart / お問い合わせ | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/entry` | `http://127.0.0.1:8080/entry` | BeMart / 新規会員登録 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/forgot-password` | `http://127.0.0.1:8080/forgot-password` | BeMart / パスワードの再発行 | `http://127.0.0.1:8080/login` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/help/about` | `http://127.0.0.1:8080/help/about` | BeMart / 当サイトについて | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/help/agreement` | `http://127.0.0.1:8080/help/agreement` | BeMart / 利用規約 | `http://127.0.0.1:8080/entry` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/help/privacy` | `http://127.0.0.1:8080/help/privacy` | BeMart / プライバシーポリシー | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/help/tradelaw` | `http://127.0.0.1:8080/help/tradelaw` | BeMart / 特定商取引法に基づく表記 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/login` | `http://127.0.0.1:8080/login` | BeMart / ログイン | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 401 | `http://127.0.0.1:8080/mypage/favorite-list` | `http://127.0.0.1:8080/mypage/favorite-list` | 401 Unauthorized - BeMart | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=admin-active-001` | `http://127.0.0.1:8080/product?productCode=admin-active-001` | BeMart / 管理画面用 商品A | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=admin-hidden-001` | `http://127.0.0.1:8080/product?productCode=admin-hidden-001` | BeMart / 管理画面用 非公開商品B | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=admin-withdrawn-001` | `http://127.0.0.1:8080/product?productCode=admin-withdrawn-001` | BeMart / 管理画面用 廃止商品C | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=api-persist-20260522-001` | `http://127.0.0.1:8080/product?productCode=api-persist-20260522-001` | BeMart / 彩のジェラートセット | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=sample-001` | `http://127.0.0.1:8080/product?productCode=sample-001` | BeMart / サンプル商品 A | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=sample-002` | `http://127.0.0.1:8080/product?productCode=sample-002` | BeMart / Sample Product B | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/product?productCode=ui-create-20260522-001` | `http://127.0.0.1:8080/product?productCode=ui-create-20260522-001` | BeMart / UI商品登録テスト | `http://127.0.0.1:8080/admin/product-list` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list` | `http://127.0.0.1:8080/products/list` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list?category_id=1` | `http://127.0.0.1:8080/products/list?category_id=1` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list?category_id=2` | `http://127.0.0.1:8080/products/list?category_id=2` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list?category_id=3` | `http://127.0.0.1:8080/products/list?category_id=3` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list?category_id=4` | `http://127.0.0.1:8080/products/list?category_id=4` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list?category_id=5` | `http://127.0.0.1:8080/products/list?category_id=5` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `admin-authenticated` | 200 | `http://127.0.0.1:8080/products/list?category_id=6` | `http://127.0.0.1:8080/products/list?category_id=6` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/` | `http://127.0.0.1:8080/` | BeMart | `(seed)` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/cart` | `http://127.0.0.1:8080/cart` | BeMart / ショッピングカート | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/contact` | `http://127.0.0.1:8080/contact` | BeMart / お問い合わせ | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/entry` | `http://127.0.0.1:8080/entry` | BeMart / 新規会員登録 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/forgot-password` | `http://127.0.0.1:8080/forgot-password` | BeMart / パスワードの再発行 | `http://127.0.0.1:8080/login` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/help/about` | `http://127.0.0.1:8080/help/about` | BeMart / 当サイトについて | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/help/agreement` | `http://127.0.0.1:8080/help/agreement` | BeMart / 利用規約 | `http://127.0.0.1:8080/entry` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/help/privacy` | `http://127.0.0.1:8080/help/privacy` | BeMart / プライバシーポリシー | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/help/tradelaw` | `http://127.0.0.1:8080/help/tradelaw` | BeMart / 特定商取引法に基づく表記 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/login` | `http://127.0.0.1:8080/login` | BeMart / ログイン | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 401 | `http://127.0.0.1:8080/mypage/favorite-list` | `http://127.0.0.1:8080/mypage/favorite-list` | 401 Unauthorized - BeMart | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/product?productCode=admin-active-001` | `http://127.0.0.1:8080/product?productCode=admin-active-001` | BeMart / 管理画面用 商品A | `http://127.0.0.1:8080/products/list` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/product?productCode=api-persist-20260522-001` | `http://127.0.0.1:8080/product?productCode=api-persist-20260522-001` | BeMart / 彩のジェラートセット | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/product?productCode=sample-001` | `http://127.0.0.1:8080/product?productCode=sample-001` | BeMart / サンプル商品 A | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/product?productCode=sample-002` | `http://127.0.0.1:8080/product?productCode=sample-002` | BeMart / Sample Product B | `http://127.0.0.1:8080/products/list` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/product?productCode=ui-create-20260522-001` | `http://127.0.0.1:8080/product?productCode=ui-create-20260522-001` | BeMart / UI商品登録テスト | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list` | `http://127.0.0.1:8080/products/list` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list?category_id=1` | `http://127.0.0.1:8080/products/list?category_id=1` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list?category_id=2` | `http://127.0.0.1:8080/products/list?category_id=2` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list?category_id=3` | `http://127.0.0.1:8080/products/list?category_id=3` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list?category_id=4` | `http://127.0.0.1:8080/products/list?category_id=4` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list?category_id=5` | `http://127.0.0.1:8080/products/list?category_id=5` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/products/list?category_id=6` | `http://127.0.0.1:8080/products/list?category_id=6` | BeMart / 商品一覧 | `http://127.0.0.1:8080/` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/shopping/non-member` | `http://127.0.0.1:8080/shopping/non-member` | BeMart / お客様情報の入力 | `http://127.0.0.1:8080/shopping?cart_key=session-prefix-1_1` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/shopping?cart_key=session-prefix-1_1` | `http://127.0.0.1:8080/shopping/login` | BeMart / ログイン | `http://127.0.0.1:8080/cart` |
| `storefront-anonymous` | 200 | `http://127.0.0.1:8080/shopping?cart_key=session-prefix-1_2` | `http://127.0.0.1:8080/shopping/login` | BeMart / ログイン | `http://127.0.0.1:8080/cart` |

## 次に直すべきこと

1. 未ログインの `/mypage*` は401エラー画面ではなく、EC-CUBE相当のログイン誘導へ303/表示誘導する。今回検出: `/mypage/favorite-list`。
2. 管理画面の未実装501リンクを、画面実装または安全な「準備中」画面に分類する。優先: `admin_order_edit`, `admin_customer_edit`, CSV/PDFエクスポート、テンプレートダウンロード。
3. 状態変更系リンク（コピー/削除/プラグイン有効化など）は、GETクリックではなく専用のPOST/DELETE監査で確認する。
4. この監査を PHPUnit の `LinkAuditTest` として固定し、404/500/Fatalが再発したら落ちるようにする。
