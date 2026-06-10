# 20260610-web-db-all-routes Web+DB 全ルート検証結果

## Summary

- 実行日時: 2026-06-09T21:25:24.610Z
- context: `html-eccube-sql-hal-app via public/page.php`
- baseUrl: `http://127.0.0.1:18080`
- DB: eccubedb_test via DATABASE_URL（malt MySQL; setup-db.sh; 開発標準 root/パスワードなし）
- データ方針: Direct SQL seed was not used as primary evidence. setup-db.sh loaded schema/master data only. Business data creation was attempted through browser forms; failures are recorded as fail.
- 主所見: CSRFがWebフォーム境界としてend-to-endに配線されていない。ブラウザからトークン発行・埋め込み・セッション検証の往復が安定して成立しないため、業務データ作成失敗は主因ではなく派生証跡として扱う。
- Feature matrix: pass 13 / fail 170 / targetOut 3 / total 186
- OpenAPI operations: pass 36 / fail 196 / targetOut 4 / total 236
- OK操作 attempts: pass 0 / fail 6
- NGケース: pass 4 / fail 6 / total 10
- screenshots: `docs/web-e2e/screenshots/20260610-web-db-all-routes/`
- results JSON: `docs/web-e2e/results/20260610-web-db-all-routes.json`

## 重要確認結果

- CSRF: ✘ fail — 欠落/不一致を403にする箇所はあるが、Webフォームの正常系でトークン発行・埋め込み・セッション検証の往復が成立していない。今回の大量failの主因として記録。
- `/shopping/non-member` 空送信: ✘ fail — pref
- `/shopping/non-member` HTMLフォーム境界: ✘ fail — ブラウザフォームのNG応答が `text/html` ではなく `application/json` で返ったため、inlineエラー/入力値再表示の確認に進めない。
- `Invalid parameter type`: 検出あり（failとして記録）
- `Invalid parameter type` の意味: Resource呼び出し時のPHP `TypeError` が400へ変換されたもの。証跡では会員登録POSTで発生し、フォーム文字列/空文字と `int|null` 引数などの型変換境界が原因候補。
- 業務データ作成: 商品・会員・問い合わせ・非会員購入情報はWebフォームでOK操作を試行。成功しないものはfailとして記録。

## 既知fail（優先再検証）

- 注文履歴詳細: ✘ fail（Web操作だけで注文作成に到達できず、注文履歴詳細を生成できない） — 既知failを優先再検証。商品/会員/注文の前提データをWebで作成できず、注文履歴詳細へ到達不能。
- 再注文: ✘ fail（再注文元の注文履歴詳細をWeb操作で作成できない） — 既知failを優先再検証。再注文元の注文をWeb操作で作成できない。

## 新規fail（抜粋）

- #5 User 商品詳細表示: ✘ fail（指定された商品コードに該当する商品が見つかりません。）
- #6 User カート追加: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / カートの商品 / 現在カート内に商品はございません。）
- #7 User カート数量変更: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / カートの商品 / 現在カート内に商品はございません。）
- #8 User カート商品削除: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / カートの商品 / 現在カート内に商品はございません。）
- #11 User 非会員購入情報送信: ✘ fail（非会員購入情報送信失敗: pref）
- #12 User 購入確認: ✘ fail（全ての商品 / 新規会員登録  お気に入り  ログイン / カートの商品）
- #13 User 購入完了: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / カートの商品）
- #15 User 会員登録入力: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 新規会員登録）
- #16 User 会員登録確認: ✘ fail（会員登録失敗: Invalid parameter type）
- #17 User 会員登録完了: ✘ fail（会員登録失敗: Invalid parameter type）
- #18 User 会員登録完了画面: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 新規会員登録(仮登録完了) / 会員登録ありがとうございます）
- #19 User 会員メール認証: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 新規会員登録(完了) / 会員登録ありがとうございます）
- #20 User ログイン: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / ログイン / ログイン）
- #21 User ログアウト: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 新着商品 / サンプル商品 A）
- #22 User パスワード再発行依頼: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / ご登録時のメールアドレスを入力して「次へ」ボタンをクリックしてください。）
- #23 User パスワードリセット: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン）
- #24 User マイページ表示: ✘ fail（この操作を行うにはログインが必要です。）
- #25 User 注文履歴一覧: ✘ fail（この操作を行うにはログインが必要です。）
- #28 User お気に入り一覧: ✘ fail（この操作を行うにはログインが必要です。）
- #29 User お気に入り追加: ✘ fail（状態変更OK操作は成功未確認。route evidence: この操作を行うにはログインが必要です。）
- #30 User お気に入り削除: ✘ fail（状態変更OK操作は成功未確認。route evidence: この操作を行うにはログインが必要です。）
- #31 User 会員情報変更: ✘ fail（状態変更OK操作は成功未確認。route evidence: この操作を行うにはログインが必要です。）
- #32 User 会員情報変更完了: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / マイページ/会員情報編集(完了) / 会員情報編集）
- #33 User お届け先一覧: ✘ fail（この操作を行うにはログインが必要です。）
- #34 User お届け先追加: ✘ fail（状態変更OK操作は成功未確認。route evidence: この操作を行うにはログインが必要です。）
- #35 User お届け先編集: ✘ fail（状態変更OK操作は成功未確認。route evidence: この操作を行うにはログインが必要です。）
- #36 User お届け先削除: ✘ fail（状態変更OK操作は成功未確認。route evidence: この操作を行うにはログインが必要です。）
- #37 User 退会確認: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 会員情報編集）
- #38 User 退会入力/表示: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 会員情報編集）
- #39 User 退会実行: ✘ fail（状態変更OK操作は成功未確認。route evidence: 全ての商品 / 新規会員登録  お気に入り  ログイン / 会員情報編集）
- ...他 138 件

## OpenAPI operation fail（抜粋）

- #1 GET /action-redirect (goActionRedirect) — status=503 Service Unavailable
- #2 POST /action-redirect (doActionRedirect) — status=403 HTTP 403
- #5 POST /contact (doSubmitContact) — status=403 全ての商品 / 新規会員登録 / ログイン / 現在カート内に商品はございません。
- #7 POST /entry (doRegisterCustomer) — status=403 BeMart / 新規会員登録 / 全ての商品 / 新規会員登録 / ログイン
- #10 POST /forgot-password (doRequestPasswordReset) — status=400 email
- #13 POST /login (doLogin) — status=403 BeMart / ログイン / 全ての商品 / 新規会員登録 / ログイン
- #14 POST /logout (doLogout) — status=403 HTTP 403
- #15 GET /mypage (goMypage) — status=401 この操作を行うにはログインが必要です。
- #16 GET /product (goProduct) — status=404 指定された商品コードに該当する商品が見つかりません。
- #19 POST /reset (doResetPassword) — status=400 resetKey
- #23 GET /admin/action-redirect (goAdminActionRedirect) — status=403 HTTP 403
- #24 POST /admin/action-redirect (doAdminActionRedirect) — status=403 HTTP 403
- #25 GET /admin/authority-role (doUpdateAuthorityRole) — status=403 権限管理 システム設定 - BeMart / 管理者 様 / 商品管理 / 商品一覧
- #26 POST /admin/authority-role (doUpdateAuthorityRole) — status=400 loginId
- #27 GET /admin/base-info (goBaseInfo) — status=403 この操作には管理者ログインが必要です。
- #28 POST /admin/base-info (doUpdateBaseInfo) — status=400 shopName
- #29 GET /admin/calendar (goCalendar) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #30 POST /admin/calendar (doUpdateCalendar) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #31 DELETE /admin/calendar (doDeleteCalendarHoliday) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #32 GET /admin/change-password (doChangePassword) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #33 POST /admin/change-password (doChangePassword) — status=400 currentPassword
- #34 POST /admin/create-customer (doCreateCustomer) — status=403 HTTP 403
- #35 GET /admin/csv-config (doUpdateCsv) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #36 POST /admin/csv-config (doUpdateCsv) — status=400 csvType
- #37 GET /admin/customer (goCustomer) — status=403 この操作には管理者ログインが必要です。
- #38 GET /admin/customer-csv (goExportCustomer) — status=403 この操作には管理者ログインが必要です。
- #39 GET /admin/customer-delivery-edit (goAdminCustomerDeliveryEdit) — status=403 お届け先編集 会員管理 - BeMart / 管理者 様 / 商品管理 / 商品一覧
- #40 GET /admin/customer-list (goCustomerList) — status=403 この操作には管理者ログインが必要です。
- #41 POST /admin/delete-customer (doDeleteCustomer) — status=403 HTTP 403
- #42 GET /admin/empty-page (goAdminEmptyPage) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #43 GET /admin/index (goAdminTop) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #44 GET /admin/log (goAdminLog) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #46 POST /admin/login (doAdminLogin) — status=403 ログイン - BeMart / Invalid or missing CSRF token. / ログイン
- #47 GET /admin/login-history (goLoginHistoryList) — status=403 この操作には管理者ログインが必要です。
- #48 POST /admin/logout (doAdminLogout) — status=403 HTTP 403
- #49 GET /admin/mail-template (goMailTemplateList) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #50 POST /admin/mail-template (doUpdateMailTemplate) — status=400 mailTemplateId
- #51 DELETE /admin/mail-template (doDeleteMailTemplate) — status=400 mailTemplateId
- #52 GET /admin/master-data (goMasterData) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- #53 PUT /admin/master-data (doSelectMasterData) — status=403 管理者 様 / 商品管理 / 商品一覧 / 商品登録
- ...他 156 operations

## NGケース代表結果

- 重点: /shopping/non-member 空送信: ✘ fail — status=400, contentType=application/json; charset=utf-8, message=pref, screenshot=docs/web-e2e/screenshots/20260610-web-db-all-routes/ng-shopping-non-member-empty-after.png
- 非会員購入 形式不正/確認不一致/境界値: ✘ fail — status=400, contentType=application/json; charset=utf-8, message=pref, screenshot=docs/web-e2e/screenshots/20260610-web-db-all-routes/ng-shopping-non-member-invalid-filled-after.png
- 会員登録 形式不正/確認不一致/パスワード非再表示: ✘ fail — status=400, contentType=application/json; charset=utf-8, message=Invalid parameter type, screenshot=docs/web-e2e/screenshots/20260610-web-db-all-routes/ng-entry-invalid-mismatch-after.png
- CSRF欠落: 管理ログインPOST: ✔ pass — status=403, contentType=text/html; charset=utf-8, message=ログイン - BeMart / Invalid or missing CSRF token. / ログイン
- CSRF不一致: 商品作成POST: ✘ fail — status=403, contentType=text/html; charset=utf-8, message=商品詳細 商品管理 - BeMart / 管理者 様 / 商品管理 / 商品一覧
- 未ログイン: マイページGET: ✔ pass — status=401, contentType=application/json; charset=utf-8, message=この操作を行うにはログインが必要です。
- 未ログイン: 管理ダッシュボードGET: ✔ pass — status=403, contentType=text/html; charset=utf-8, message=管理者 様 / 商品管理 / 商品一覧 / 商品登録
- 存在しないID: 商品詳細GET: ✔ pass — status=404, contentType=application/json; charset=utf-8, message=指定された商品コードに該当する商品が見つかりません。
- 存在しないID: 注文履歴詳細GET: ✘ fail — status=401, contentType=application/json; charset=utf-8, message=この操作を行うにはログインが必要です。
- 存在しないID: 管理会員詳細GET: ✘ fail — status=403, contentType=application/json; charset=utf-8, message=この操作には管理者ログインが必要です。

## 対象外境界

- 外部決済ゲートウェイ送信
- 実メール配送（SMTP/外部配送そのもの。画面/フォームが存在する送信操作は別途pass/fail判定）
- 本番運用ファイル破壊的変更（実CSS/JS/テンプレート/メンテナンス反映の破壊的副作用）
- OpenAPI上の `/unsupported-route` / `/admin/unsupported-route` は意図的unsupported境界としてtargetOut。

## 関連検証

- PHPUnit: ✔ pass — `composer test -- --colors=never`（PHP 8.5.6, memory_limit=512M）で `OK (1737 tests, 26143 assertions)`。補足: 直接 `vendor/bin/phpunit --colors=never` は memory_limit 128M で TCPDF `cid0jp` 読み込み時にメモリ不足となったため、Composer scriptの512M設定で再実行。
- Psalm: ✔ pass — `vendor/bin/psalm --no-cache --no-progress` は `No errors found`（info-level 182件）。
- DB setup: ✔ pass — 開発標準の `root` / パスワードなし `DATABASE_URL` で `sql/setup-db.sh` を実行し、`eccubedb_test` を初期化。
- Server: ✔ pass — `public/page.php` を `127.0.0.1:18080` で起動し検証。
