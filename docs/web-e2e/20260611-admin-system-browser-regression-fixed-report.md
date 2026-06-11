# 20260611-admin-system-browser-regression-fixed Web+DB 全ルート検証結果

> Note: this is a limited regression run executed with `--limit=150`.
> Runner-marked failures after the limit are "not executed" rows, not new product regressions.


## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- network scope: `baseUrl is resolved from the runner process, not necessarily the user-visible local browser.`
- DB: `bemart_e2e_system_20260611_fixed` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 134 / fail 50 / 対象外 2
- OpenAPI operations: pass 135 / fail 99 / 対象外 3 / total 237
- NG cases: pass 0 / fail 0 / total 0
- screenshots: `docs/web-e2e/screenshots/20260611-admin-system-browser-regression-fixed/`
- results JSON: `docs/web-e2e/results/20260611-admin-system-browser-regression-fixed.json`

## Scope

- 母集団は `docs/api/openapi.json` の 237 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: pass final=`http://127.0.0.1:18080/admin/index`
- 会員登録: pass final=`http://127.0.0.1:18080/entry/complete`
- 業務状態作成: pass product=`we-mq8qhszb-camiu5` memberOrder=`1fa61905b50e2dcbc4b28c6453751fd2` nonMemberOrder=`329c06c1388982e5b777871cc4810be8`
- ✔ pass setup:admin-base-info-update final=`http://127.0.0.1:18080/admin/base-info` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-base-info-update.png`
- ✔ pass setup:admin-cache-clear final=`http://127.0.0.1:18080/admin/content/cache` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-cache-clear.png`
- ✔ pass setup:admin-maintenance-toggle final=`http://127.0.0.1:18080/admin/content/maintenance` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-maintenance-enable.png`
- ✔ pass setup:admin-member-maintenance final=`http://127.0.0.1:18080/admin/member-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-member-delete.png`
- ✔ pass setup:admin-security-update final=`http://127.0.0.1:18080/admin/security` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-security-update.png`
- ✔ pass setup:admin-two-factor-verify final=`http://127.0.0.1:18080/admin/index` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-two-factor-verify.png`
- ✔ pass setup:admin-payment-maintenance-create final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-payment-maintenance-create.png`
- ✔ pass setup:admin-payment-maintenance-update final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-payment-maintenance-update.png`
- ✔ pass setup:admin-payment-maintenance-delete final=`http://127.0.0.1:18080/admin/payment/payment-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-payment-maintenance-delete.png`
- ✔ pass setup:admin-delivery-maintenance-create final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-delivery-maintenance-create.png`
- ✔ pass setup:admin-delivery-maintenance-update final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-delivery-maintenance-update.png`
- ✔ pass setup:admin-delivery-maintenance-delete final=`http://127.0.0.1:18080/admin/delivery/delivery-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-delivery-maintenance-delete.png`
- ✔ pass setup:admin-tax-rule-create final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-tax-rule-create.png`
- ✔ pass setup:admin-tax-rule-delete final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-tax-rule-delete.png`
- ✔ pass setup:admin-class-name-create final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-name-create.png`
- ✔ pass setup:admin-class-name-update final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-name-update.png`
- ✔ pass setup:admin-class-category-create final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-category-create.png`
- ✔ pass setup:admin-class-category-update final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-category-update.png`
- ✔ pass setup:admin-class-category-delete final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-category-delete.png`
- ✔ pass setup:admin-class-name-delete final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-name-delete.png`
- ✔ pass setup:admin-product-create final=`/admin/product?productCode=we-mq8qhszb-camiu5` screenshot=``
- ✔ pass setup:admin-product-readback final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8qhszb-camiu5` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-product-readback.png`
- ✔ pass setup:admin-product-csv-upload final=`http://127.0.0.1:18080/admin/product?productCode=we-csv-4yeebuzx` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-product-csv-upload.png`
- ✔ pass setup:admin-product-update final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8qhszb-camiu5` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-product-update.png`
- ✔ pass setup:admin-product-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8qhszb-camiu5-copy` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-product-copy.png`
- ✔ pass setup:admin-product-bulk-status final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8qhszb-camiu5-copy` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-product-bulk-status.png`
- ✔ pass setup:admin-product-delete-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8qhszb-camiu5-copy` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-product-delete-copy.png`
- ✔ pass setup:admin-category-create final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-category-create.png`
- ✔ pass setup:admin-category-update final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-category-update.png`
- ✔ pass setup:admin-category-delete final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-category-delete.png`
- ✔ pass setup:admin-category-csv-upload final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-category-csv-upload.png`
- ✔ pass setup:admin-class-name-csv-upload final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-name-csv-upload.png`
- ✔ pass setup:admin-class-category-csv-upload final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-class-category-csv-upload.png`
- ✔ pass setup:admin-tag-create final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-tag-create.png`
- ✔ pass setup:admin-tag-delete final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-tag-delete.png`
- ✔ pass setup:admin-customer-create final=`/admin/customer?email=admin-customer-o5t4zz%40example.test` screenshot=``
- ✔ pass setup:admin-customer-readback final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-o5t4zz%40example.test` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-customer-readback.png`
- ✔ pass setup:admin-customer-delete final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-o5t4zz%40example.test` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-customer-delete.png`
- ✔ pass setup:admin-logout final=`/admin/login` screenshot=``
- ✔ pass setup:storefront-product-readback final=`http://127.0.0.1:18080/product?productCode=we-mq8qhszb-camiu5` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/storefront-product-readback.png`
- ✔ pass setup:non-member-purchase final=`http://127.0.0.1:18080/shopping/complete?orderNo=329c06c1388982e5b777871cc4810be8` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/non-member-purchase-complete.png`
- ✔ pass setup:customer-login final=`http://127.0.0.1:18080/mypage` screenshot=``
- ✔ pass setup:member-purchase-history-reorder final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/member-reorder-cart.png`
- ✔ pass setup:admin-order-update final=`http://127.0.0.1:18080/admin/order?orderNo=1fa61905b50e2dcbc4b28c6453751fd2` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-update.png`
- ✔ pass setup:admin-order-status-update final=`http://127.0.0.1:18080/admin/order?orderNo=1fa61905b50e2dcbc4b28c6453751fd2` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-status-update.png`
- ✔ pass setup:admin-order-shipping-address-update final=`http://127.0.0.1:18080/admin/order/shipping-address?orderNo=1fa61905b50e2dcbc4b28c6453751fd2` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-shipping-address-update.png`
- ✔ pass setup:admin-order-tracking-number-update final=`/admin/order/tracking-number?orderNo=1fa61905b50e2dcbc4b28c6453751fd2&_method=put` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-tracking-number-update.png`
- ✔ pass setup:admin-order-shipping-csv-import final=`http://127.0.0.1:18080/admin/order/import-shipping` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-shipping-csv-import.png`
- ✔ pass setup:admin-order-shipping-notify-send final=`/admin/order/shipping-notify-mail` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-shipping-notify-send.png`
- ✔ pass setup:admin-order-mail-send final=`/admin/order/send-mail?orderNo=1fa61905b50e2dcbc4b28c6453751fd2` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/admin-order-mail-send.png`
- ✔ pass setup:cart-quantity-and-delete final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/cart-quantity-and-delete.png`
- ✔ pass setup:customer-profile-favorite-address final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/customer-profile-favorite-address.png`
- ✔ pass setup:customer-logout-and-relogin final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/customer-logout-and-relogin.png`
- ✔ pass setup:contact-submit final=`http://127.0.0.1:18080/contact/complete?ticketId=INQ-6a29fbb6d02bf5.07403674` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/contact-submit.png`
- ✔ pass setup:password-reset-request final=`` screenshot=``
- ✔ pass setup:customer-withdraw final=`http://127.0.0.1:18080/mypage/withdraw-complete` screenshot=`screenshots/20260611-admin-system-browser-regression-fixed/setup/customer-withdraw.png`

## Known Failures

- なし

## New Failures

- 083 Admin 商品規格編集: ✘ fail（unsafe operation not executed: PUT /admin/product/product-class） final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8qhszb-camiu5` reason=Browser navigation reached the page, but PUT /admin/product/product-class was not executed as an OK scenario.
- 091 Admin 会員認証メール再送: ✘ fail（unsafe operation not executed: POST /admin/customer/resend-activation-mail） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason=Browser navigation reached the page, but POST /admin/customer/resend-activation-mail was not executed as an OK scenario.
- 096 Admin 受注作成: ✘ fail（unsafe operation not executed: POST /admin/order/create） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but POST /admin/order/create was not executed as an OK scenario.
- 098 Admin 受注削除: ✘ fail（unsafe operation not executed: POST /admin/order/bulk-delete） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but POST /admin/order/bulk-delete was not executed as an OK scenario.
- 124 Admin 定休日作成: ✘ fail（unsafe operation not executed: POST /admin/calendar） final=`http://127.0.0.1:18080/admin/calendar` reason=Browser navigation reached the page, but POST /admin/calendar was not executed as an OK scenario.
- 125 Admin 定休日削除: ✘ fail（unsafe operation not executed: DELETE /admin/calendar） final=`http://127.0.0.1:18080/admin/calendar` reason=Browser navigation reached the page, but DELETE /admin/calendar was not executed as an OK scenario.
- 127 Admin 特定商取引法更新: ✘ fail（unsafe operation not executed: POST /admin/trade-law） final=`http://127.0.0.1:18080/admin/trade-law` reason=Browser navigation reached the page, but POST /admin/trade-law was not executed as an OK scenario.
- 129 Admin 受注ステータス設定更新: ✘ fail（unsafe operation not executed: PUT /admin/order-status） final=`http://127.0.0.1:18080/admin/order-status` reason=Browser navigation reached the page, but PUT /admin/order-status was not executed as an OK scenario.
- 131 Admin メールテンプレート編集: ✘ fail（unsafe operation not executed: POST /admin/mail-template） final=`http://127.0.0.1:18080/admin/mail-template` reason=Browser navigation reached the page, but POST /admin/mail-template was not executed as an OK scenario.
- 132 Admin メールテンプレート削除: ✘ fail（unsafe operation not executed: DELETE /admin/mail-template） final=`http://127.0.0.1:18080/admin/mail-template` reason=Browser navigation reached the page, but DELETE /admin/mail-template was not executed as an OK scenario.
- 134 Admin CSV設定更新: ✘ fail（unsafe operation not executed: POST /admin/csv-config） final=`http://127.0.0.1:18080/admin/csv-config` reason=Browser navigation reached the page, but POST /admin/csv-config was not executed as an OK scenario.
- 136 Admin マスタデータ選択: ✘ fail（unsafe operation not executed: PUT /admin/master-data） final=`http://127.0.0.1:18080/admin/master-data?masterType=tag` reason=Browser navigation reached the page, but PUT /admin/master-data was not executed as an OK scenario.
- 137 Admin マスタデータ更新: ✘ fail（unsafe operation not executed: PUT /admin/master-data-edit） final=`http://127.0.0.1:18080/admin/master-data?masterType=tag` reason=Browser navigation reached the page, but PUT /admin/master-data-edit was not executed as an OK scenario.
- 143 Admin 権限設定更新: ✘ fail（unsafe operation not executed: POST /admin/authority-role） final=`http://127.0.0.1:18080/admin/authority-role` reason=Browser navigation reached the page, but POST /admin/authority-role was not executed as an OK scenario.
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
- 183 Admin プラグイン削除: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 184 Boundary 実決済ゲートウェイ送信: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 185 Boundary 実メール配送: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行
- 186 Boundary 本番運用ファイル破壊的変更: ✘ fail（--limit により未実行） final=`` reason=--limit により未実行


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
- POST /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/empty-page: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/log: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data-edit: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/plugin: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-disable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-enable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product-new: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /admin/sort-no-move: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/system: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/toggle-visible: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /admin/trade-law: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/two-factor-auth-edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
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
- GET /admin/category/edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/content/cache: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/cache: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/content/css: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/css: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/content/file-manager: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/content/js: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/js: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/content/maintenance: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/maintenance: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/customer/resend-activation-mail: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/delivery/delivery: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/layout/layout: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /admin/layout/layout: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/layout/layout-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/mail-template/create: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/news/news: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /admin/news/news: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/news/news: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/news/news-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/news/news-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/bulk-delete: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/create: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order/edit: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/order/import-shipping: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/order/order-pdf: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /admin/order/send-mail: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.

- ... 19 more operation failures are in the JSON.

## Negative Case Failures

- なし

## Negative Cases



## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
- `baseUrl` は runner プロセスから見たネットワーク境界。ローカルChrome/in-app browserが別マシンで動く場合、同じ `localhost` / `127.0.0.1` でもこの証跡とは同一視しない。
