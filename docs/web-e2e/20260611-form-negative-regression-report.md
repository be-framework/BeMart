# 20260611-form-negative-regression Web+DB 全ルート検証結果

> Note: this is a limited regression run executed with `--limit=43`.
> Runner-marked failures after the limit are "not executed" rows, not new product regressions.


## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 41 / fail 143 / 対象外 2
- OpenAPI operations: pass 44 / fail 190 / 対象外 3 / total 237
- NG cases: pass 19 / fail 0 / total 19
- screenshots: `docs/web-e2e/screenshots/20260611-form-negative-regression/`
- results JSON: `docs/web-e2e/results/20260611-form-negative-regression.json`

## Scope

- 母集団は `docs/api/openapi.json` の 237 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: pass final=`http://127.0.0.1:18080/admin/index`
- 会員登録: pass final=`http://127.0.0.1:18080/entry/complete`
- 業務状態作成: pass product=`we-mq8lp0pd-mp4ume` memberOrder=`b85d472c48e5a0d61eb23151be4e7761` nonMemberOrder=`a0e3e9531e83fd99c1aeaab7069815c3`
- ✔ pass setup:admin-base-info-update final=`http://127.0.0.1:18080/admin/base-info` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-base-info-update.png`
- ✔ pass setup:admin-payment-maintenance-create final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-payment-maintenance-create.png`
- ✔ pass setup:admin-payment-maintenance-update final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-payment-maintenance-update.png`
- ✔ pass setup:admin-payment-maintenance-delete final=`http://127.0.0.1:18080/admin/payment/payment-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-payment-maintenance-delete.png`
- ✔ pass setup:admin-delivery-maintenance-create final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-delivery-maintenance-create.png`
- ✔ pass setup:admin-delivery-maintenance-update final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-delivery-maintenance-update.png`
- ✔ pass setup:admin-delivery-maintenance-delete final=`http://127.0.0.1:18080/admin/delivery/delivery-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-delivery-maintenance-delete.png`
- ✔ pass setup:admin-tax-rule-create final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-tax-rule-create.png`
- ✔ pass setup:admin-tax-rule-delete final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-tax-rule-delete.png`
- ✔ pass setup:admin-class-name-create final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-name-create.png`
- ✔ pass setup:admin-class-name-update final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-name-update.png`
- ✔ pass setup:admin-class-category-create final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-category-create.png`
- ✔ pass setup:admin-class-category-update final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-category-update.png`
- ✔ pass setup:admin-class-category-delete final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-category-delete.png`
- ✔ pass setup:admin-class-name-delete final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-name-delete.png`
- ✔ pass setup:admin-product-create final=`/admin/product?productCode=we-mq8lp0pd-mp4ume` screenshot=``
- ✔ pass setup:admin-product-readback final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8lp0pd-mp4ume` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-product-readback.png`
- ✔ pass setup:admin-product-csv-upload final=`http://127.0.0.1:18080/admin/product?productCode=we-csv-6qk9yx2t` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-product-csv-upload.png`
- ✔ pass setup:admin-product-update final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8lp0pd-mp4ume` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-product-update.png`
- ✔ pass setup:admin-product-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8lp0pd-mp4ume-copy` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-product-copy.png`
- ✔ pass setup:admin-product-bulk-status final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8lp0pd-mp4ume-copy` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-product-bulk-status.png`
- ✔ pass setup:admin-product-delete-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8lp0pd-mp4ume-copy` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-product-delete-copy.png`
- ✔ pass setup:admin-category-create final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-category-create.png`
- ✔ pass setup:admin-category-update final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-category-update.png`
- ✔ pass setup:admin-category-delete final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-category-delete.png`
- ✔ pass setup:admin-category-csv-upload final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-category-csv-upload.png`
- ✔ pass setup:admin-class-name-csv-upload final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-name-csv-upload.png`
- ✔ pass setup:admin-class-category-csv-upload final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-class-category-csv-upload.png`
- ✔ pass setup:admin-tag-create final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-tag-create.png`
- ✔ pass setup:admin-tag-delete final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-tag-delete.png`
- ✔ pass setup:admin-customer-create final=`/admin/customer?email=admin-customer-20260611-form-negative-regression%40example.test` screenshot=``
- ✔ pass setup:admin-customer-readback final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-form-negative-regression%40example.test` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-customer-readback.png`
- ✔ pass setup:admin-customer-delete final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-form-negative-regression%40example.test` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-customer-delete.png`
- ✔ pass setup:admin-logout final=`/admin/login` screenshot=``
- ✔ pass setup:storefront-product-readback final=`http://127.0.0.1:18080/product?productCode=we-mq8lp0pd-mp4ume` screenshot=`screenshots/20260611-form-negative-regression/setup/storefront-product-readback.png`
- ✔ pass setup:non-member-purchase final=`http://127.0.0.1:18080/shopping/complete?orderNo=a0e3e9531e83fd99c1aeaab7069815c3` screenshot=`screenshots/20260611-form-negative-regression/setup/non-member-purchase-complete.png`
- ✔ pass setup:customer-login final=`http://127.0.0.1:18080/mypage` screenshot=``
- ✔ pass setup:member-purchase-history-reorder final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-form-negative-regression/setup/member-reorder-cart.png`
- ✔ pass setup:admin-order-update final=`http://127.0.0.1:18080/admin/order?orderNo=b85d472c48e5a0d61eb23151be4e7761` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-update.png`
- ✔ pass setup:admin-order-status-update final=`http://127.0.0.1:18080/admin/order?orderNo=b85d472c48e5a0d61eb23151be4e7761` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-status-update.png`
- ✔ pass setup:admin-order-shipping-address-update final=`http://127.0.0.1:18080/admin/order/shipping-address?orderNo=b85d472c48e5a0d61eb23151be4e7761` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-shipping-address-update.png`
- ✔ pass setup:admin-order-tracking-number-update final=`/admin/order/tracking-number?orderNo=b85d472c48e5a0d61eb23151be4e7761&_method=put` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-tracking-number-update.png`
- ✔ pass setup:admin-order-shipping-csv-import final=`http://127.0.0.1:18080/admin/order/import-shipping` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-shipping-csv-import.png`
- ✔ pass setup:admin-order-shipping-notify-send final=`/admin/order/shipping-notify-mail` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-shipping-notify-send.png`
- ✔ pass setup:admin-order-mail-send final=`/admin/order/send-mail?orderNo=b85d472c48e5a0d61eb23151be4e7761` screenshot=`screenshots/20260611-form-negative-regression/setup/admin-order-mail-send.png`
- ✔ pass setup:cart-quantity-and-delete final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-form-negative-regression/setup/cart-quantity-and-delete.png`
- ✔ pass setup:customer-profile-favorite-address final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-form-negative-regression/setup/customer-profile-favorite-address.png`
- ✔ pass setup:customer-logout-and-relogin final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-form-negative-regression/setup/customer-logout-and-relogin.png`
- ✔ pass setup:contact-submit final=`http://127.0.0.1:18080/contact/complete?ticketId=INQ-6a29dc2b7c2587.12956888` screenshot=`screenshots/20260611-form-negative-regression/setup/contact-submit.png`
- ✔ pass setup:password-reset-request final=`` screenshot=``
- ✔ pass setup:customer-withdraw final=`http://127.0.0.1:18080/mypage/withdraw-complete` screenshot=`screenshots/20260611-form-negative-regression/setup/customer-withdraw.png`

## Known Failures

- なし

## New Failures

- 044 User 当サイトについて: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 045 User ご利用ガイド: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 046 User 利用規約: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 047 User プライバシーポリシー: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 048 User 特定商取引法表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 049 Admin 管理ログイン: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 050 Admin 管理ログアウト: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 051 Admin 管理ダッシュボード表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 052 Admin 商品一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 053 Admin 商品検索: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 054 Admin 商品新規登録: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 055 Admin 商品詳細表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 056 Admin 商品編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 057 Admin 商品削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 058 Admin 商品コピー: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 059 Admin 商品公開状態一括変更: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 060 Admin 商品CSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 061 Admin 商品CSV取込: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 062 Admin カテゴリ一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 063 Admin カテゴリ作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 064 Admin カテゴリ編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 065 Admin カテゴリ削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 066 Admin カテゴリCSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 067 Admin カテゴリCSV取込: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 068 Admin タグ一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 069 Admin タグ作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 070 Admin タグ削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 071 Admin 規格管理表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 072 Admin 規格作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 073 Admin 規格編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 074 Admin 規格削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 075 Admin 規格CSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 076 Admin 規格CSV取込: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 077 Admin 規格分類管理表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 078 Admin 規格分類作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 079 Admin 規格分類編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 080 Admin 規格分類削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 081 Admin 規格分類CSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 082 Admin 規格分類CSV取込: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 083 Admin 商品規格編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 084 Admin 会員一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 085 Admin 会員検索: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 086 Admin 会員詳細表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 087 Admin 会員作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 088 Admin 会員編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 089 Admin 会員削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 090 Admin 会員配送先編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 091 Admin 会員認証メール再送: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 092 Admin 会員CSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 093 Admin 受注一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 094 Admin 受注検索: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 095 Admin 受注詳細表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 096 Admin 受注作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 097 Admin 受注編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 098 Admin 受注削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 099 Admin 受注対応状況変更: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 100 Admin 配送先編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 101 Admin 追跡番号更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 102 Admin 出荷通知メール表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 103 Admin 出荷通知メール送信: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 104 Admin 受注メール確認: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 105 Admin 受注メール送信: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 106 Admin 受注CSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 107 Admin 出荷CSV出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 108 Admin 出荷CSV取込: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 109 Admin 受注PDF出力: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 110 Admin 基本情報表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 111 Admin 基本情報更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 112 Admin 支払方法一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 113 Admin 支払方法作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 114 Admin 支払方法編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 115 Admin 支払方法削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 116 Admin 配送方法一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 117 Admin 配送方法作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 118 Admin 配送方法編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 119 Admin 配送方法削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 120 Admin 税率設定一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 121 Admin 税率設定作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 122 Admin 税率設定削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 123 Admin 定休日カレンダー表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行

- ... 63 more failures are in the JSON.

## OpenAPI Operation Failures

- GET /action-redirect: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /action-redirect: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /forgot-complete: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /unsupported-route: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /unsupported-route: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/action-redirect: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/action-redirect: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/authority-role: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/authority-role: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/base-info: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/base-info: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/change-password: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/change-password: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/create-customer: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer-delivery-edit: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/customer-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/delete-customer: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/empty-page: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/index: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/log: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/login: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/login: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/login-history: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/logout: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/master-data: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data-edit: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/member-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/plugin: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-disable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-enable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/product: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-bulk-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-copy: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product-new: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/security: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/security: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/sort-no-move: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/system: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/toggle-visible: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/trade-law: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/trade-law: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/two-factor-auth: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/two-factor-auth: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/two-factor-auth-edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/two-factor-auth-set: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/two-factor-auth-set: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/unsupported-route: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/unsupported-route: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /entry/activate: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /help/about: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.

- ... 110 more operation failures are in the JSON.

## Negative Case Failures

- なし

## Negative Cases

- ✔ pass 会員登録 必須欠落: POST /entry, status=400, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-entry-required-missing.png`
- ✔ pass 会員登録 メール形式不正/確認不一致: POST /entry, status=400, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-entry-invalid-email-mismatch.png`
- ✔ pass 会員登録 CSRF欠落: POST /entry, status=403, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-entry-csrf-missing.png`
- ✔ pass ログイン 認証失敗: POST /login, status=401, final=`http://127.0.0.1:18080/login`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-login-wrong-credential.png`
- ✔ pass ログイン 形式不正: POST /login, status=400, final=`http://127.0.0.1:18080/login`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-login-invalid-email.png`
- ✔ pass パスワード再発行 メール形式不正: POST /forgot-password, status=403, final=`http://127.0.0.1:18080/forgot-password`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-forgot-password-invalid-email.png`
- ✔ pass パスワードリセット 不正キー: POST /reset, status=403, final=`http://127.0.0.1:18080/reset`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-reset-invalid-key.png`
- ✔ pass お問い合わせ 必須欠落: POST /contact, status=400, final=`http://127.0.0.1:18080/contact`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-contact-required-missing.png`
- ✔ pass お問い合わせ 形式不正/境界超過: POST /contact, status=400, final=`http://127.0.0.1:18080/contact`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-contact-invalid-email-long-body.png`
- ✔ pass カート投入 数量境界不正: POST /cart/item, status=403, final=`http://127.0.0.1:18080/cart/item`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-cart-item-invalid-quantity.png`
- ✔ pass 非会員購入 必須欠落: POST /shopping/non-member, status=400, final=`http://127.0.0.1:18080/shopping/non-member`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-shopping-non-member-required-missing.png`
- ✔ pass 購入確定 存在しない preOrderId: POST /shopping/checkout, status=403, final=`http://127.0.0.1:18080/shopping/checkout`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-shopping-checkout-nonexistent-preorder.png`
- ✔ pass 会員情報変更 未ログイン: POST /mypage/change, status=403, final=`http://127.0.0.1:18080/mypage/change`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-mypage-change-unauthenticated.png`
- ✔ pass お届け先編集 存在しないID/未ログイン: PUT /mypage/address, status=400, final=`http://127.0.0.1:18080/mypage/address`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-mypage-address-nonexistent-id.png`
- ✔ pass 管理ログイン 認証失敗: POST /admin/login, status=401, final=`http://127.0.0.1:18080/admin/login`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-admin-login-wrong-credential.png`
- ✔ pass 管理ログイン CSRF不一致: POST /admin/login, status=403, final=`http://127.0.0.1:18080/admin/login`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-admin-login-csrf-invalid.png`
- ✔ pass 管理2FA チャレンジなし: POST /admin/two-factor-auth, status=403, final=`http://127.0.0.1:18080/admin/two-factor-auth`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-admin-two-factor-no-challenge.png`
- ✔ pass 管理商品 未ログインPOST: POST /admin/product, status=400, final=`http://127.0.0.1:18080/admin/product`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-admin-product-unauthenticated.png`
- ✔ pass 管理CSVアップロード 未ログイン: POST /admin/product-csv, status=400, final=`http://127.0.0.1:18080/admin/product-csv`, screenshot=`screenshots/20260611-form-negative-regression/negative/ng-admin-csv-upload-unauthenticated.png`

## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
