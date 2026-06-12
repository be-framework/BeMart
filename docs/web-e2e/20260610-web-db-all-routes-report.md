# 20260610-web-db-all-routes Web+DB 全ルート検証結果

## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- DB: `eccubedb_test` (`DATABASE_URL`, password redacted in JSON)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 117 / fail 64 / 対象外 5
- OpenAPI operations: pass 169 / fail 65 / 対象外 3 / total 237
- NG cases: pass 19 / fail 0 / total 19
- screenshots: `docs/web-e2e/screenshots/20260610-web-db-all-routes/`
- results JSON: `docs/web-e2e/results/20260610-web-db-all-routes.json`

## Scope

- 母集団は `docs/api/openapi.json` の 237 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: pass final=`http://127.0.0.1:18080/admin/index`
- 会員登録: pass final=`http://127.0.0.1:18080/entry/complete`
- 業務状態作成: pass product=`we-mq7kz9gc-1g2alz` memberOrder=`aab3a84258b2e6e7818055b25b7783d5` nonMemberOrder=`742d10116f150616a9b4dc684128c094`
- ✔ pass setup:admin-payment-create final=`/admin/payment/payment?paymentId=1` screenshot=``
- ✔ pass setup:admin-payment-maintenance-create final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=2` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-payment-maintenance-create.png`
- ✔ pass setup:admin-payment-maintenance-update final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=2` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-payment-maintenance-update.png`
- ✔ pass setup:admin-payment-maintenance-delete final=`http://127.0.0.1:18080/admin/payment/payment-list` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-payment-maintenance-delete.png`
- ✔ pass setup:admin-product-create final=`/admin/product?productCode=we-mq7kz9gc-1g2alz` screenshot=``
- ✔ pass setup:admin-product-readback final=`http://127.0.0.1:18080/admin/product?productCode=we-mq7kz9gc-1g2alz` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-product-readback.png`
- ✔ pass setup:admin-product-update final=`http://127.0.0.1:18080/admin/product?productCode=we-mq7kz9gc-1g2alz` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-product-update.png`
- ✔ pass setup:admin-product-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq7kz9gc-1g2alz-copy` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-product-copy.png`
- ✔ pass setup:admin-product-bulk-status final=`http://127.0.0.1:18080/admin/product?productCode=we-mq7kz9gc-1g2alz-copy` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-product-bulk-status.png`
- ✔ pass setup:admin-product-delete-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq7kz9gc-1g2alz-copy` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-product-delete-copy.png`
- ✔ pass setup:admin-category-create final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-category-create.png`
- ✔ pass setup:admin-category-update final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-category-update.png`
- ✔ pass setup:admin-category-delete final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-category-delete.png`
- ✔ pass setup:admin-tag-create final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-tag-create.png`
- ✔ pass setup:admin-tag-delete final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-tag-delete.png`
- ✔ pass setup:admin-customer-create final=`/admin/customer?email=admin-customer-20260610-web-db-all-routes%40example.test` screenshot=``
- ✔ pass setup:admin-customer-readback final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260610-web-db-all-routes%40example.test` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-customer-readback.png`
- ✔ pass setup:admin-customer-delete final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260610-web-db-all-routes%40example.test` screenshot=`screenshots/20260610-web-db-all-routes/setup/admin-customer-delete.png`
- ✔ pass setup:admin-logout final=`/admin/login` screenshot=``
- ✔ pass setup:storefront-product-readback final=`http://127.0.0.1:18080/product?productCode=we-mq7kz9gc-1g2alz` screenshot=`screenshots/20260610-web-db-all-routes/setup/storefront-product-readback.png`
- ✔ pass setup:non-member-purchase final=`http://127.0.0.1:18080/shopping/complete?orderNo=742d10116f150616a9b4dc684128c094` screenshot=`screenshots/20260610-web-db-all-routes/setup/non-member-purchase-complete.png`
- ✔ pass setup:customer-login final=`http://127.0.0.1:18080/mypage` screenshot=``
- ✔ pass setup:member-purchase-history-reorder final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260610-web-db-all-routes/setup/member-reorder-cart.png`
- ✔ pass setup:cart-quantity-and-delete final=`http://127.0.0.1:18080/cart` screenshot=`screenshots/20260610-web-db-all-routes/setup/cart-quantity-and-delete.png`
- ✔ pass setup:customer-profile-favorite-address final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260610-web-db-all-routes/setup/customer-profile-favorite-address.png`
- ✔ pass setup:customer-logout-and-relogin final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260610-web-db-all-routes/setup/customer-logout-and-relogin.png`
- ✔ pass setup:contact-submit final=`http://127.0.0.1:18080/contact/complete?ticketId=INQ-6a28eb1edff727.30457589` screenshot=`screenshots/20260610-web-db-all-routes/setup/contact-submit.png`
- ✔ pass setup:password-reset-request final=`` screenshot=``
- ✔ pass setup:customer-withdraw final=`http://127.0.0.1:18080/mypage/withdraw-complete` screenshot=`screenshots/20260610-web-db-all-routes/setup/customer-withdraw.png`

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
- 083 Admin 商品規格編集: ✘ fail（unsafe operation not executed: PUT /admin/product/product-class） final=`http://127.0.0.1:18080/admin/product?productCode=we-mq7kz9gc-1g2alz` reason=Browser navigation reached the page, but PUT /admin/product/product-class was not executed as an OK scenario.
- 091 Admin 会員認証メール再送: ✘ fail（unsafe operation not executed: POST /admin/customer/resend-activation-mail） final=`http://127.0.0.1:18080/admin/customer?customerId=1` reason=Browser navigation reached the page, but POST /admin/customer/resend-activation-mail was not executed as an OK scenario.
- 096 Admin 受注作成: ✘ fail（unsafe operation not executed: POST /admin/order/create） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but POST /admin/order/create was not executed as an OK scenario.
- 097 Admin 受注編集: ✘ fail（unsafe operation not executed: PUT /admin/order） final=`http://127.0.0.1:18080/admin/order?orderNo=aab3a84258b2e6e7818055b25b7783d5` reason=Browser navigation reached the page, but PUT /admin/order was not executed as an OK scenario.
- 098 Admin 受注削除: ✘ fail（unsafe operation not executed: POST /admin/order/bulk-delete） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but POST /admin/order/bulk-delete was not executed as an OK scenario.
- 099 Admin 受注対応状況変更: ✘ fail（unsafe operation not executed: POST /admin/order-status） final=`http://127.0.0.1:18080/admin/order-status` reason=Browser navigation reached the page, but POST /admin/order-status was not executed as an OK scenario.
- 100 Admin 配送先編集: ✘ fail（unsafe operation not executed: PUT /admin/order/shipping-address） final=`http://127.0.0.1:18080/admin/order/shipping-address?orderNo=aab3a84258b2e6e7818055b25b7783d5` reason=Browser navigation reached the page, but PUT /admin/order/shipping-address was not executed as an OK scenario.
- 101 Admin 追跡番号更新: ✘ fail（unsafe operation not executed: PUT /admin/order/tracking-number） final=`http://127.0.0.1:18080/admin/order-list` reason=Browser navigation reached the page, but PUT /admin/order/tracking-number was not executed as an OK scenario.
- 103 Admin 出荷通知メール送信: ✘ fail（unsafe operation not executed: POST /admin/order/shipping-notify-mail） final=`http://127.0.0.1:18080/admin/order/shipping-notify-mail?orderNo=aab3a84258b2e6e7818055b25b7783d5` reason=Browser navigation reached the page, but POST /admin/order/shipping-notify-mail was not executed as an OK scenario.
- 105 Admin 受注メール送信: ✘ fail（unsafe operation not executed: POST /admin/order/send-mail） final=`http://127.0.0.1:18080/admin/order/send-mail?orderNo=aab3a84258b2e6e7818055b25b7783d5` reason=Browser navigation reached the page, but POST /admin/order/send-mail was not executed as an OK scenario.
- 108 Admin 出荷CSV取込: ✘ fail（unsafe operation not executed: POST /admin/order/import-shipping） final=`http://127.0.0.1:18080/admin/order/import-shipping` reason=Browser navigation reached the page, but POST /admin/order/import-shipping was not executed as an OK scenario.
- 111 Admin 基本情報更新: ✘ fail（unsafe operation not executed: POST /admin/base-info） final=`http://127.0.0.1:18080/admin/base-info` reason=Browser navigation reached the page, but POST /admin/base-info was not executed as an OK scenario.
- 117 Admin 配送方法作成: ✘ fail（unsafe operation not executed: POST /admin/delivery/delivery-list） final=`http://127.0.0.1:18080/admin/delivery/delivery-list` reason=Browser navigation reached the page, but POST /admin/delivery/delivery-list was not executed as an OK scenario.
- 118 Admin 配送方法編集: ✘ fail（status=404 final=/admin/delivery/delivery） final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` reason=BeMart 管理者 様 ホーム 商品管理 商品一覧 商品登録 規格管理 規格分類管理 カテゴリ管理 タグ管理 受注管理 受注一覧 対応状況管理 受注CSV出力 出荷CSV出力 会員管理 会員一覧 会員CSV出力 コンテンツ管理 新着情報管理 ページ管理 レイアウト管理 ブロック管理 CSS管理 JavaScript管理 キャッシュ管理 設定 基本設定 配送方法設定 支払方法設定 税率設定 CSV出力項目設定 メンバー管理 権限管理 セ...
- 119 Admin 配送方法削除: ✘ fail（status=404 final=/admin/delivery/delivery） final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` reason=BeMart 管理者 様 ホーム 商品管理 商品一覧 商品登録 規格管理 規格分類管理 カテゴリ管理 タグ管理 受注管理 受注一覧 対応状況管理 受注CSV出力 出荷CSV出力 会員管理 会員一覧 会員CSV出力 コンテンツ管理 新着情報管理 ページ管理 レイアウト管理 ブロック管理 CSS管理 JavaScript管理 キャッシュ管理 設定 基本設定 配送方法設定 支払方法設定 税率設定 CSV出力項目設定 メンバー管理 権限管理 セ...
- 121 Admin 税率設定作成: ✘ fail（unsafe operation not executed: POST /admin/tax-rule/tax-rule-list） final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` reason=Browser navigation reached the page, but POST /admin/tax-rule/tax-rule-list was not executed as an OK scenario.
- 122 Admin 税率設定削除: ✘ fail（unsafe operation not executed: DELETE /admin/tax-rule/tax-rule） final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` reason=Browser navigation reached the page, but DELETE /admin/tax-rule/tax-rule was not executed as an OK scenario.
- 124 Admin 定休日作成: ✘ fail（unsafe operation not executed: POST /admin/calendar） final=`http://127.0.0.1:18080/admin/calendar` reason=Browser navigation reached the page, but POST /admin/calendar was not executed as an OK scenario.
- 125 Admin 定休日削除: ✘ fail（unsafe operation not executed: DELETE /admin/calendar） final=`http://127.0.0.1:18080/admin/calendar` reason=Browser navigation reached the page, but DELETE /admin/calendar was not executed as an OK scenario.
- 127 Admin 特定商取引法更新: ✘ fail（unsafe operation not executed: POST /admin/trade-law） final=`http://127.0.0.1:18080/admin/trade-law` reason=Browser navigation reached the page, but POST /admin/trade-law was not executed as an OK scenario.
- 129 Admin 受注ステータス設定更新: ✘ fail（unsafe operation not executed: PUT /admin/order-status） final=`http://127.0.0.1:18080/admin/order-status` reason=Browser navigation reached the page, but PUT /admin/order-status was not executed as an OK scenario.
- 131 Admin メールテンプレート編集: ✘ fail（unsafe operation not executed: POST /admin/mail-template） final=`http://127.0.0.1:18080/admin/mail-template` reason=Browser navigation reached the page, but POST /admin/mail-template was not executed as an OK scenario.
- 132 Admin メールテンプレート削除: ✘ fail（unsafe operation not executed: DELETE /admin/mail-template） final=`http://127.0.0.1:18080/admin/mail-template` reason=Browser navigation reached the page, but DELETE /admin/mail-template was not executed as an OK scenario.
- 134 Admin CSV設定更新: ✘ fail（unsafe operation not executed: POST /admin/csv-config） final=`http://127.0.0.1:18080/admin/csv-config` reason=Browser navigation reached the page, but POST /admin/csv-config was not executed as an OK scenario.
- 136 Admin マスタデータ選択: ✘ fail（unsafe operation not executed: PUT /admin/master-data） final=`http://127.0.0.1:18080/admin/master-data?masterType=tag` reason=Browser navigation reached the page, but PUT /admin/master-data was not executed as an OK scenario.
- 137 Admin マスタデータ更新: ✘ fail（unsafe operation not executed: PUT /admin/master-data-edit） final=`http://127.0.0.1:18080/admin/master-data?masterType=tag` reason=Browser navigation reached the page, but PUT /admin/master-data-edit was not executed as an OK scenario.
- 139 Admin メンバー作成: ✘ fail（unsafe operation not executed: POST /admin/member） final=`http://127.0.0.1:18080/admin/member-list` reason=Browser navigation reached the page, but POST /admin/member was not executed as an OK scenario.
- 140 Admin メンバー詳細表示: ✘ fail（status=404 final=/admin/member） final=`http://127.0.0.1:18080/admin/member?loginId=workflow-admin-08bcfc76` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u7ba1\u7406\u8005\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 141 Admin メンバー編集: ✘ fail（status=404 final=/admin/member） final=`http://127.0.0.1:18080/admin/member?loginId=workflow-admin-08bcfc76` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u7ba1\u7406\u8005\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 142 Admin メンバー削除: ✘ fail（status=404 final=/admin/member） final=`http://127.0.0.1:18080/admin/member?loginId=workflow-admin-08bcfc76` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u7ba1\u7406\u8005\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 143 Admin 権限設定更新: ✘ fail（unsafe operation not executed: POST /admin/authority-role） final=`http://127.0.0.1:18080/admin/authority-role` reason=Browser navigation reached the page, but POST /admin/authority-role was not executed as an OK scenario.
- 146 Admin セキュリティ設定更新: ✘ fail（unsafe operation not executed: PUT /admin/security） final=`http://127.0.0.1:18080/admin/security` reason=Browser navigation reached the page, but PUT /admin/security was not executed as an OK scenario.
- 150 Admin 二要素認証実行: ✘ fail（unsafe operation not executed: POST /admin/two-factor-auth） final=`http://127.0.0.1:18080/admin/two-factor-auth` reason=Browser navigation reached the page, but POST /admin/two-factor-auth was not executed as an OK scenario.
- 154 Admin キャッシュ削除: ✘ fail（unsafe operation not executed: PUT /admin/content/cache） final=`http://127.0.0.1:18080/admin/content/cache` reason=Browser navigation reached the page, but PUT /admin/content/cache was not executed as an OK scenario.
- 156 Admin メンテナンス切替: ✘ fail（unsafe operation not executed: PUT /admin/content/maintenance） final=`http://127.0.0.1:18080/admin/content/maintenance` reason=Browser navigation reached the page, but PUT /admin/content/maintenance was not executed as an OK scenario.
- 158 Admin ニュース作成: ✘ fail（unsafe operation not executed: POST /admin/news/news-list） final=`http://127.0.0.1:18080/admin/news/news-list` reason=Browser navigation reached the page, but POST /admin/news/news-list was not executed as an OK scenario.
- 159 Admin ニュース編集: ✘ fail（status=404 final=/admin/news/news） final=`http://127.0.0.1:18080/admin/news/news?newsId=1` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u30cb\u30e5\u30fc\u30b9\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 160 Admin ニュース削除: ✘ fail（status=404 final=/admin/news/news） final=`http://127.0.0.1:18080/admin/news/news?newsId=1` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u30cb\u30e5\u30fc\u30b9\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 162 Admin ページ作成: ✘ fail（unsafe operation not executed: POST /admin/page/page-list） final=`http://127.0.0.1:18080/admin/page/page-list` reason=Browser navigation reached the page, but POST /admin/page/page-list was not executed as an OK scenario.
- 163 Admin ページ編集: ✘ fail（status=404 final=/admin/page/page） final=`http://127.0.0.1:18080/admin/page/page?pageId=1` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u30da\u30fc\u30b8\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 164 Admin ページ削除: ✘ fail（status=404 final=/admin/page/page） final=`http://127.0.0.1:18080/admin/page/page?pageId=1` reason={"code":404,"message":"\u6307\u5b9a\u3055\u308c\u305f\u30da\u30fc\u30b8\u306f\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002"}
- 166 Admin ブロック作成: ✘ fail（unsafe operation not executed: POST /admin/block/block-list） final=`http://127.0.0.1:18080/admin/block/block-list` reason=Browser navigation reached the page, but POST /admin/block/block-list was not executed as an OK scenario.
- 167 Admin ブロック編集: ✘ fail（unsafe operation not executed: PUT /admin/block/block） final=`http://127.0.0.1:18080/admin/block/block?blockId=1` reason=Browser navigation reached the page, but PUT /admin/block/block was not executed as an OK scenario.
- 168 Admin ブロック削除: ✘ fail（unsafe operation not executed: DELETE /admin/block/block） final=`http://127.0.0.1:18080/admin/block/block?blockId=1` reason=Browser navigation reached the page, but DELETE /admin/block/block was not executed as an OK scenario.
- 170 Admin レイアウト編集: ✘ fail（unsafe operation not executed: PUT /admin/layout/layout） final=`http://127.0.0.1:18080/admin/layout/layout?layoutId=1` reason=Browser navigation reached the page, but PUT /admin/layout/layout was not executed as an OK scenario.
- 172 Admin CSS更新: ✘ fail（unsafe operation not executed: PUT /admin/content/css） final=`http://127.0.0.1:18080/admin/content/css` reason=Browser navigation reached the page, but PUT /admin/content/css was not executed as an OK scenario.
- 174 Admin JavaScript更新: ✘ fail（unsafe operation not executed: PUT /admin/content/js） final=`http://127.0.0.1:18080/admin/content/js` reason=Browser navigation reached the page, but PUT /admin/content/js was not executed as an OK scenario.
- 176 Admin テンプレート追加: ✘ fail（unsafe operation not executed: POST /admin/template/template-add） final=`http://127.0.0.1:18080/admin/template/template-add` reason=Browser navigation reached the page, but POST /admin/template/template-add was not executed as an OK scenario.
- 177 Admin テンプレート有効化: ✘ fail（unsafe operation not executed: PUT /admin/template/template-list） final=`http://127.0.0.1:18080/admin/template/template-list` reason=Browser navigation reached the page, but PUT /admin/template/template-list was not executed as an OK scenario.
- 178 Admin テンプレート削除: ✘ fail（unsafe operation not executed: DELETE /admin/template/template-list） final=`http://127.0.0.1:18080/admin/template/template-list` reason=Browser navigation reached the page, but DELETE /admin/template/template-list was not executed as an OK scenario.
- 180 Admin プラグインインストール: ✘ fail（unsafe operation not executed: POST /admin/plugin-list） final=`http://127.0.0.1:18080/admin/plugin-list` reason=Browser navigation reached the page, but POST /admin/plugin-list was not executed as an OK scenario.
- 181 Admin プラグイン有効化: ✘ fail（unsafe operation not executed: POST /admin/plugin-enable） final=`http://127.0.0.1:18080/admin/plugin-list` reason=Browser navigation reached the page, but POST /admin/plugin-enable was not executed as an OK scenario.
- 182 Admin プラグイン無効化: ✘ fail（unsafe operation not executed: POST /admin/plugin-disable） final=`http://127.0.0.1:18080/admin/plugin-list` reason=Browser navigation reached the page, but POST /admin/plugin-disable was not executed as an OK scenario.
- 183 Admin プラグイン削除: ✘ fail（unsafe operation not executed: DELETE /admin/plugin） final=`http://127.0.0.1:18080/admin/plugin-list` reason=Browser navigation reached the page, but DELETE /admin/plugin was not executed as an OK scenario.


## OpenAPI Operation Failures

- GET /action-redirect: coverage=direct-http-get, status=503, reason=OpenAPI GET operation was probed directly.
- GET /admin/action-redirect: coverage=direct-http-get, status=503, reason=OpenAPI GET operation was probed directly.
- POST /admin/authority-role: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/base-info: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/calendar: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/csv-config: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/mail-template: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/master-data-edit: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/member: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/plugin: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-disable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-enable: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/plugin-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product-csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/security: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/trade-law: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/two-factor-auth: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/block/block: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/block/block: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/block/block-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/category/csv: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/class-category/class-category: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/class-category/class-category: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/class-name/class-name: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/class-name/class-name: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/cache: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/css: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/js: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/content/maintenance: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/customer/resend-activation-mail: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/delivery/delivery: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/delivery/delivery: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/delivery/delivery-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/layout/layout: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/news/news: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/news/news: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/news/news-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/bulk-delete: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/create: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/import-shipping: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/send-mail: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/order/shipping-address: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order/shipping-address: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/order/shipping-notify-mail: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/order/tracking-number: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/page/page: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/page/page: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/page/page-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product/csv-class-category: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/product/csv-class-name: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /admin/product/product-class: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/tax-rule/tax-rule: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/tax-rule/tax-rule-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /admin/template/template-add: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- PUT /admin/template/template-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /admin/template/template-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.


## Negative Case Failures

- なし

## Negative Cases

- ✔ pass 会員登録 必須欠落: POST /entry, status=400, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-entry-required-missing.png`
- ✔ pass 会員登録 メール形式不正/確認不一致: POST /entry, status=400, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-entry-invalid-email-mismatch.png`
- ✔ pass 会員登録 CSRF欠落: POST /entry, status=403, final=`http://127.0.0.1:18080/entry`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-entry-csrf-missing.png`
- ✔ pass ログイン 認証失敗: POST /login, status=401, final=`http://127.0.0.1:18080/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-login-wrong-credential.png`
- ✔ pass ログイン 形式不正: POST /login, status=400, final=`http://127.0.0.1:18080/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-login-invalid-email.png`
- ✔ pass パスワード再発行 メール形式不正: POST /forgot-password, status=403, final=`http://127.0.0.1:18080/forgot-password`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-forgot-password-invalid-email.png`
- ✔ pass パスワードリセット 不正キー: POST /reset, status=403, final=`http://127.0.0.1:18080/reset`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-reset-invalid-key.png`
- ✔ pass お問い合わせ 必須欠落: POST /contact, status=400, final=`http://127.0.0.1:18080/contact`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-contact-required-missing.png`
- ✔ pass お問い合わせ 形式不正/境界超過: POST /contact, status=400, final=`http://127.0.0.1:18080/contact`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-contact-invalid-email-long-body.png`
- ✔ pass カート投入 数量境界不正: POST /cart/item, status=403, final=`http://127.0.0.1:18080/cart/item`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-cart-item-invalid-quantity.png`
- ✔ pass 非会員購入 必須欠落: POST /shopping/non-member, status=400, final=`http://127.0.0.1:18080/shopping/non-member`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-shopping-non-member-required-missing.png`
- ✔ pass 購入確定 存在しない preOrderId: POST /shopping/checkout, status=403, final=`http://127.0.0.1:18080/shopping/checkout`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-shopping-checkout-nonexistent-preorder.png`
- ✔ pass 会員情報変更 未ログイン: POST /mypage/change, status=403, final=`http://127.0.0.1:18080/mypage/change`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-mypage-change-unauthenticated.png`
- ✔ pass お届け先編集 存在しないID/未ログイン: PUT /mypage/address, status=400, final=`http://127.0.0.1:18080/mypage/address`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-mypage-address-nonexistent-id.png`
- ✔ pass 管理ログイン 認証失敗: POST /admin/login, status=401, final=`http://127.0.0.1:18080/admin/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-login-wrong-credential.png`
- ✔ pass 管理ログイン CSRF不一致: POST /admin/login, status=403, final=`http://127.0.0.1:18080/admin/login`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-login-csrf-invalid.png`
- ✔ pass 管理2FA チャレンジなし: POST /admin/two-factor-auth, status=403, final=`http://127.0.0.1:18080/admin/two-factor-auth`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-two-factor-no-challenge.png`
- ✔ pass 管理商品 未ログインPOST: POST /admin/product, status=400, final=`http://127.0.0.1:18080/admin/product`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-product-unauthenticated.png`
- ✔ pass 管理CSVアップロード 未ログイン: POST /admin/product-csv, status=400, final=`http://127.0.0.1:18080/admin/product-csv`, screenshot=`screenshots/20260610-web-db-all-routes/negative/ng-admin-csv-upload-unauthenticated.png`

## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
