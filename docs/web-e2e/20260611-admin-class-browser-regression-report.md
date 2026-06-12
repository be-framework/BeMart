# 20260611-admin-class-browser-regression Web+DB 全ルート検証結果

> Note: this is a limited regression run executed with `--limit=83`.
> Runner-marked failures after the limit are "not executed" rows, not new product regressions.


## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://127.0.0.1:18080`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 51 / fail 133 / 対象外 2
- OpenAPI operations: pass 53 / fail 181 / 対象外 3 / total 237
- NG cases: pass 0 / fail 0 / total 0
- screenshots: `docs/web-e2e/screenshots/20260611-admin-class-browser-regression/`
- results JSON: `docs/web-e2e/results/20260611-admin-class-browser-regression.json`

## Scope

- 母集団は `docs/api/openapi.json` の 237 operations と `docs/web-e2e/feature-implementation-matrix.md` の 186 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: pass final=`http://127.0.0.1:18080/admin/index`
- 会員登録: pass final=`http://127.0.0.1:18080/entry/complete`
- 業務状態作成: fail product=`we-mq8h1gez-cdq1m2` memberOrder=`` nonMemberOrder=`7b1f2f40fd48ab05d31f1e236574ddc2`
- ✔ pass setup:admin-base-info-update final=`http://127.0.0.1:18080/admin/base-info` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-base-info-update.png`
- ✔ pass setup:admin-payment-maintenance-create final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-payment-maintenance-create.png`
- ✔ pass setup:admin-payment-maintenance-update final=`http://127.0.0.1:18080/admin/payment/payment?paymentId=3` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-payment-maintenance-update.png`
- ✔ pass setup:admin-payment-maintenance-delete final=`http://127.0.0.1:18080/admin/payment/payment-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-payment-maintenance-delete.png`
- ✔ pass setup:admin-delivery-maintenance-create final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-delivery-maintenance-create.png`
- ✔ pass setup:admin-delivery-maintenance-update final=`http://127.0.0.1:18080/admin/delivery/delivery?deliveryId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-delivery-maintenance-update.png`
- ✔ pass setup:admin-delivery-maintenance-delete final=`http://127.0.0.1:18080/admin/delivery/delivery-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-delivery-maintenance-delete.png`
- ✔ pass setup:admin-tax-rule-create final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-tax-rule-create.png`
- ✘ fail setup:admin-tax-rule-delete final=`http://127.0.0.1:18080/admin/tax-rule/tax-rule-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-tax-rule-delete.png` error=Error: tax rule delete failed status=503 body={ "message": "Service Unavailable", "logref": "cef55a37", "request": "delete page://self/admin/tax-rule/tax-rule?csrfToken=34c8abdfff0...
- ✔ pass setup:admin-class-name-create final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-class-name-create.png`
- ✘ fail setup:admin-class-name-update final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-class-name-update.png` error=Error: class name update failed status=503 body={ "message": "Service Unavailable", "logref": "a50fc829", "request": "put page://self/admin/class-name/class-name?classNameLabel=WE+...
- ✔ pass setup:admin-class-category-create final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-class-category-create.png`
- ✘ fail setup:admin-class-category-update final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-class-category-update.png` error=Error: class category update failed status=503 body={ "message": "Service Unavailable", "logref": "dbdb8970", "request": "put page://self/admin/class-category/class-category?classC...
- ✘ fail setup:admin-class-category-delete final=`http://127.0.0.1:18080/admin/class-category/class-category-list?classNameId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-class-category-delete.png` error=Error: class category delete failed status=503 body={ "message": "Service Unavailable", "logref": "dbdb8970", "request": "delete page://self/admin/class-category/class-category?csr...
- ✘ fail setup:admin-class-name-delete final=`http://127.0.0.1:18080/admin/class-name/class-name-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-class-name-delete.png` error=Error: class name delete failed status=503 body={ "message": "Service Unavailable", "logref": "a50fc829", "request": "delete page://self/admin/class-name/class-name?csrfToken=34c8a...
- ✔ pass setup:admin-product-create final=`/admin/product?productCode=we-mq8h1gez-cdq1m2` screenshot=``
- ✔ pass setup:admin-product-readback final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8h1gez-cdq1m2` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-product-readback.png`
- ✔ pass setup:admin-product-update final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8h1gez-cdq1m2` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-product-update.png`
- ✔ pass setup:admin-product-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8h1gez-cdq1m2-copy` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-product-copy.png`
- ✘ fail setup:admin-product-bulk-status final=`http://127.0.0.1:18080/admin/product-list?nameKeyword=Web%20E2E%20%E5%AE%8C%E6%88%90%E5%88%A4%E5%AE%9A%2020260611-admin-class-browser-regression%20%E6%9B%B4%E6%96%B0` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-product-bulk-status.png` error=Error: product bulk-status failed status=503 body={ "message": "Service Unavailable", "logref": "3f7ccc3e", "request": "post page://self/admin/product-bulk-status?productCodes%5B0%...
- ✔ pass setup:admin-product-delete-copy final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8h1gez-cdq1m2-copy` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-product-delete-copy.png`
- ✔ pass setup:admin-category-create final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-category-create.png`
- ✔ pass setup:admin-category-update final=`http://127.0.0.1:18080/admin/category/category?categoryId=1` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-category-update.png`
- ✔ pass setup:admin-category-delete final=`http://127.0.0.1:18080/admin/category/category-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-category-delete.png`
- ✔ pass setup:admin-tag-create final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-tag-create.png`
- ✘ fail setup:admin-tag-delete final=`http://127.0.0.1:18080/admin/tag/tag-list` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-tag-delete.png` error=Error: tag delete failed status=503 body={ "message": "Service Unavailable", "logref": "765ce296", "request": "delete page://self/admin/tag/tag?csrfToken=34c8abdfff047f3b98a591843b...
- ✔ pass setup:admin-customer-create final=`/admin/customer?email=admin-customer-20260611-admin-class-browser-regression%40example.test` screenshot=``
- ✔ pass setup:admin-customer-readback final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-admin-class-browser-regression%40example.test` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-customer-readback.png`
- ✘ fail setup:admin-customer-delete final=`http://127.0.0.1:18080/admin/customer-list?emailKeyword=admin-customer-20260611-admin-class-browser-regression%40example.test` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/admin-customer-delete.png` error=Error: admin customer delete failed status=503 body={ "message": "Service Unavailable", "logref": "a5ad5f74", "request": "post page://self/admin/delete-customer?customerId=2&csrfTo...
- ✔ pass setup:admin-logout final=`/admin/login` screenshot=``
- ✔ pass setup:storefront-product-readback final=`http://127.0.0.1:18080/product?productCode=we-mq8h1gez-cdq1m2` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/storefront-product-readback.png`
- ✔ pass setup:non-member-purchase final=`http://127.0.0.1:18080/shopping/complete?orderNo=7b1f2f40fd48ab05d31f1e236574ddc2` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/non-member-purchase-complete.png`
- ✔ pass setup:customer-login final=`http://127.0.0.1:18080/mypage` screenshot=``
- ✘ fail setup:member-purchase-history-reorder final=`http://127.0.0.1:18080/shopping/login` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/member-purchase-history-reorder.png` error=Error: member shopping page did not expose preOrderId: 全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 現在カート内に商品はございません。 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ ログイン ログイン ログイン情報をお忘れですか？ 新規会員登録 会員登録を...
- ✘ fail setup:cart-quantity-and-delete final=`http://127.0.0.1:18080/shopping/login` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/cart-quantity-and-delete.png` error=Error: cart quantity update failed status=503 body={ "message": "Service Unavailable", "logref": "49924136", "request": "put page://self/cart/item?productCode=we-mq8h1gez-cdq1m2&qu...
- ✘ fail setup:customer-profile-favorite-address final=`http://127.0.0.1:18080/mypage/change` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/customer-profile-favorite-address.png` error=Error: profile update failed status=401 body={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u30...
- ✔ pass setup:customer-logout-and-relogin final=`http://127.0.0.1:18080/mypage` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/customer-logout-and-relogin.png`
- ✔ pass setup:contact-submit final=`http://127.0.0.1:18080/contact/complete?ticketId=INQ-6a29bda0493522.28790084` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/contact-submit.png`
- ✔ pass setup:password-reset-request final=`` screenshot=``
- ✘ fail setup:customer-withdraw final=`http://127.0.0.1:18080/forgot-password` screenshot=`screenshots/20260611-admin-class-browser-regression/setup/customer-withdraw.png` error=Error: withdraw failed status=401 body={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}

## Known Failures

- User 注文履歴詳細: ✘ fail（status=400 final=/mypage/history） final=`http://127.0.0.1:18080/mypage/history` screenshot=`screenshots/20260611-admin-class-browser-regression/026-注文履歴詳細.png`
- User 再注文: ✘ fail（status=405 final=/mypage/reorder） final=`http://127.0.0.1:18080/mypage/reorder` screenshot=`screenshots/20260611-admin-class-browser-regression/027-再注文.png`

## New Failures

- 007 User カート数量変更: ✘ fail（unsafe operation not executed: PUT /cart/item） final=`http://127.0.0.1:18080/cart` reason=Browser navigation reached the page, but PUT /cart/item was not executed as an OK scenario.
- 008 User カート商品削除: ✘ fail（unsafe operation not executed: DELETE /cart/item） final=`http://127.0.0.1:18080/cart` reason=Browser navigation reached the page, but DELETE /cart/item was not executed as an OK scenario.
- 012 User 購入確認: ✘ fail（status=404 final=/shopping/confirm） final=`http://127.0.0.1:18080/shopping/confirm` reason=全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 現在カート内に商品はございません。 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ ご注文内容のご確認 1 カートの商品 2 ご注文手続き 3 ご注文内容確認 4 完了 お客様情報 様 〒 配送情報 ( ) 様 〒 お支払方法 (￥0) お問い合わせ 小計 ￥0 手数料 ￥0 送料 ￥0 合計￥0税込 お支払い合計￥0税込 注文する ご注文手続き...
- 024 User マイページ表示: ✘ fail（status=401 final=/mypage） final=`http://127.0.0.1:18080/mypage` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 025 User 注文履歴一覧: ✘ fail（status=401 final=/mypage） final=`http://127.0.0.1:18080/mypage` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 028 User お気に入り一覧: ✘ fail（status=401 final=/mypage/favorite-list） final=`http://127.0.0.1:18080/mypage/favorite-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 029 User お気に入り追加: ✘ fail（status=401 final=/mypage/favorite-list） final=`http://127.0.0.1:18080/mypage/favorite-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 030 User お気に入り削除: ✘ fail（status=401 final=/mypage/favorite-list） final=`http://127.0.0.1:18080/mypage/favorite-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 031 User 会員情報変更: ✘ fail（status=401 final=/mypage/change） final=`http://127.0.0.1:18080/mypage/change` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 033 User お届け先一覧: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 034 User お届け先追加: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 035 User お届け先編集: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 036 User お届け先削除: ✘ fail（status=401 final=/mypage/address-list） final=`http://127.0.0.1:18080/mypage/address-list` reason={"code":401,"message":"\u3053\u306e\u64cd\u4f5c\u3092\u884c\u3046\u306b\u306f\u30ed\u30b0\u30a4\u30f3\u304c\u5fc5\u8981\u3067\u3059\u3002"}
- 038 User 退会入力/表示: ✘ fail（status=401 final=/mypage/withdraw） final=`http://127.0.0.1:18080/mypage/withdraw` reason=全ての商品 新規会員登録 お気に入り ログイン 0 ￥0 現在カート内に商品はございません。 BeMart 新入荷 ジェラート 彩のデザート CUBE アイスサンド フルーツ マイページ/退会手続き ご注文履歴 お気に入り一覧 会員情報編集 お届け先一覧 退会手続き ようこそ さん 退会手続きの前にご確認ください 退会手続きが完了した時点で、現在保存されている購入履歴やお届け先等の情報は、すべて削除されますのでご注意ください。 退会手続...
- 039 User 退会実行: ✘ fail（unsafe operation not executed: POST /mypage/withdraw） final=`http://127.0.0.1:18080/mypage/withdraw-complete` reason=Browser navigation reached the page, but POST /mypage/withdraw was not executed as an OK scenario.
- 059 Admin 商品公開状態一括変更: ✘ fail（unsafe operation not executed: POST /admin/product-bulk-status） final=`http://127.0.0.1:18080/admin/product-list` reason=Browser navigation reached the page, but POST /admin/product-bulk-status was not executed as an OK scenario.
- 061 Admin 商品CSV取込: ✘ fail（unsafe operation not executed: POST /admin/product-csv） final=`http://127.0.0.1:18080/admin/product/csv-product` reason=Browser navigation reached the page, but POST /admin/product-csv was not executed as an OK scenario.
- 067 Admin カテゴリCSV取込: ✘ fail（unsafe operation not executed: POST /admin/category/csv） final=`http://127.0.0.1:18080/admin/product/csv-category` reason=Browser navigation reached the page, but POST /admin/category/csv was not executed as an OK scenario.
- 070 Admin タグ削除: ✘ fail（unsafe operation not executed: DELETE /admin/tag/tag） final=`http://127.0.0.1:18080/admin/tag/tag-list` reason=Browser navigation reached the page, but DELETE /admin/tag/tag was not executed as an OK scenario.
- 072 Admin 規格作成: ✘ fail（unsafe operation not executed: POST /admin/class-name/class-name） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason=Browser navigation reached the page, but POST /admin/class-name/class-name was not executed as an OK scenario.
- 073 Admin 規格編集: ✘ fail（unsafe operation not executed: PUT /admin/class-name/class-name） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason=Browser navigation reached the page, but PUT /admin/class-name/class-name was not executed as an OK scenario.
- 074 Admin 規格削除: ✘ fail（unsafe operation not executed: DELETE /admin/class-name/class-name） final=`http://127.0.0.1:18080/admin/class-name/class-name-list` reason=Browser navigation reached the page, but DELETE /admin/class-name/class-name was not executed as an OK scenario.
- 076 Admin 規格CSV取込: ✘ fail（unsafe operation not executed: POST /admin/product/csv-class-name） final=`http://127.0.0.1:18080/admin/product/csv-class-name` reason=Browser navigation reached the page, but POST /admin/product/csv-class-name was not executed as an OK scenario.
- 078 Admin 規格分類作成: ✘ fail（unsafe operation not executed: POST /admin/class-category/class-category） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason=Browser navigation reached the page, but POST /admin/class-category/class-category was not executed as an OK scenario.
- 079 Admin 規格分類編集: ✘ fail（unsafe operation not executed: PUT /admin/class-category/class-category） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason=Browser navigation reached the page, but PUT /admin/class-category/class-category was not executed as an OK scenario.
- 080 Admin 規格分類削除: ✘ fail（unsafe operation not executed: DELETE /admin/class-category/class-category） final=`http://127.0.0.1:18080/admin/class-category/class-category-list` reason=Browser navigation reached the page, but DELETE /admin/class-category/class-category was not executed as an OK scenario.
- 082 Admin 規格分類CSV取込: ✘ fail（unsafe operation not executed: POST /admin/product/csv-class-category） final=`http://127.0.0.1:18080/admin/product/csv-class-category` reason=Browser navigation reached the page, but POST /admin/product/csv-class-category was not executed as an OK scenario.
- 083 Admin 商品規格編集: ✘ fail（unsafe operation not executed: PUT /admin/product/product-class） final=`http://127.0.0.1:18080/admin/product?productCode=we-mq8h1gez-cdq1m2` reason=Browser navigation reached the page, but PUT /admin/product/product-class was not executed as an OK scenario.
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

- ... 51 more failures are in the JSON.

## OpenAPI Operation Failures

- GET /action-redirect: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- POST /action-redirect: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /forgot-complete: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /mypage: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
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
- POST /admin/product-bulk-status: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
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
- PUT /cart/item: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /cart/item: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /entry/activate: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- GET /mypage/address: coverage=no-feature-row, status=n/a, reason=No matching feature matrix row. Run again without --no-probe-uncovered to send a boundary probe.
- PUT /mypage/address: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /mypage/address: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /mypage/address-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /mypage/address-list: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- GET /mypage/change: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /mypage/change: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- POST /mypage/favorite: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.
- DELETE /mypage/favorite: coverage=feature-matrix, status=n/a, reason=Covered by feature matrix browser navigation or operation evidence.

- ... 101 more operation failures are in the JSON.

## Negative Case Failures

- なし

## Negative Cases



## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
