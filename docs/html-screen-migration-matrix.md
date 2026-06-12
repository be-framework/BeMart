---
layout: default
title: "HTML画面移植マトリクス（2026-05-22）"
---

# HTML画面移植マトリクス（2026-05-22）

EC-CUBE 4.3 の **routableなstorefront/admin画面 + 画面表示に必要な共有部品** を対象にした現状ベースラインです。

## ALPS画面タクソノミー更新（2026-05-23）

HTML移植の残差を「エンティティがある/ない」だけで判断すると、`Order` や `Customer` のようなリソース状態と、EC-CUBEの具体的な画面ルートが混ざってしまいます。
そのため `alps.json` に `page` / `page-admin` / `page-list` / `page-detail` / `page-edit` / `route-ec-cube` / `migration-target` タグを追加し、画面状態として追跡する粒度を明示しました。

| ALPS node | EC-CUBE route | EC-CUBE template | BeMart現状 | ドキュメント |
|---|---|---|---|---|
| `AdminOrderEditPage` | `admin_order_edit` | `Admin/Order/edit.twig` | HTTP導線あり。受注編集フォームを追加 | `docs/states/admin-order-edit.md` |
| `AdminCustomerEditPage` | `admin_customer_edit` | `Admin/Customer/edit.twig` | HTTP導線あり。EC-CUBE相当のID指定に対応 | `docs/states/admin-customer-edit.md` |

既存の `OrderList` / `CustomerList` からこれらのページ状態へリンクすることで、一覧→編集画面の移植漏れを ALPS 上で説明できるようにしています。

実装時の順序は従来どおり **Fake → スキーマ → SQL → Resource/Form → HTML/Browser** を固定します。ALPSの `page*` taxonomy は移植漏れを見える化するための分類であり、SQL先行やTwig先行の開発順へ変更するものではありません。

## 集計

| 項目 | 現状 |
|---|---:|
| `src/Resource/Page/**/*.php` | 128 |
| `var/templates/Page/**/*.html.twig` | 97 |
| Storefront page templates | 41 |
| Admin page templates | 56 |
| テンプレート内 `url()` / `path()` route名 | 142 |
| `EccubeRouteMap` で実HTTP導線へ変換 | 99 |
| 未対応alert + 501 fallback | 43 |

## 実サイト探索ベースライン（2026-05-23）

コード棚卸しとは別に、起動中の EC-CUBE `http://127.0.0.1:8081` と BeMart `http://127.0.0.1:8080` を実HTTPで探索し、フォーム/リンク/画面タイトルから機能欠落を確認しました。詳細は `docs/ec-cube-site-exploration-gaps-2026-05-23.md`。

| 対象 | 探索ページ数 | エラー | 主な発見 |
|---|---:|---|---|
| EC-CUBE storefront匿名 | 21 | 0 | 商品一覧カート投入、商品規格、favorite、問い合わせ住所項目などを確認 |
| BeMart storefront匿名 | 26 | 1 | 匿名 `/mypage/favorite-list` が401。EC-CUBE同様ログイン誘導に寄せる必要あり |
| EC-CUBE adminログイン済み | 145 | 5 | 商品規格行列、受注新規、会員新規、ファイル管理、ログ系を確認 |
| BeMart adminログイン済み | 60 | 0 | URL到達は安定。ただしProduct/Order/Customerのフォーム機能はまだ薄い |

## 主要導線ステータス

| ロール | 導線 | 状態 | 残差 / 次タスク |
|---|---|---|---|
| Storefront | トップ → 検索フォーム「全ての商品」 → `/products/list` | HTTP到達可 | カテゴリフィルタ、表示件数/並び順、一覧カート投入を追加。EC-CUBE pager partial忠実度は残差 |
| Storefront | 商品一覧 → `/product?productCode=...` | HTTP到達可 | 画像・カテゴリ・タグ・規格名をFake/SQL bodyに追加。SEO JSON-LDは残差 |
| Storefront | 商品詳細 → カート投入 → `/cart` | 修正済み | CSRF hiddenを追加、成功時は303でカートへ遷移。カートの増減/削除リンクは残差 |
| Storefront | 匿名MYページ系 → ログイン誘導 | 修正済み | `/mypage`、お気に入り、配送先、会員編集は匿名時 `/login` へ303。戻り先復元は残差 |
| Admin | `/admin/login` | HTTP/Browser到達可 | ローカル比較用に `test-admin` / `admin-test-password-2026` をprefill |
| Admin | `/admin/index` | HTTP/Browser到達可 | ダッシュボードKPIは空プレースホルダ |
| Admin | `/admin/product-list` | HTTP/Browser到達可 | 商品登録ボタン追加済み |
| Admin | `/admin/product/new` | 追加済み | 初回は既存Input項目に限定（画像/カテゴリ/タグ/規格/税/商品クラスは別タスク） |
| Admin | `/admin/product?productCode=...` | HTTP/Browser到達可 | 画像・カテゴリ・タグ・規格名の表示を追加。画像アップロード/並び替えは残差 |
| Admin | `/admin/order?orderNo=...` | HTTP到達可 | 受注ヘッダ・顧客・明細・金額編集フォームを追加。PDF/CSV/メール送信は非画面alert |
| Admin | `/admin/customer?customerId=...` | HTTP到達可 | `email` 暫定リンクから `customerId` / `id` 指定へ変更 |
| Admin | `/admin/category/category-list` | HTTP到達可 | カテゴリ一覧/編集のHTML画面を追加 |
| 共通 | 未定義Resource / MethodNotAllowed / 必須param不足 | 修正済み | raw Fatal + HTTP 200は禁止。HTMLは専用エラー、JSONはHTTPコード付き |
| 共通 | EC-CUBE route名リンク | 修正中 | 142 routeのうち99は実導線、43は非画面アクション中心の未対応alert + 501 fallback。 |

## Web+DB完成判定との差分（2026-06-10）

このマトリクスはHTML画面移植とroute到達のベースラインであり、Web+DBの完成判定そのものではない。最新の完成判定は `docs/web-e2e/20260610-web-db-all-routes-report.md` と `docs/web-e2e/feature-implementation-matrix.md` を正とする。

20260610 run では、画面表示は多くのrouteで安定しているが、Admin の unsafe CRUD/update は実フォーム・実リンク・`Location`・ALPS rel から副作用を実行し、readback できるところまで確認できていないため fail として残した。画面があることと、業務状態をWeb/HTTP affordanceで変更できることは分けて扱う。

## Route変換マトリクス

下表はテンプレート内の `url()` / `path()` route名に対して、BeMartがブラウザへ出すURLと状態を示します。未対応routeはリンクを隠さず、JS有効時はalertで説明し、JS無効時は安全な501ページへ遷移します。

| EC-CUBE route | BeMart URL | 状態 |
|---|---|---|
| `admin_content_block` | `/admin/block/block-list` | HTTP導線あり |
| `admin_content_block_delete` | `/__not-implemented?route=admin_content_block_delete` | 未対応alert + 501 fallback |
| `admin_content_block_edit` | `/admin/block/block` | HTTP導線あり |
| `admin_content_block_new` | `/admin/block/block` | HTTP導線あり |
| `admin_content_cache` | `/admin/content/cache` | HTTP導線あり |
| `admin_content_css` | `/admin/content/css` | HTTP導線あり |
| `admin_content_js` | `/admin/content/js` | HTTP導線あり |
| `admin_content_layout` | `/admin/layout/layout-list` | HTTP導線あり |
| `admin_content_layout_edit` | `/admin/layout/layout` | HTTP導線あり |
| `admin_content_layout_new` | `/admin/layout/layout` | HTTP導線あり |
| `admin_content_maintenance` | `/admin/content/maintenance` | HTTP導線あり |
| `admin_content_news` | `/admin/news/news-list` | HTTP導線あり |
| `admin_content_news_delete` | `/__not-implemented?route=admin_content_news_delete` | 未対応alert + 501 fallback |
| `admin_content_news_edit` | `/admin/news/news` | HTTP導線あり |
| `admin_content_news_new` | `/admin/news/news` | HTTP導線あり |
| `admin_content_page` | `/admin/page/page-list` | HTTP導線あり |
| `admin_content_page_delete` | `/__not-implemented?route=admin_content_page_delete` | 未対応alert + 501 fallback |
| `admin_content_page_edit` | `/admin/page/page` | HTTP導線あり |
| `admin_content_page_new` | `/admin/page/page` | HTTP導線あり |
| `admin_customer` | `/admin/customer-list` | HTTP導線あり |
| `admin_customer_delete` | `/__not-implemented?route=admin_customer_delete` | 未対応alert + 501 fallback |
| `admin_customer_delivery_new` | `/admin/customer-delivery-edit` | HTTP導線あり |
| `admin_customer_edit` | `/admin/customer` | HTTP導線あり |
| `admin_customer_export` | `/__not-implemented?route=admin_customer_export` | 未対応alert + 501 fallback |
| `admin_homepage` | `/admin/index` | HTTP導線あり |
| `admin_homepage_customer` | `/admin/customer-list` | HTTP導線あり |
| `admin_homepage_nonstock` | `/admin/product-list` | HTTP導線あり |
| `admin_homepage_sale` | `/admin/order-list` | HTTP導線あり |
| `admin_login` | `/admin/login` | HTTP導線あり |
| `admin_order` | `/admin/order-list` | HTTP導線あり |
| `admin_order_bulk_delete` | `/__not-implemented?route=admin_order_bulk_delete` | 未対応alert + 501 fallback |
| `admin_order_edit` | `/admin/order` | HTTP導線あり |
| `admin_order_export_order` | `/__not-implemented?route=admin_order_export_order` | 未対応alert + 501 fallback |
| `admin_order_export_pdf` | `/__not-implemented?route=admin_order_export_pdf` | 未対応alert + 501 fallback |
| `admin_order_export_shipping` | `/__not-implemented?route=admin_order_export_shipping` | 未対応alert + 501 fallback |
| `admin_product` | `/admin/product-list` | HTTP導線あり |
| `admin_product_bulk_product_status` | `/admin/product-bulk-status` | HTTP導線あり |
| `admin_product_category` | `/admin/category/category-list` | HTTP導線あり |
| `admin_product_category_edit` | `/admin/category/category` | HTTP導線あり |
| `admin_product_class_category` | `/admin/class-category/class-category-list` | HTTP導線あり |
| `admin_product_class_category_delete` | `/__not-implemented?route=admin_product_class_category_delete` | 未対応alert + 501 fallback |
| `admin_product_class_category_edit` | `/__not-implemented?route=admin_product_class_category_edit` | 未対応alert + 501 fallback |
| `admin_product_class_category_export` | `/__not-implemented?route=admin_product_class_category_export` | 未対応alert + 501 fallback |
| `admin_product_class_category_sort_no_move` | `/__not-implemented?route=admin_product_class_category_sort_no_move` | 未対応alert + 501 fallback |
| `admin_product_class_category_visibility` | `/__not-implemented?route=admin_product_class_category_visibility` | 未対応alert + 501 fallback |
| `admin_product_class_name` | `/admin/class-name/class-name-list` | HTTP導線あり |
| `admin_product_class_name_delete` | `/__not-implemented?route=admin_product_class_name_delete` | 未対応alert + 501 fallback |
| `admin_product_class_name_export` | `/__not-implemented?route=admin_product_class_name_export` | 未対応alert + 501 fallback |
| `admin_product_class_name_sort_no_move` | `/__not-implemented?route=admin_product_class_name_sort_no_move` | 未対応alert + 501 fallback |
| `admin_product_export` | `/admin/product-csv` | HTTP導線あり |
| `admin_product_product_copy` | `/admin/product-copy` | HTTP導線あり |
| `admin_product_product_delete` | `/admin/product` | HTTP導線あり |
| `admin_product_product_edit` | `/admin/product` | HTTP導線あり |
| `admin_product_product_new` | `/admin/product/new` | HTTP導線あり |
| `admin_product_tag` | `/admin/tag/tag-list` | HTTP導線あり |
| `admin_product_tag_delete` | `/__not-implemented?route=admin_product_tag_delete` | 未対応alert + 501 fallback |
| `admin_product_tag_sort_no_move` | `/__not-implemented?route=admin_product_tag_sort_no_move` | 未対応alert + 501 fallback |
| `admin_setting_shop` | `/admin/base-info` | HTTP導線あり |
| `admin_setting_shop_calendar` | `/admin/calendar` | HTTP導線あり |
| `admin_setting_shop_calendar_delete` | `/__not-implemented?route=admin_setting_shop_calendar_delete` | 未対応alert + 501 fallback |
| `admin_setting_shop_calendar_new` | `/__not-implemented?route=admin_setting_shop_calendar_new` | 未対応alert + 501 fallback |
| `admin_setting_shop_csv` | `/admin/csv-config` | HTTP導線あり |
| `admin_setting_shop_delivery` | `/admin/delivery/delivery-list` | HTTP導線あり |
| `admin_setting_shop_delivery_delete` | `/__not-implemented?route=admin_setting_shop_delivery_delete` | 未対応alert + 501 fallback |
| `admin_setting_shop_delivery_edit` | `/admin/delivery/delivery` | HTTP導線あり |
| `admin_setting_shop_delivery_new` | `/admin/delivery/delivery` | HTTP導線あり |
| `admin_setting_shop_delivery_sort_no_move` | `/__not-implemented?route=admin_setting_shop_delivery_sort_no_move` | 未対応alert + 501 fallback |
| `admin_setting_shop_delivery_visibility` | `/__not-implemented?route=admin_setting_shop_delivery_visibility` | 未対応alert + 501 fallback |
| `admin_setting_shop_mail` | `/admin/mail-template` | HTTP導線あり |
| `admin_setting_shop_mail_delete` | `/__not-implemented?route=admin_setting_shop_mail_delete` | 未対応alert + 501 fallback |
| `admin_setting_shop_order_status` | `/admin/order-status` | HTTP導線あり |
| `admin_setting_shop_payment` | `/admin/payment/payment-list` | HTTP導線あり |
| `admin_setting_shop_payment_delete` | `/__not-implemented?route=admin_setting_shop_payment_delete` | 未対応alert + 501 fallback |
| `admin_setting_shop_payment_edit` | `/admin/payment/payment` | HTTP導線あり |
| `admin_setting_shop_payment_new` | `/admin/payment/payment` | HTTP導線あり |
| `admin_setting_shop_payment_sort_no_move` | `/__not-implemented?route=admin_setting_shop_payment_sort_no_move` | 未対応alert + 501 fallback |
| `admin_setting_shop_payment_visible` | `/__not-implemented?route=admin_setting_shop_payment_visible` | 未対応alert + 501 fallback |
| `admin_setting_shop_tax` | `/admin/tax-rule/tax-rule-list` | HTTP導線あり |
| `admin_setting_shop_tax_delete` | `/__not-implemented?route=admin_setting_shop_tax_delete` | 未対応alert + 501 fallback |
| `admin_setting_shop_tax_new` | `/__not-implemented?route=admin_setting_shop_tax_new` | 未対応alert + 501 fallback |
| `admin_setting_shop_tradelaw` | `/admin/trade-law` | HTTP導線あり |
| `admin_setting_system_authority` | `/admin/authority-role` | HTTP導線あり |
| `admin_setting_system_masterdata` | `/admin/master-data` | HTTP導線あり |
| `admin_setting_system_masterdata_edit` | `/admin/master-data` | HTTP導線あり |
| `admin_setting_system_member` | `/admin/member-list` | HTTP導線あり |
| `admin_setting_system_member_delete` | `/__not-implemented?route=admin_setting_system_member_delete` | 未対応alert + 501 fallback |
| `admin_setting_system_member_down` | `/__not-implemented?route=admin_setting_system_member_down` | 未対応alert + 501 fallback |
| `admin_setting_system_member_edit` | `/admin/member` | HTTP導線あり |
| `admin_setting_system_member_new` | `/admin/member` | HTTP導線あり |
| `admin_setting_system_member_up` | `/__not-implemented?route=admin_setting_system_member_up` | 未対応alert + 501 fallback |
| `admin_setting_system_security` | `/admin/security` | HTTP導線あり |
| `admin_setting_system_system_phpinfo` | `/admin/system` | HTTP導線あり |
| `admin_shipping_notify_mail` | `/__not-implemented?route=admin_shipping_notify_mail` | 未対応alert + 501 fallback |
| `admin_shipping_preview_notify_mail` | `/__not-implemented?route=admin_shipping_preview_notify_mail` | 未対応alert + 501 fallback |
| `admin_shipping_update_order_status` | `/__not-implemented?route=admin_shipping_update_order_status` | 未対応alert + 501 fallback |
| `admin_shipping_update_tracking_number` | `/__not-implemented?route=admin_shipping_update_tracking_number` | 未対応alert + 501 fallback |
| `admin_store_plugin_disable` | `/admin/plugin-disable` | HTTP導線あり |
| `admin_store_plugin_enable` | `/admin/plugin-enable` | HTTP導線あり |
| `admin_store_plugin_install` | `/__not-implemented?route=admin_store_plugin_install` | 未対応alert + 501 fallback |
| `admin_store_plugin_owners_search_page` | `/admin/plugin-list` | HTTP導線あり |
| `admin_store_plugin_uninstall` | `/__not-implemented?route=admin_store_plugin_uninstall` | 未対応alert + 501 fallback |
| `admin_store_template` | `/admin/template/template-list` | HTTP導線あり |
| `admin_store_template_delete` | `/__not-implemented?route=admin_store_template_delete` | 未対応alert + 501 fallback |
| `admin_store_template_download` | `/__not-implemented?route=admin_store_template_download` | 未対応alert + 501 fallback |
| `admin_store_template_install` | `/admin/template/template-add` | HTTP導線あり |
| `admin_two_factor_auth` | `/admin/two-factor-auth` | HTTP導線あり |
| `admin_two_factor_auth_set` | `/admin/two-factor-auth-set` | HTTP導線あり |
| `block_cart` | `/cart` | HTTP導線あり |
| `cart` | `/cart` | HTTP導線あり |
| `cart_buystep` | `/shopping` | HTTP導線あり |
| `cart_handle_item` | `/cart/item` | HTTP導線あり |
| `contact` | `/contact` | HTTP導線あり |
| `entry` | `/entry` | HTTP導線あり |
| `forgot` | `/forgot-password` | HTTP導線あり |
| `help_about` | `/help/about` | HTTP導線あり |
| `help_agreement` | `/help/agreement` | HTTP導線あり |
| `help_privacy` | `/help/privacy` | HTTP導線あり |
| `help_tradelaw` | `/help/tradelaw` | HTTP導線あり |
| `homepage` | `/` | HTTP導線あり |
| `mypage` | `/mypage` | HTTP導線あり |
| `mypage_change` | `/mypage/change` | HTTP導線あり |
| `mypage_delivery` | `/mypage/address-list` | HTTP導線あり |
| `mypage_delivery_delete` | `/__not-implemented?route=mypage_delivery_delete` | 未対応alert + 501 fallback |
| `mypage_delivery_edit` | `/mypage/address` | HTTP導線あり |
| `mypage_delivery_new` | `/mypage/address` | HTTP導線あり |
| `mypage_favorite` | `/mypage/favorite-list` | HTTP導線あり |
| `mypage_favorite_delete` | `/__not-implemented?route=mypage_favorite_delete` | 未対応alert + 501 fallback |
| `mypage_history` | `/mypage/history` | HTTP導線あり |
| `mypage_login` | `/login` | HTTP導線あり |
| `mypage_withdraw` | `/mypage/withdraw` | HTTP導線あり |
| `product_add_cart` | `/cart/item` | HTTP導線あり |
| `product_detail` | `/product` | HTTP導線あり |
| `product_list` | `/products/list` | HTTP導線あり |
| `shopping` | `/shopping` | HTTP導線あり |
| `shopping_checkout` | `/shopping/checkout` | HTTP導線あり |
| `shopping_confirm` | `/shopping/confirm` | HTTP導線あり |
| `shopping_login` | `/shopping/login` | HTTP導線あり |
| `shopping_nonmember` | `/shopping/non-member` | HTTP導線あり |
| `shopping_shipping` | `/shopping/shipping` | HTTP導線あり |
| `shopping_shipping_edit` | `/shopping/shipping-edit` | HTTP導線あり |
| `shopping_shipping_multiple` | `/shopping/shipping-multiple` | HTTP導線あり |
| `shopping_shipping_multiple_edit` | `/shopping/shipping-multiple-edit` | HTTP導線あり |
