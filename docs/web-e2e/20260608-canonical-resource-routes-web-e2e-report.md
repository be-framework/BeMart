# 20260608-canonical-resource-routes-web-e2e 実DB Web E2E結果

## Summary

- context: `html-prod-hal-api-app`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用**
- データ作成: 会員・商品・カート投入はWebフォーム操作で実施。SQL `INSERT/UPDATE` seed は使っていない。
- Web入力で作成したデータ:
  - customer email: `web-e2e-20260608-1780851113517@example.test`
  - productCode: `web-e2e-20260608-1780851276`
  - admin login used: `workflow-admin-08bcfc76`
- 結果: pass 181 / fail 2 / 対象外 3
- `Web操作結果` 未実施: 0
- 実操作対象のScreenshot空欄: 0

## Authenticated download verification

CSV/PDFのダウンロード境界は、ブラウザで導線画面を表示したうえで、同じ実DB/prodサーバーへ管理者ログインCookie付きHTTPリクエストを送り、ステータス・Content-Type・Content-Disposition・本文先頭を確認した。
- 060 Admin 商品CSV出力: /admin/product-csv — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/product-csv を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=attachment; filename="products.csv", body starts with productCode,productName,price02,stock,...
- 066 Admin カテゴリCSV出力: /admin/category/csv — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/category/csv を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=(no Content-Disposition), body starts with categoryId,categoryName,parentId,sortNo...
- 075 Admin 規格CSV出力: /admin/class-name/class-name-export — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/class-name/class-name-export を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=attachment; filename="class_name.csv", body starts with 規格名ID,規格名...
- 081 Admin 規格分類CSV出力: /admin/class-category/class-category-export — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/class-category/class-category-export を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=attachment; filename="class_category.csv", body starts with 規格分類ID,規格名ID,規格分類名...
- 092 Admin 会員CSV出力: /admin/customer-csv — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/customer-csv を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=attachment; filename="customers.csv", body starts with customerId,email,name01,name02,...
- 106 Admin 受注CSV出力: /admin/order/export-order — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/order/export-order を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=(no Content-Disposition), body starts with orderNo,customerId,orderStatus,orderDate,...
- 107 Admin 出荷CSV出力: /admin/order/export-shipping — ✔ pass（実DB/prod: ブラウザで導線画面を確認し、認証付きHTTPで200/text/csv; charset=UTF-8と内容先頭を確認） — 実DB/prodで管理画面導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/order/export-shipping を確認。status=200, Content-Type=text/csv; charset=UTF-8, Content-Disposition=(no Content-Disposition), body starts with orderNo,name01,name02,postalCode,...
- 109 Admin 受注PDF出力: /admin/order/export-order-pdf?orderNos[]=3aaaf6c72af21076c8cd32ab83434fce — ✔ pass（実DB/prod: ブラウザで受注一覧導線を確認し、認証付きHTTPでPDFダウンロードを確認） — 実DB/prodで受注一覧のPDF出力導線をブラウザ表示後、認証Cookie付きHTTP GET /admin/order/export-order-pdf?orderNos[]=... を確認。status=200, Content-Type=application/pdf, Content-Disposition=attachment; filename="nouhinsyo-No...pdf", body starts with %PDF-1.7

## Fix14 re-verification

2026-06-08にfail 14件を先に修正・再確認した。

- 注文履歴詳細/再注文: 再検証でfailに訂正。実DB/prodでWeb入力により会員作成、商品詳細からカート追加までは実行できたが、`/shopping` で顧客・配送・合計が空/0円となり、`/shopping/checkout` は `preOrderId` 400。注文履歴詳細/再注文の前提となる注文をWeb入力のみで作成できなかった。
- CSV取込4件: ブラウザで取込フォームを表示し、同一実DBセッションのmultipart HTTPアップロードで各POSTが303の正規遷移先へ到達。
- タグ3件: `127.0.0.1` canonical URLで一覧200、作成303、削除303を確認。
- 出荷通知メール2件: GET 200、POST 303 `/admin/order?orderNo=...` を確認（FakeMailer境界）。
- マスタデータ3件: GET 200、選択PUT 200、更新PUT 303を確認。

現在のfailは2。

## Failures

現在のfailは2。

- User 注文履歴詳細: 実DB/prodでWeb入力のみの再検証を実施。会員登録、商品詳細からカート追加までは成功したが、`/shopping` で顧客・配送・合計が空/0円となり、`/shopping/checkout` は `preOrderId` 400。注文履歴詳細へ到達するための注文を作成できなかった。
- User 再注文: 注文作成に失敗したため、再注文元となる注文履歴詳細を作れず、再注文操作へ到達できなかった。

この2件は「証跡不足」ではなく、現時点の実DBブラウザ操作で再現した失敗として記録する。
