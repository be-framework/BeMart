# 20260611-admin-class-csv-browser-regression Web+DB 全ルート検証結果

> Note: this is a limited regression run executed with `--limit=83`.
> Runner-marked failures after the limit are "not executed" rows, not new product regressions.


## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 80 / fail 104 / 対象外 2
- OpenAPI operations: pass 83 / fail 151 / 対象外 3 / total 237
- NG cases: pass 0 / fail 0 / total 0
- screenshots: `docs/web-e2e/screenshots/20260611-admin-class-csv-browser-regression/`
- results JSON: `docs/web-e2e/results/20260611-admin-class-csv-browser-regression.json`

## Scope

- 母集団は `docs/api/openapi.json` の 237 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: pass final=`http://127.0.0.1:18080/admin/index`
- 会員登録: pass final=`http://127.0.0.1:18080/entry/complete`
- 業務状態作成: pass product=`we-mq8ixjzn-ki7urc` memberOrder=`b0c3843364c4e165611281cf74859016` nonMemberOrder=`1ad1248f8ff2aa4389f67eb639905cfa`
- ✔ pass setup:admin-base-info-update final=`http://127.0.0.1:18080/admin/base-info` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-base-info-update.png`
- ✔ pass setup:admin-payment-maintenance-create final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-payment-maintenance-create.png`
- ✔ pass setup:admin-payment-maintenance-update final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-payment-maintenance-update.png`
- ✔ pass setup:admin-payment-maintenance-delete final=`http://127.0.0.1:18080/admin/payment/payment-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-payment-maintenance-delete.png`
- ✔ pass setup:admin-delivery-maintenance-create final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-delivery-maintenance-create.png`
- ✔ pass setup:admin-delivery-maintenance-update final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-delivery-maintenance-update.png`
- ✔ pass setup:admin-delivery-maintenance-delete final=`http://127.0.0.1:18080/admin/delivery/delivery-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-delivery-maintenance-delete.png`
- ✔ pass setup:admin-tax-rule-create final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-tax-rule-create.png`
- ✔ pass setup:admin-tax-rule-delete final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-tax-rule-delete.png`
- ✔ pass setup:admin-class-name-create final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-name-create.png`
- ✔ pass setup:admin-class-name-update final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-name-update.png`
- ✔ pass setup:admin-class-category-create final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-category-create.png`
- ✔ pass setup:admin-class-category-update final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-category-update.png`
- ✔ pass setup:admin-class-category-delete final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-category-delete.png`
- ✔ pass setup:admin-class-name-delete final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-name-delete.png`
- ✔ pass setup:admin-product-create final=`/admin/product?productCode=we-mq8ixjzn-ki7urc` screenshot=``
- ✔ pass setup:admin-product-readback final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8ixjzn-ki7urc` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-product-readback.png`
- ✔ pass setup:admin-product-csv-upload final=`http://127.0.0.1:18080/admin/product?productCode=we-csv-4z03yys5` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-product-csv-upload.png`
- ✔ pass setup:admin-product-update final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8ixjzn-ki7urc` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-product-update.png`
- ✔ pass setup:admin-product-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8ixjzn-ki7urc-copy` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-product-copy.png`
- ✔ pass setup:admin-product-bulk-status final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8ixjzn-ki7urc-copy` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-product-bulk-status.png`
- ✔ pass setup:admin-product-delete-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8ixjzn-ki7urc-copy` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-product-delete-copy.png`
- ✔ pass setup:admin-category-create final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-category-create.png`
- ✔ pass setup:admin-category-update final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-category-update.png`
- ✔ pass setup:admin-category-delete final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-category-delete.png`
- ✔ pass setup:admin-category-csv-upload final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-category-csv-upload.png`
- ✔ pass setup:admin-class-name-csv-upload final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-name-csv-upload.png`
- ✔ pass setup:admin-class-category-csv-upload final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-class-category-csv-upload.png`
- ✔ pass setup:admin-tag-create final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-tag-create.png`
- ✔ pass setup:admin-tag-delete final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-tag-delete.png`
- ✔ pass setup:admin-customer-create final=`/admin/customer?email=admin-customer-20260611-admin-class-csv-browser-regression%40example.test` screenshot=``
- ✔ pass setup:admin-customer-readback final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-admin-class-csv-browser-regression%40example.test` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-customer-readback.png`
- ✔ pass setup:admin-customer-delete final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-admin-class-csv-browser-regression%40example.test` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/admin-customer-delete.png`
- ✔ pass setup:admin-logout final=`/admin/login` screenshot=``
- ✔ pass setup:storefront-product-readback final=`http://127.0.0.1:18080/product?productCode=we-mq8ixjzn-ki7urc` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/storefront-product-readback.png`
- ✔ pass setup:non-member-purchase final=`http://127.0.0.1:18080/shopping/complete?orderNo=1ad1248f8ff2aa4389f67eb639905cfa` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/non-member-purchase-complete.png`
- ✔ pass setup:customer-login final=`http://127.0.0.1:18080/mypage` screenshot=``
- ✔ pass setup:member-purchase-history-reorder final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/member-reorder-cart.png`
- ✔ pass setup:cart-quantity-and-delete final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/cart-quantity-and-delete.png`
- ✔ pass setup:customer-profile-favorite-address final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/customer-profile-favorite-address.png`
- ✔ pass setup:customer-logout-and-relogin final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/customer-logout-and-relogin.png`
- ✔ pass setup:contact-submit final=`http://127.0.0.1:18080/contact/complete?ticketId=INQ-6a29ca001b51f7.23714729` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/contact-submit.png`
- ✔ pass setup:password-reset-request final=`` screenshot=``
- ✔ pass setup:customer-withdraw final=`http://127.0.0.1:18080/mypage/withdraw-complete` screenshot=`screenshots/20260611-admin-class-csv-browser-regression/setup/customer-withdraw.png`

## Known Failures

- なし

## New Failures

- 083 Admin 商品規格編集: ✘ fail（unsafe operation not executed: PUT /admin/product/product-class） final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8ixjzn-ki7urc` reason=Browser navigation reached the page, but PUT /admin/product/product-class was not executed as an OK scenario.
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
- 124 Admin 定休日作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 125 Admin 定休日削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 126 Admin 特定商取引法表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 127 Admin 特定商取引法更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 128 Admin 受注ステータス設定表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 129 Admin 受注ステータス設定更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 130 Admin メールテンプレート一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 131 Admin メールテンプレート編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 132 Admin メールテンプレート削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 133 Admin CSV設定表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 134 Admin CSV設定更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 135 Admin マスタデータ表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 136 Admin マスタデータ選択: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 137 Admin マスタデータ更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 138 Admin メンバー一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 139 Admin メンバー作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 140 Admin メンバー詳細表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 141 Admin メンバー編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 142 Admin メンバー削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 143 Admin 権限設定更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 144 Admin ログイン履歴表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 145 Admin セキュリティ設定表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 146 Admin セキュリティ設定更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 147 Admin 二要素認証設定表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 148 Admin 二要素認証設定更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 149 Admin 二要素認証表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 150 Admin 二要素認証実行: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 151 Admin システム情報表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 152 Admin ログ表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 153 Admin キャッシュ管理表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 154 Admin キャッシュ削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 155 Admin メンテナンス表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 156 Admin メンテナンス切替: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 157 Admin ニュース一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 158 Admin ニュース作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 159 Admin ニュース編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 160 Admin ニュース削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 161 Admin ページ一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 162 Admin ページ作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行

- ... 24 more failures are in the JSON.

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
- GET /admin/log: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/login-history: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
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
- GET /mypage/address: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping/confirm: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping/login: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping/shipping: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /shopping/shipping: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping/shipping-edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /shopping/shipping-edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping/shipping-multiple: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /shopping/shipping-multiple: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /shopping/shipping-multiple-edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /shopping/shipping-multiple-edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/block/block: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /admin/block/block: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/block/block: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.

- ... 71 more operation failures are in the JSON.

## Negative Case Failures

- なし

## Negative Cases



## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
