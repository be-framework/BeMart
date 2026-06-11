# 20260611-admin-tax-rule-browser-regression-fixed2 Web+DB 全ルート検証結果

> Note: this is a limited regression run executed with `--limit=123`.
> Runner-marked failures after the limit are "not executed" rows, not new product regressions.


## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 100 / fail 84 / 対象外 2
- OpenAPI operations: pass 100 / fail 134 / 対象外 3 / total 237
- NG cases: pass 0 / fail 0 / total 0
- screenshots: `docs/web-e2e/screenshots/20260611-admin-tax-rule-browser-regression-fixed2/`
- results JSON: `docs/web-e2e/results/20260611-admin-tax-rule-browser-regression-fixed2.json`

## Scope

- 母集団は `docs/api/openapi.json` の 237 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: pass final=`http://127.0.0.1:18080/admin/index`
- 会員登録: pass final=`http://127.0.0.1:18080/entry/complete`
- 業務状態作成: pass product=`we-mq8gae80-zsjw1q` memberOrder=`6835e3ac5aee5476b311ef704ecc4d2f` nonMemberOrder=`544b22e398b5b847338a27cfdbdfb0a5`
- ✔ pass setup:admin-base-info-update final=`http://127.0.0.1:18080/admin/base-info` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-base-info-update.png`
- ✔ pass setup:admin-payment-maintenance-create final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-payment-maintenance-create.png`
- ✔ pass setup:admin-payment-maintenance-update final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-payment-maintenance-update.png`
- ✔ pass setup:admin-payment-maintenance-delete final=`http://127.0.0.1:18080/admin/payment/payment-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-payment-maintenance-delete.png`
- ✔ pass setup:admin-delivery-maintenance-create final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-delivery-maintenance-create.png`
- ✔ pass setup:admin-delivery-maintenance-update final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-delivery-maintenance-update.png`
- ✔ pass setup:admin-delivery-maintenance-delete final=`http://127.0.0.1:18080/admin/delivery/delivery-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-delivery-maintenance-delete.png`
- ✔ pass setup:admin-tax-rule-create final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-tax-rule-create.png`
- ✔ pass setup:admin-tax-rule-delete final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-tax-rule-delete.png`
- ✔ pass setup:admin-product-create final=`/admin/product?productCode=we-mq8gae80-zsjw1q` screenshot=``
- ✔ pass setup:admin-product-readback final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8gae80-zsjw1q` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-product-readback.png`
- ✔ pass setup:admin-product-update final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8gae80-zsjw1q` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-product-update.png`
- ✔ pass setup:admin-product-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8gae80-zsjw1q-copy` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-product-copy.png`
- ✔ pass setup:admin-product-bulk-status final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8gae80-zsjw1q-copy` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-product-bulk-status.png`
- ✔ pass setup:admin-product-delete-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8gae80-zsjw1q-copy` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-product-delete-copy.png`
- ✔ pass setup:admin-category-create final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-category-create.png`
- ✔ pass setup:admin-category-update final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-category-update.png`
- ✔ pass setup:admin-category-delete final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-category-delete.png`
- ✔ pass setup:admin-tag-create final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-tag-create.png`
- ✔ pass setup:admin-tag-delete final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-tag-delete.png`
- ✔ pass setup:admin-customer-create final=`/admin/customer?email=admin-customer-20260611-admin-tax-rule-browser-regression-fixed2%40example.test` screenshot=``
- ✔ pass setup:admin-customer-readback final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-admin-tax-rule-browser-regression-fixed2%40example.test` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-customer-readback.png`
- ✔ pass setup:admin-customer-delete final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-admin-tax-rule-browser-regression-fixed2%40example.test` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-customer-delete.png`
- ✔ pass setup:admin-logout final=`/admin/login` screenshot=``
- ✔ pass setup:storefront-product-readback final=`http://127.0.0.1:18080/product?productCode=we-mq8gae80-zsjw1q` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/storefront-product-readback.png`
- ✔ pass setup:non-member-purchase final=`http://127.0.0.1:18080/shopping/complete?orderNo=544b22e398b5b847338a27cfdbdfb0a5` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/non-member-purchase-complete.png`
- ✔ pass setup:customer-login final=`http://127.0.0.1:18080/mypage` screenshot=``
- ✔ pass setup:member-purchase-history-reorder final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/member-reorder-cart.png`
- ✔ pass setup:cart-quantity-and-delete final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/cart-quantity-and-delete.png`
- ✔ pass setup:customer-profile-favorite-address final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/customer-profile-favorite-address.png`
- ✔ pass setup:customer-logout-and-relogin final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/customer-logout-and-relogin.png`
- ✔ pass setup:contact-submit final=`http://127.0.0.1:18080/contact/complete?ticketId=INQ-6a29b895408bc5.04763265` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/contact-submit.png`
- ✔ pass setup:password-reset-request final=`` screenshot=``
- ✔ pass setup:customer-withdraw final=`http://127.0.0.1:18080/mypage/withdraw-complete` screenshot=`screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/customer-withdraw.png`

## Known Failures

- なし

## New Failures

- 061 Admin 商品CSV取込: ✘ fail（unsafe operation not executed: POST /admin/product-csv） final=`http://127.0.0.1:18080/admin/product/csv-product` reason=Browser navigation reached the page, but POST /admin/product-csv was not executed as an OK scenario.
- 067 Admin カテゴリCSV取込: ✘ fail（unsafe operation not executed: POST /admin/category/csv） final=`http://127.0.0.1:18080/admin/product/csv-category` reason=Browser navigation reached the page, but POST /admin/category/csv was not executed as an OK scenario.
- 072 Admin 規格作成: ✘ fail（unsafe operation not executed: POST /admin/class-name/class-name） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason=Browser navigation reached the page, but POST /admin/class-name/class-name was not executed as an OK scenario.
- 073 Admin 規格編集: ✘ fail（unsafe operation not executed: PUT /admin/class-name/class-name） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason=Browser navigation reached the page, but PUT /admin/class-name/class-name was not executed as an OK scenario.
- 074 Admin 規格削除: ✘ fail（unsafe operation not executed: DELETE /admin/class-name/class-name） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason=Browser navigation reached the page, but DELETE /admin/class-name/class-name was not executed as an OK scenario.
- 076 Admin 規格CSV取込: ✘ fail（unsafe operation not executed: POST /admin/product/csv-class-name） final=`http://127.0.0.1:18080/admin/product/csv-class-name` reason=Browser navigation reached the page, but POST /admin/product/csv-class-name was not executed as an OK scenario.
- 078 Admin 規格分類作成: ✘ fail（unsafe operation not executed: POST /admin/class-category/class-category） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason=Browser navigation reached the page, but POST /admin/class-category/class-category was not executed as an OK scenario.
- 079 Admin 規格分類編集: ✘ fail（unsafe operation not executed: PUT /admin/class-category/class-category） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason=Browser navigation reached the page, but PUT /admin/class-category/class-category was not executed as an OK scenario.
- 080 Admin 規格分類削除: ✘ fail（unsafe operation not executed: DELETE /admin/class-category/class-category） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason=Browser navigation reached the page, but DELETE /admin/class-category/class-category was not executed as an OK scenario.
- 082 Admin 規格分類CSV取込: ✘ fail（unsafe operation not executed: POST /admin/product/csv-class-category） final=`http://127.0.0.1:18080/admin/product/csv-class-category` reason=Browser navigation reached the page, but POST /admin/product/csv-class-category was not executed as an OK scenario.
- 083 Admin 商品規格編集: ✘ fail（unsafe operation not executed: PUT /admin/product/product-class） final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8gae80-zsjw1q` reason=Browser navigation reached the page, but PUT /admin/product/product-class was not executed as an OK scenario.
- 091 Admin 会員認証メール再送: ✘ fail（unsafe operation not executed: POST /admin/customer/resend-activation-mail） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason=Browser navigation reached the page, but POST /admin/customer/resend-activation-mail was not executed as an OK scenario.
- 096 Admin 受注作成: ✘ fail（unsafe operation not executed: POST /admin/order/create） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but POST /admin/order/create was not executed as an OK scenario.
- 097 Admin 受注編集: ✘ fail（unsafe operation not executed: PUT /admin/order） final=`http://127.0.0.1:18080/admin/order?orderNo=6835e3ac5aee5476b311ef704ecc4d2f` reason=Browser navigation reached the page, but PUT /admin/order was not executed as an OK scenario.
- 098 Admin 受注削除: ✘ fail（unsafe operation not executed: POST /admin/order/bulk-delete） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but POST /admin/order/bulk-delete was not executed as an OK scenario.
- 099 Admin 受注対応状況変更: ✘ fail（unsafe operation not executed: POST /admin/order-status） final=`http://127.0.0.1:18080/admin/order-status` reason=Browser navigation reached the page, but POST /admin/order-status was not executed as an OK scenario.
- 100 Admin 配送先編集: ✘ fail（unsafe operation not executed: PUT /admin/order/shipping-address） final=`http://127.0.0.1:18080/admin/order/shipping-address?orderNo=6835e3ac5aee5476b311ef704ecc4d2f` reason=Browser navigation reached the page, but PUT /admin/order/shipping-address was not executed as an OK scenario.
- 101 Admin 追跡番号更新: ✘ fail（unsafe operation not executed: PUT /admin/order/tracking-number） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but PUT /admin/order/tracking-number was not executed as an OK scenario.
- 103 Admin 出荷通知メール送信: ✘ fail（unsafe operation not executed: POST /admin/order/shipping-notify-mail） final=`http://127.0.0.1:18080/admin/order/shipping-notify-mail?orderNo=6835e3ac5aee5476b311ef704ecc4d2f` reason=Browser navigation reached the page, but POST /admin/order/shipping-notify-mail was not executed as an OK scenario.
- 105 Admin 受注メール送信: ✘ fail（unsafe operation not executed: POST /admin/order/send-mail） final=`http://127.0.0.1:18080/admin/order/send-mail?orderNo=6835e3ac5aee5476b311ef704ecc4d2f` reason=Browser navigation reached the page, but POST /admin/order/send-mail was not executed as an OK scenario.
- 108 Admin 出荷CSV取込: ✘ fail（unsafe operation not executed: POST /admin/order/import-shipping） final=`http://127.0.0.1:18080/admin/order/import-shipping` reason=Browser navigation reached the page, but POST /admin/order/import-shipping was not executed as an OK scenario.
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
- 163 Admin ページ編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 164 Admin ページ削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 165 Admin ブロック一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 166 Admin ブロック作成: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 167 Admin ブロック編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 168 Admin ブロック削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 169 Admin レイアウト一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 170 Admin レイアウト編集: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 171 Admin CSS管理表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 172 Admin CSS更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 173 Admin JavaScript管理表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 174 Admin JavaScript更新: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 175 Admin テンプレート一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 176 Admin テンプレート追加: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 177 Admin テンプレート有効化: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 178 Admin テンプレート削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 179 Admin プラグイン一覧表示: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 180 Admin プラグインインストール: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 181 Admin プラグイン有効化: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 182 Admin プラグイン無効化: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行

- ... 4 more failures are in the JSON.

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
- POST /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/change-password: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/change-password: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
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
- PUT /admin/order: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/plugin: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-disable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-enable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
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
- GET /admin/block/block-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/block/block-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/category/category: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/category/csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/category/edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /admin/class-category/class-category: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/class-category/class-category: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/class-category/class-category-list: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /admin/class-name/class-name: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/class-name/class-name: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.

- ... 54 more operation failures are in the JSON.

## Negative Case Failures

- なし

## Negative Cases



## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
