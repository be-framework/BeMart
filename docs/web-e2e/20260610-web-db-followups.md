# 20260610 Web+DB follow-ups

`20260610-web-db-all-routes` で fail として残した項目です。ここにあるものは、runner側で成功扱いにするのではなく、先に Hypermedia/HTTP workflow へ戻してから実装修正・ブラウザ再検証を行います。

完成判定の恒久ルールは [`completion-evidence-rules.md`](completion-evidence-rules.md) を正とします。この follow-up は日付付きの事実、未解決境界、自信のない箇所を残す台帳です。

## 現在の完成判定

- Feature matrix: pass 175 / fail 2 / targetOut 9（`feature-implementation-matrix.md` 現在値。runner parser 準拠で 186 feature 行。admin order create 096 は限定 Web+DB runner `20260611-admin-order-create-browser-regression-fixed2`、admin order bulk-delete 098 は `20260611-admin-order-bulk-delete-browser-regression`、trade-law 127 は `20260611-admin-trade-law-browser-regression`、order-status settings 129 は `20260611-admin-order-status-settings-browser-regression`、mail-template 131/132 は `20260611-admin-mail-template-browser-regression`、CSV設定更新 134 は `20260611-admin-csv-config-browser-regression-2`、admin member 139-142 は `20260611-admin-member-browser-regression-emailfix`、authority-role 143 は `20260611-admin-authority-role-browser-regression`、admin system 146/150 は `20260611-admin-system-browser-regression-fixed`、calendar 124/125 は `20260611-admin-calendar-browser-regression-fixed`、master-data 136/137 は `20260611-admin-master-data-update-browser-regression`、news 158-160 は `20260611-admin-news-browser-regression-fixed`、page 162-164 は `20260611-admin-page-browser-regression-fixed`、block 166-168 は `20260611-admin-block-edit-browser-regression`、layout 170 は `20260611-admin-layout-browser-regression`、content CSS/JS 172/174 は `20260611-admin-content-css-js-browser-regression-fixed`、template 176-178 は `20260611-admin-template-active-browser-regression` で実フォーム/認証境界に接続済み。plugin 180-183 は migration scope 外として targetOut）
- OpenAPI operations: 最新の全件run `20260611-web-db-all-routes` は pass 169 / fail 65 / targetOut 3。限定回帰runで `POST /admin/order/create`、`POST /admin/order/bulk-delete`、`POST /admin/trade-law`、`POST /admin/order/import-shipping`、`PUT /admin/order-status`、`POST /admin/mail-template`、`DELETE /admin/mail-template`、`POST /admin/csv-config`、`POST /admin/authority-role`、admin member 139-142、content 154/156/172/174、`PUT /admin/security`、`POST /admin/two-factor-auth`、`POST /admin/calendar`、`DELETE /admin/calendar`、`POST /admin/news/news-list`、`PUT /admin/news/news`、`DELETE /admin/news/news`、`POST /admin/page/page-list`、`PUT /admin/page/page`、`DELETE /admin/page/page`、`PUT /admin/block/block`、`PUT /admin/layout/layout`、`PUT /admin/master-data`、`PUT /admin/master-data-edit` は pass 証跡を追加したが、次の全件runで再集計するまで公式集計へ混ぜない。
- NG cases: pass 19 / fail 0（`20260611-form-negative-visible-ui-regression-1278080` で可視エラーUI数まで確認）
- PHPUnit targeted gates: `hypermedia` / `http` / Psalm は green。
- Full PHPUnit: 既存の `composer test -- --no-progress` 契約は green（1935 tests, 27375 assertions）。
- Current generated API docs: `composer doc:api` 後は 237 operations / audit gaps 0。`20260610-web-db-all-routes` は 237 operations 母集団で再実行済み。
- 20260608 で既知 fail だった `注文履歴詳細` / `再注文` は、Web購入flow由来の注文で green。
- 全 feature fail は Admin 側。限定回帰runで見えた `unsafe operation not executed` と 404 screen/action の内訳は、次の全件runで再集計する。
- 2026-06-10 follow-up で、required workflow 内の admin direct `doCreateOrder` 使用は 0 件になった。注文を必要とする workflow は storefront checkout 由来の `orderNo` を使う。
- 2026-06-10 追加 follow-up で、admin customer create -> detail readback -> list search -> delete は `flow-admin-customer-maintenance` として Hypermedia/HTTP green になった。237-operation Web+DB browser runner では 087 会員作成と 089 会員削除を `Location`、一覧 readback、削除後 0件表示で pass に更新した。
- 2026-06-10 product follow-up で、admin product create/read/update/copy/bulk status/delete は Hypermedia/HTTP と Web+DB browser runner の両方で green。商品一覧 HTML には unsafe action token、`productCodes[]`、copy/delete/bulk status action を実 affordance として出し、HTML context の bulk status / delete は 303 PRG として確認した。
- 2026-06-10 category/tag follow-up で、admin category create/update/delete と tag create/delete は Hypermedia/HTTP と Web+DB browser runner の両方で green。操作URLは一覧/詳細の form action、`Location`、削除 anchor/token から取得し、カテゴリ詳細は input value readback、削除は一覧から消えたことを確認した。
- 2026-06-10 payment follow-up で、admin payment create/update/delete は Hypermedia/HTTP と Web+DB browser runner の両方で green。フォーム名は Resource の `paymentMethodName` / `ruleMin` / `ruleMax` に揃え、create/update/delete は HTML context で 303 PRG、`Location` readback、削除後一覧から消えたことを確認した。
- 2026-06-10 非会員購入 follow-up で、旧 Web+DB runner が `POST /shopping/non-member` の `Location` evidence だけで pass としていた不足を確認した。ブラウザー form POST は `201 Created` では遷移しないため、HTMLフォーム境界は `303 /shopping/confirm?preOrderId=...&paymentMethodId=...` に修正し、非会員の購入者情報は `dtb_order` の注文者スナップショットとして保持・読戻しする regression を追加した。in-app browser 接続不可のため、スクリーンショット evidence は次回 run で差し替える。
- 2026-06-11 追加確認で、fresh DB の `/shopping/non-member` 有効POSTが `dtb_order.payment_id -> dtb_payment.id` FK違反で 503 になる穴を確認した。既存 workflow は先に admin 支払方法作成を実行していたため、fresh DB 直後のゲスト購入を表現していなかった。Fake/Resource 側は `DefaultPaymentMethodFactory` の fallback をDB制約なしで使い、旧HTTPフォームテストも `Location` までしか見ていなかった。
- 2026-06-11 fix で `sql/seed/dtb-system-master.sql` に installer-level の初期支払方法（代金引換/クレジットカード）を追加し、`HttpSqlNonMemberCheckoutFormTest` で SQL HTML context の GET `/shopping/non-member` -> 有効POST -> GET `/shopping/confirm` を確認する regression を追加した。confirm HTML は見出し、入力メール、支払方法名まで readback する。
- 2026-06-11 follow-up で `flow-customer-purchase` から購入前の admin `doCreatePayment` setup を削除した。購入flowは商品だけをWeb/HTTPで作り、支払方法は `setup-db` の installer master を使う。`WorkflowBackdoorStateCoverageTest` に、customer purchase flow が `doCreatePayment` を使ったら fail する guard を追加した。
- 2026-06-11 browser runner follow-up で、`scripts/web-e2e-runner.mjs` から購入用の `admin-payment-create` setup を削除した。runner の支払方法 CRUD は maintenance step に分離し、購入は `setup-db` の初期支払方法を使う。`POST /shopping/non-member` は 303 `Location: /shopping/confirm?...` を必須にし、実ブラウザーで confirm URL を開いて非会員メールと `代金引換` の readback、スクリーンショット `screenshots/20260611-non-member-browser-regression/setup/shopping-non-member-confirm.png` を evidence にする。`WorkflowBackdoorStateCoverageTest` には runner がこの浅い判定へ戻らない guard も追加した。
- 2026-06-11 規格/規格分類 follow-up で、admin class-name create/update/delete と class-category create/update/delete は `flow-admin-class-maintenance` として Hypermedia/HTTP green になった。HTML form は `classNameLabel` / `classCategoryName` を送信し、`backend_name` は表示専用に戻した。browser runner では `20260611-admin-class-browser-regression-fixed` で rows 072-074 / 078-080 を 303 PRG、`Location` readback、削除後一覧から消えたことで pass に更新した。
- 2026-06-11 追加 follow-up で、非会員購入の空POSTとメール確認不一致を `HttpSqlNonMemberCheckoutFormTest` に追加した。SQL HTML context の実フォームPOSTで `400`、同じ `お客様情報の入力` 画面、inline `入力してください。` / `メールアドレスが一致しません。`、入力値再表示、例外ページなしを確認する。
- 2026-06-11 追加 follow-up で、会員登録を `HttpSqlCustomerRegistrationFormTest` に追加した。SQL HTML context の `GET /entry` -> 空POST inline error（パスワード非再表示） -> 有効POST `303 /entry/complete` -> `POST /login` `303 /mypage` -> `GET /mypage/change` の登録メール readback まで確認する。これにより、runner が `Location` を手で辿って隠していたログイン成功POSTの `200` 再描画を、HTMLフォーム送信時は PRG に修正した。
- 2026-06-11 admin CSV follow-up で、商品CSV/カテゴリCSVの `import_file` multipart upload を `HttpSqlAdminCsvUploadFormTest` に追加した。旧routerは `$_FILES` を読まないため商品CSVは `{"code":400,"message":"csv"}`、カテゴリCSVは空CSVとして200再描画していた。修正後は router が `import_file` を `csv` としてResourceへ渡し、HTML contextでは商品/カテゴリCSV import後に 303 PRG、商品詳細/カテゴリ一覧でreadbackする。
- 2026-06-11 admin class CSV follow-up で、規格CSV/規格分類CSVの `import_file` multipart upload と一覧readbackを `HttpSqlAdminCsvUploadFormTest` に追加した。旧 `EccubeClassCsvCompatibility` はCSV行数を数えるだけで `dtb_class_name` / `dtb_class_category` に永続化していなかった。修正後は既存の `ClassNameStorageInterface` / `ClassCategoryStorageInterface` とID providerでupsertし、HTML contextでは 303 PRG、規格一覧/規格分類一覧でreadbackする。`flow-admin-csv-exchange` も count 確認だけではなく、`Location` を辿って規格名IDを取得し、そのIDで規格分類CSVを取り込んだ後に一覧readbackする。
- 2026-06-11 admin CSV設定更新 follow-up で、`20260611-admin-csv-config-browser-regression` が `POST /admin/csv-config` を実行したものの `400 {"code":400,"message":"columns"}` で落ちることを確認した。HTTP regression は `columns[...]` を直接 POST していたため、実画面の `csvOutput[]` / `csvNotOutput[]` と submit 時 hidden `columns[...]` 生成の境界を見逃していた。修正後は Resource が HTML multi-select field を `columns` へ正規化し、テンプレートの submit handler は jQuery 依存を外して vanilla JS で hidden `columns[...]` を生成する。併せて EC-CUBE seed の `mtb_csv_type` に合わせて CSV type ID（1=商品, 2=会員, 3=受注, 4=配送）を修正し、`dtb_csv` 置換SQLは multi-statement 依存をやめて delete/insert に分割した。
- 2026-06-11 admin CSV設定更新 regression `npm run web:e2e -- --run-id=20260611-admin-csv-config-browser-regression-2 --base-url=http://127.0.0.1:18094 --limit=134 --skip-negative --no-probe-uncovered --no-update-matrix`: row 134 は pass。`POST /admin/csv-config` は `httpStatus=303`、`Location=/admin/csv-config?csvType=3`、`GET /admin/order/export-order` の CSV header は `paymentTotal,orderNo,orderDate`。スクリーンショット `docs/web-e2e/screenshots/20260611-admin-csv-config-browser-regression-2/setup/admin-csv-config-update.png` を保存した。この件は CSRF bypass ではなく、実フォームの入力形状と JavaScript dependency の見落としだった。
- 2026-06-11 admin order-status settings follow-up で、`HttpSqlAdminOrderStatusFormTest` を追加し、SQL HTML context の `GET /admin/order-status` -> form action `/admin/order-status?_method=put` -> hidden `csrfToken` -> `POST` override -> `303 /admin/order-status` -> settings row readback を確認した。旧 GET は hidden CSRF が空で、`onPut()` は HTML context でも同じ template を再描画して `orderStatuses` が空になるため、browser form としては成功後画面が壊れていた。修正後は `OrderStatus::onGet()` が `csrfToken` を出し、HTML context の `PUT` は 303 PRG で戻る。
- 2026-06-11 admin order-status settings browser regression `npm run web:e2e -- --run-id=20260611-admin-order-status-settings-browser-regression --base-url=http://127.0.0.1:18096 --limit=130 --skip-negative --no-probe-uncovered --no-update-matrix`: row 129 は pass。`PUT /admin/order-status` は setup operation evidence `httpStatus=303`、`Location=/admin/order-status`、settings page readback。スクリーンショット `docs/web-e2e/screenshots/20260611-admin-order-status-settings-browser-regression/setup/admin-order-status-settings-update.png` を保存した。
- 2026-06-11 admin mail-template follow-up で、`HttpSqlAdminMailTemplateFormTest` を追加し、SQL HTML context の `GET /admin/mail-template` -> `POST /admin/mail-template/create` -> selected edit form -> `POST /admin/mail-template` with real `mail_subject` -> readback -> rendered delete affordance -> `DELETE /admin/mail-template` -> list readback を確認した。旧 Resource/flow は `mailSubject` を直接POSTしていたため、HTML field name `mail_subject`、form action、hidden `mailTemplateId`、削除 affordance を見逃していた。さらに画面の削除可否を `id > 2` で仮判定しており、fresh DB では作成行が id=2 になって削除ボタンが出なかった。修正後は `dtb_mail_template.deletable` を Entity/Final/Resource へ通して `Mail.isDeletable` を決める。
- 2026-06-11 admin mail-template browser regression `npm run web:e2e -- --run-id=20260611-admin-mail-template-browser-regression --base-url=http://127.0.0.1:18095 --limit=133 --skip-negative --no-probe-uncovered --no-update-matrix`: row 131/132 は pass。`POST /admin/mail-template` は `httpStatus=303`、`Location=/admin/mail-template?mailTemplateId=...`、selected form の `mail_subject` value readback。`DELETE /admin/mail-template` は rendered delete href から `303 /admin/mail-template`、select option から消えたことを確認。スクリーンショット `docs/web-e2e/screenshots/20260611-admin-mail-template-browser-regression/setup/admin-mail-template-update.png` / `admin-mail-template-delete.png` を保存した。
- 2026-06-11 form negative follow-up で、`20260611-form-negative-regression` を `--skip-negative` なしで実行した。entry 空POST、entry メール/パスワード確認不一致、login 認証失敗/形式不正、contact 必須/長文、non-member 空POST、CSRF欠落、未ログイン系など NG 19件は pass / fail 0。スクリーンショットは `docs/web-e2e/screenshots/20260611-form-negative-regression/negative/` に保存した。feature summary は pass 41 / fail 143 / targetOut 2 だが、`--limit=43` による未実行行を fail として含むため、このrunはフォームNG証跡として扱う。
- 2026-06-11 環境確認で、in-app browser / Chrome はローカルマシン、Codex の `php -S` / `curl` / Playwright はリモート実行環境を見ており、同じ `localhost` / `127.0.0.1` でも別マシンを指していたことを確認した。これにより、手元ブラウザで見えない画面を runner が pass とする不一致が起きていた。以後、手元ブラウザ証跡と runner 証跡を同一視しない。手元ブラウザで検証する場合は、そのマシンで同じ branch / DB / PHP server を起動し、結果JSONとは別に「local browser evidence」として記録する。
- 2026-06-11 追加の環境確認で、リモート側でも `php -S localhost:8080` は名前解決により `[::1]:8080` のみを listen し、`127.0.0.1:8080` とは別境界になり得ることを確認した。さらに `lsof -nP -iTCP:8080 -sTCP:LISTEN` / `lsof -nP -iTCP:18080 -sTCP:LISTEN` / `curl --max-time 2` で、既存 server を停止したことを確認した。runner を起動する前には、必ず listener 空状態を確認してから 1本だけ起動する。
- 2026-06-11 visible error UI regression `20260611-form-negative-visible-ui-regression-1278080` はリモート実行環境の `127.0.0.1:8080` に対する `--only-negative` 限定runとして実行し、NG 19件 pass / fail 0。entry 空POSTは `status=400`、`visibleErrorUi=6/1`、final `http://127.0.0.1:8080/entry`、スクリーンショット `docs/web-e2e/screenshots/20260611-form-negative-visible-ui-regression-1278080/negative/ng-entry-required-missing.png` を保存した。non-member 空POSTは `visibleErrorUi=11/1`、contact 空POSTは `visibleErrorUi=4/1`、admin login 認証失敗は `visibleErrorUi=1/1`。このrunはローカルChromeの目視証跡ではなく、リモート runner 証跡として扱う。
- 2026-06-11 admin member follow-up で、`HttpSqlAdminMemberFormTest` を追加し、SQL HTML context の admin member create -> `Location` detail readback -> name update -> list delete form -> soft delete redirect を確認した。HTML form は create/update/delete の action、CSRF、`mode=member_form`、authority select、passwordConfirm を実フォーム境界として送る。編集時は password/passwordConfirm を出さず、department/work/2FA/password change は現 Resource/OpenAPI 契約外として永続化しない。
- 2026-06-11 content operation follow-up で、`HttpSqlAdminContentOperationFormTest` を追加し、SQL HTML context の cache clear、maintenance toggle、CSS更新、JavaScript更新を実フォームから確認した。GET は CSRF token を出し、HTML form は `_method=put` / `mode=content_operation_form` を送る。Cache は 303 `/admin/content/cache` へPRG、Maintenance は `var/tmp/maintenance-mode.flag` の marker-file 境界で enable/disable readback、CSS/JS は `var/tmp/customize-assets-<DATABASE_URL hash>.json` の runtime 境界で textarea readback する。
- 2026-06-11 content operation browser regression `20260611-content-operation-browser-regression` は、リモート runner の `127.0.0.1:18080` / DB `bemart_e2e_content_20260611` に対する `--limit=156` run。154 キャッシュ削除と 156 メンテナンス切替は setup operation evidence で pass。このrun時点では 139-142 admin member が runner の業務状態作成へ未接続で fail だったが、後続の `20260611-admin-member-browser-regression-emailfix` で実フォーム境界へ接続して pass に更新した。
- 2026-06-11 admin member browser regression `20260611-admin-member-browser-regression-emailfix` は、リモート runner の `127.0.0.1:18080` / DB `bemart_e2e_member_20260611_emailfix` に対する `--limit=142` run。139-142 は pass、業務状態作成も pass。直前の `cacheclear` run で見えた admin customer setup fail は、runner が長い run ID をそのまま `admin-customer-<run-id>@example.test` として使い local-part 64 文字制限を超えた検証ハーネス不具合だったため、短い `taxonomySuffix` を使うように修正した。
- 2026-06-11 admin system browser regression `20260611-admin-system-browser-regression-fixed` は、リモート runner の `127.0.0.1:18080` / DB `bemart_e2e_system_20260611_fixed` に対する `--limit=150` run。146 セキュリティ設定更新は、実 form action `/admin/security?_method=put`、CSRF、`trustedHosts` の送信後に GET `/admin/security` で value readback する形で pass。150 二要素認証実行は、2FA setup 後の別 browser context で `/admin/two-factor-auth` を経由し、TOTP verification から `/admin/index` へ戻ることで pass。旧 run のような「operation evidence だけ pass だが setup readback fail」は採用しない。
- 2026-06-11 master-data follow-up で、`HttpSqlAdminMasterDataFormTest` を追加し、SQL HTML context の `GET /admin/master-data` -> `#form1` action `/admin/master-data?_method=put` -> `masterType=payment` submit -> `form#form2` の `rows[*]` readback -> `POST /admin/master-data-edit?_method=put` -> `303 /admin/master-data?masterType=payment` -> payment row name readback を確認した。限定 Web+DB runner `20260611-admin-master-data-update-browser-regression` で row 136/137 は setup operation evidence と screenshot 付きで pass。
- 同 follow-up の full `http` で、前段の支払方法CRUDが作った長い表示名により `PUT /admin/master-data` response schema の `rows[*].name maxLength=32` が 400 を返すことを確認した。これは test data を短くして隠さず、master row が支払方法/配送方法/ニュース/規格など複数SQL masterを代表する contract として `get/put-admin-master-data` の `rows[*].name` を 255 に修正した。
- row 137 は `EccubeMasterDataWriter` の process-local write をやめ、`AdminMasterRegistry::listRows()` が読む payment/delivery/tag/className/classCategory/member/news storage へ接続した。generic form は `id` + `name` しか送らないため、writer は既存 row を読み、非表示列（charge/visible/classNameId/passwordHash/publishDate など）を保持して `name` 相当列だけを書き戻す。ignored `var/tmp` overlay は採用していない。
- 2026-06-11 CSRF 方針確認: 現コードに `NullCSRF` binding はない。Fake context は `tests/Fake/Reason/Service/FakeCsrfToken` の固定 token 検証、HTML SQL context は `EccubeSharedCsrfTokenAdapter` と `X-BeMart-Test-Csrf-Token` / hidden `csrfToken` の一致で通している。`isValid()` が常に true なのは smoke 専用 `ResourceSmokeCsrfToken` だけ。今後「テスト時は Null CSRF」を採用するなら、フォーム token の表示/送信 affordance と CSRF 検証無効化を分けて設計し、browser evidence を弱めないようにする。
- 2026-06-11 authority-role follow-up で、`POST /admin/authority-role` の HTML form と Resource 契約の不一致を閉じた。GET は `dtb_authority_role` から URL deny rules を読み、POST は EC-CUBE form shape `AuthorityRoles[*][Authority]` / `AuthorityRoles[*][deny_url]` を DB-backed に保存し、HTML context では `303 /admin/authority-role` へPRGする。既存 member role flip shape `loginId` / `authority` は残し、`FlowAdminSystemOperationTest` も継続 green。`FlowAdminAuthorityRoleRulesTest`、`HttpSqlAdminAuthorityRoleFormTest`、限定 browser run `20260611-admin-authority-role-browser-regression` で form action、hidden CSRF、303、deny URL readback、screenshot `screenshots/20260611-admin-authority-role-browser-regression/setup/admin-authority-role-update.png` を確認した。
- 2026-06-11 calendar follow-up で、定休日カレンダー 124/125 を実フォーム境界へ接続した。`POST /admin/calendar` は `GET /admin/calendar` の `#form1` action `/admin/calendar?operation=create` と CSRF を使い、303 PRG 後に作成行を readback する。`DELETE /admin/calendar` は作成行の削除 href `/admin/calendar?calendarId=...&_method=delete` から実行し、削除後に一覧から消えたことを確認する。`Title` / `Holiday` / `CalendarId` semantic variable 未登録 Notice が HTTP header/body を壊していたため、既存 ID/日付語彙と同じ型契約の Semantic を追加した。
- 2026-06-11 news follow-up で、ニュース 158-160 を実フォーム境界へ接続した。`AdminNewsForm` は EC-CUBE 互換 id を残しつつ `newsTitle` / `publishDate` / `newsUrl` / `newsDescription` / `linkMethod` の canonical field name を送る。`GET /admin/news/news` の create form action は `/admin/news/news-list`、edit form action は `/admin/news/news?newsId=...&_method=put`。HTML context の create/update/delete は 303 PRG とし、`HttpSqlAdminNewsFormTest` と限定 browser run `20260611-admin-news-browser-regression-fixed` で detail/list readback、削除後一覧から消えること、screenshots `admin-news-create.png` / `admin-news-update.png` / `admin-news-delete.png` を確認した。
- 2026-06-11 page follow-up で、ページ 162-164 を実フォーム境界へ接続した。`AdminPageForm` は EC-CUBE 互換 id を残しつつ `pageName` / `pageUrl` / `pageFileName` の canonical field name を送る。`tpl_data` / meta / layout join は現 Resource/OpenAPI 契約外なので disabled 表示境界に留め、送信しない。`GET /admin/page/page` の create form action は `/admin/page/page-list`、edit form action は `/admin/page/page?pageId=...&_method=put`。`pageEditType=0` の user page に delete affordance を出し、`tpage_remove.sql` は MediaQuery で実行できる1文 multi-table DELETE に直した。`HttpSqlAdminPageFormTest` と限定 browser run `20260611-admin-page-browser-regression-fixed` で detail/list readback、削除後一覧から消えること、screenshots `admin-page-create.png` / `admin-page-update.png` / `admin-page-delete.png` を確認した。
- 2026-06-11 template follow-up で、テンプレート 176-178 を実フォーム境界へ接続した。upload は BEAR manual の `#[InputFile]` / `Koriym\FileUpload\FileUpload::fromFile()`（https://bearsunday.github.io/manuals/1.0/ja/resource_param.html#%E3%83%95%E3%82%A1%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%83%E3%83%97%E3%83%AD%E3%83%BC%E3%83%89%E3%81%AE%E3%83%86%E3%82%B9%E3%83%88）に合わせ、Resource/Hypermedia は `FileUpload::fromFile(__DIR__ . '/../fixtures/template-upload.zip')`、HTTP/browser は同じ form action へ multipart/form-data で送る。active template は EC-CUBE 本番 asset deploy ではなく ignored `var/tmp/template-active-<DATABASE_URL hash>.txt` の runtime readback 境界として保持し、GET `/admin/template/template-list` の `templates[].active` と radio `checked="checked"` に投影する。`HttpSqlAdminTemplateFormTest` と限定 browser run `20260611-admin-template-active-browser-regression` で upload後一覧表示、select後checked表示、delete後一覧非表示、screenshots `admin-template-upload.png` / `admin-template-select.png` / `admin-template-delete.png` を確認した。
- 2026-06-11 template follow-up 中に、HTTP/browser context が古い `var/tmp/html-eccube-sql-hal-app/{di,injector,twig}` を見て、実装後も checked が出ない false fail を確認した。以後、コード変更後の HTTP/browser runner は PHP server 起動前に compiled context cache を消す。`HttpSqlAdminTemplateFormTest` と `scripts/web-e2e-runner.mjs` にはこの cache cleanup を入れた。ただし既に起動済みの PHP server が injector を保持している場合は、cache cleanup だけでは足りないので server を再起動する。

## 止める基準

- 実フォーム、`_links`、HTML form action、`Location`、ALPS rel から操作URLを得られない unsafe 操作は実行しない。
- 業務状態を作るために直接SQL seed、fixture boundary、runner専用の直POST bodyを追加しない。
- 画面到達だけで副作用を pass にしない。作成・更新・削除は readback 画面または別ロール画面で postcondition を確認する。
- 外部決済、実SMTP、本番ファイル破壊、実プラグインインストールなどは targetOut とし、fake/noop 境界を確認できる場合だけ別途 pass にする。
- テストを通すためだけの ResourceObject wrapper、URI route mapper、固定dummy fixture、DBリセット、強制ID採番補正は追加しない。根拠が不足している場合は fail/follow-up として残す。

## 自信がない/証拠不足として残す箇所

次の項目は、現時点で「作れば通る」実装をしてはいけない。標準の Hypermedia/HTTP workflow に戻せる証拠を集められない場合は、fail または targetOut として残して止める。

- Admin unsafe operation は、画面到達だけでは成功扱いにしない。実フォームまたは表現上の action/link から payload を復元できるまで、runner 専用の直POST/PUT/DELETEは作らない。
- 非会員購入フォーム送信は、`Location` ヘッダだけで pass にしない。ブラウザーで遷移可能な 303、確認画面の購入者情報 readback、完了後の注文履歴/再注文までを同一注文で確認する。
- `POST /admin/delete-customer` の HTML 境界 503 は修正済み。対応では `HttpAdminCustomerDeleteHtmlTest` を先に追加し、HTML context では 303 `/admin/customer-list` へ PRG する契約として確認した。以後も template missing を隠すだけの ResourceObject wrapper や runner 側成功扱いは追加しない。
- Admin 404 screen/action は、固定IDを仮定しない。create flow 由来のIDで edit/delete/readback へ進める workflow ができるまで、404をマスタデータ不足として握りつぶさない。
- SQL-backed Resource suite は、履歴・schema・fixture helper の整合が取れるまで復元しない。`SqlFixturesTrait` の空実装、固定dummy値、NOT NULL/FK を満たすだけの最小行投入はしない。
- HTTP projection で Resource workflow と違う挙動が出た場合は、HTTP 側を正として扱う。ただし HTTP を通すために test context や session/fake state を追加する前に、HTML form/CSRF/Cookie/redirect の観測結果を記録する。
- CSRF test boundary は現状 Null ではない。固定 token fake や test header を使う場合でも、画面に hidden `csrfToken` が出て、同一 cookie/context で送信されることを確認する。検証無効化へ寄せる場合は、フォーム affordance の確認まで消さない。
- Noop/fake 境界は、境界契約が表現またはテストで観測できる場合だけ pass にする。実SMTP、実決済、破壊的ファイル操作は、観測不能なまま成功扱いにしない。
- Raw `vendor/bin/phpunit --no-progress` は、Composer script が指定する 512MB memory limit を通らないため TCPDF PDF export で停止する。これは project test contract の差として扱い、調査前に skip や軽量 renderer で隠さない。
- `BEAR.Dev.HtmlLinkAudit` warning は full suite green でも大量に残る。warning を成功扱いから消す前に、Resource meta の target 判定と HTML/profile 表現のどちらが正かを確認する。
- `get-admin-class-name-class-name-list.json` / `get-admin-class-category-class-category-list.json` の `name.maxLength` は 32 だが、`ClassNameLabel` / `ClassCategoryName` Semantic と EC-CUBE schema は 255 文字を許す。今回のCSV回帰では短い値で通したが、schemaを32のままにするか255へ戻すかは、Semantic-Ex生成元とFake観察値を確認してから別途直す。
- `localhost` / `127.0.0.1` の手動確認結果は、ローカル/リモートの実行場所が揃っていなければ証跡へ混ぜない。フォームエラー表示の成否を判定する場合は、どのマシンの browser がどのマシンの PHP server を見ているか、listener 確認、DB URL、run ID を揃える。
- Admin member の `department`、work、2FA、password change は、今回の member form regression では完成扱いにしない。`AdminMemberForm` は department/work/2FA を表示境界に留め、編集時の authority/password 更新も Resource/OpenAPI/ALPS の保存 transition が揃うまで追加しない。
- Admin member の 139-142 は限定 Web+DB runner `20260611-admin-member-browser-regression-emailfix` で green。実フォーム操作で作った `loginId` を detail/edit/delete rows へ渡し、delete form affordance から `DELETE /admin/member` を確認した。ただし `department`、work、2FA、password change は引き続き完成扱いにしない。
- Cache clear / maintenance toggle の 154/156 は限定 browser regression `20260611-content-operation-browser-regression` で pass。CSS/JavaScript 更新の 172/174 は `20260611-admin-content-css-js-browser-regression-fixed` で pass。Maintenance marker-file は ignored `var/tmp/maintenance-mode.flag`、customize asset は ignored `var/tmp/customize-assets-<DATABASE_URL hash>.json` に限定し、本番運用ファイルへの反映は引き続き targetOut 境界として扱う。
- Authority role update の 143 は限定 browser regression `20260611-admin-authority-role-browser-regression` で pass。証跡は `screenshots/20260611-admin-authority-role-browser-regression/setup/admin-authority-role-update.png`。実フォーム `AuthorityRoles[0][Authority]` / `AuthorityRoles[0][deny_url]` / hidden CSRF から `POST /admin/authority-role` を実行し、303 PRG と `dtb_authority_role` readback まで確認した。
- Security update の 146 は限定 browser regression `20260611-admin-system-browser-regression-fixed` で pass。ただし writer は本番設定ファイルではなく ignored `var/tmp/security-config.json` に保存する runtime 境界なので、demo の可観測な設定更新として扱い、本番運用ファイル更新の完成扱いにはしない。
- Two-factor auth の 150 は限定 browser regression `20260611-admin-system-browser-regression-fixed` で pass。TOTP は `FakeTwoFactorAuth` 境界で、実デバイス登録や外部認証アプリの検証は対象外。
- Calendar create/delete の 124/125 は限定 browser regression `20260611-admin-calendar-browser-regression-fixed` で pass。証跡は `screenshots/20260611-admin-calendar-browser-regression-fixed/setup/admin-calendar-holiday-create.png` と `screenshots/20260611-admin-calendar-browser-regression-fixed/setup/admin-calendar-holiday-delete.png`。更新操作も同 flow 内で `admin-calendar-holiday-update.png` に readback を残したが、matrix 行としては作成/削除のみを pass に更新する。
- News create/update/delete の 158-160 は限定 browser regression `20260611-admin-news-browser-regression-fixed` で pass。証跡は `screenshots/20260611-admin-news-browser-regression-fixed/setup/admin-news-create.png`、`admin-news-update.png`、`admin-news-delete.png`。この修正は HTML form action/field-name mismatch と delete ALPS rel 誤りを閉じる範囲に限定し、本文/URLの追加仕様や公開/非公開 `visible` は完成扱いにしない。
- Page create/update/delete の 162-164 は限定 browser regression `20260611-admin-page-browser-regression-fixed` で pass。証跡は `screenshots/20260611-admin-page-browser-regression-fixed/setup/admin-page-create.png`、`admin-page-update.png`、`admin-page-delete.png`。この修正は HTML form action/field-name mismatch、`pageEditType=0` delete affordance、削除SQLの1文化を閉じる範囲に限定し、`tpl_data`、meta、layout join、実Twigファイル生成は完成扱いにしない。

## 調査済みだが採用しなかった回避策

- Hypermedia workflow の `ResourceObject` を eager render して snapshot 化する wrapper は採用しない。`href()`/`linkHref()` の標準挙動とズレ、`goContactForm` のような実リンク解決を壊すことを確認したため削除した。
- PHPUnit process-isolation は採用しない。`#[Depends]` が返す `ResourceObject` 内の Closure を serialize できず、workflow の形を変えるだけの回避になる。
- SQL-backed Resource suite 用の `SqlFixturesTrait` を空実装や固定dummy戻り値で作らない。現 `tests/Resource/Sql` が要求する fixture helper は多く、schema/FK/NOT NULL と履歴上の意図を確認して復元する必要がある。
- `dtb_customer.id` 重複を隠すために flow 内で DB を drop/create したり、テスト専用の採番補正を追加したりしない。2026-06-10 の調査では、原因は採番 SQL ではなく `WorkflowDbSession` が `prod` compiled context を使っていたため Resource 操作が別 connection/autocommit になったことだった。

## Follow-up groups

### Test harness / suite reliability

`vendor/bin/phpunit --testsuite hypermedia` は、2026-06-10 の follow-up 修正後に green。旧状態では assertion failure ではなく PHP process の異常終了で止まっていた。

確認済み:

- `WorkflowDbSessionTest`: green。Resource POST で作った顧客が workflow transaction 内で見え、`restore()` 後に消えることを guard している。
- 2026-06-10 中間修正時点の `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（232 tests, 1085 assertions）。
- 2026-06-10 中間修正時点の `vendor/bin/phpunit --testsuite http --no-progress`: green（215 tests, 1078 assertions）。
- `vendor/bin/psalm --no-progress`: green（errors 0、info 180）。
- 2026-06-10 追加 follow-up 後の `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（258 tests, 1240 assertions）。
- 2026-06-10 追加 follow-up 後の `vendor/bin/phpunit --testsuite http --no-progress`: green（228 tests, 1181 assertions）。
- 2026-06-10 追加 follow-up 後の `WorkflowBackdoorStateCoverageTest` + `WorkflowSkeletonCoverageTest`: green（40 tests, 222 assertions）。
- 2026-06-10 admin customer follow-up 後の `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（267 tests, 1293 assertions）。
- 2026-06-10 admin customer workflow follow-up 後、HTML delete regression 追加前の `vendor/bin/phpunit --testsuite http --no-progress`: green（234 tests, 1222 assertions）。
- 2026-06-10 admin customer follow-up 後の `vendor/bin/psalm --no-progress`: green（errors 0、info 180）。
- 2026-06-10 admin customer follow-up 後の `composer doc:api`: green。`docs/api/audit.md` は resources 147 / operations 237 / request schema 157 / gaps 0。
- 2026-06-10 admin customer follow-up 後の `HttpAdminCustomerDeleteHtmlTest`: green（1 test, 12 assertions）。
- 2026-06-10 admin customer follow-up 後の `FlowAdminCustomerMaintenanceTest` + `Http\FlowAdminCustomerMaintenanceTest`: green（12 tests, 82 assertions）。
- 2026-06-10 admin customer follow-up 後の `vendor/bin/phpunit --testsuite http --no-progress`: green（235 tests, 1234 assertions）。
- 2026-06-10 admin customer follow-up 後の `vendor/bin/psalm --no-progress`: green（errors 0、info 180）。
- 2026-06-10 admin customer follow-up 後の `npm run web:e2e -- --run-id=20260610-web-db-all-routes --base-url=http://127.0.0.1:18080`: feature 104 pass / 77 fail / 5 targetOut、OpenAPI 159 pass / 75 fail / 3 targetOut、NG 19 pass / 0 fail。
- 2026-06-10 product follow-up 後の `AdminProductResourceTest` + `AdminProductBulkStatusResourceTest` + `AdminProductListHtmlRenderTest` + `FlowAdminProductPublishTest` + `Http\FlowAdminProductPublishTest`: green（49 tests, 283 assertions）。
- 2026-06-10 product follow-up 後の `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（267 tests, 1293 assertions）。
- 2026-06-10 product follow-up 後の `vendor/bin/phpunit --testsuite http --no-progress`: green（235 tests, 1234 assertions）。
- 2026-06-10 product follow-up 後の `vendor/bin/psalm --no-progress`: green（errors 0、info 180）。
- 2026-06-10 product follow-up 後の `npm run web:e2e -- --run-id=20260610-web-db-all-routes --base-url=http://127.0.0.1:18080`: feature 110 pass / 71 fail / 5 targetOut、OpenAPI 164 pass / 70 fail / 3 targetOut、NG 19 pass / 0 fail。
- 2026-06-10 category/tag follow-up 後の `AdminCategoryResourceTest` + `AdminTagResourceTest` + `AdminCategoryEditHtmlRenderTest`: green（29 tests, 65 assertions）。
- 2026-06-10 category/tag follow-up 後の `FlowAdminCategoryMaintenanceTest` + `FlowAdminTagMaintenanceTest`: green（12 tests, 65 assertions）。
- 2026-06-10 category/tag follow-up 後の HTTP projection `FlowAdminCategoryMaintenanceTest` + `FlowAdminTagMaintenanceTest`: green（12 tests, 65 assertions）。
- 2026-06-10 category/tag follow-up 後の `npm run web:e2e -- --run-id=20260610-web-db-all-routes --base-url=http://127.0.0.1:18080`: feature 115 pass / 66 fail / 5 targetOut、OpenAPI 167 pass / 67 fail / 3 targetOut、NG 19 pass / 0 fail。
- 2026-06-10 payment follow-up 後の `AdminPaymentResourceTest` + `AdminPaymentEditHtmlRenderTest` + `AdminPaymentListHtmlRenderTest` + `FlowAdminShopConfigurationTest` + HTTP projection `FlowAdminShopConfigurationTest`: green（61 tests, 285 assertions）。
- 2026-06-10 payment follow-up 後の `npm run web:e2e -- --run-id=20260610-web-db-all-routes --base-url=http://127.0.0.1:18080`: feature 117 pass / 64 fail / 5 targetOut、OpenAPI 169 pass / 65 fail / 3 targetOut、NG 19 pass / 0 fail。
- 2026-06-10 非会員購入 follow-up 後の `HttpCheckoutEntryFormTest --filter NonMember`: green（2 tests, 47 assertions）。有効な browser form submit が 303 で `/shopping/confirm?preOrderId=...&paymentMethodId=...` へ進む契約を確認した。
- 2026-06-10 非会員購入 follow-up 後の `OrderConfirmedTest --filter Guest`: green（1 test, 14 assertions）。`customerId` を持たない非会員注文は注文者スナップショットから確認画面の customer projection を作る。
- 2026-06-10 非会員購入 follow-up 後の `HttpCheckoutEntryFormTest`、`OrderConfirmedTest` + `NonMemberSubmittedTest` + `CheckoutCompletedTest` + `AdminOrderFetchedTest`、`ShoppingConfirmResourceTest` + `ShoppingCompleteResourceTest` + `AdminOrderResourceTest`: green。
- 2026-06-10 非会員購入 follow-up 後の `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（287 tests, 1395 assertions）。
- 2026-06-10 非会員購入 follow-up 後の `vendor/bin/phpunit --testsuite http --no-progress`: green（251 tests, 1359 assertions）。
- 2026-06-10 非会員購入 follow-up 中の in-app browser 接続は `Browser is not available: iab` で不可。実ブラウザーでのスクリーンショット再証跡は未完了。
- 2026-06-10 非会員購入 follow-up 中に `tests/Resource/Sql/CheckoutResourceSqlTest.php` を直接実行したところ、既存の `SqlFixturesTrait` / `be/tests/Sql/bootstrap.php` 削除により PHPUnit load 前に停止した。SQL-backed Resource suite は現 worktree では検証ゲートとして使えない。
- 2026-06-11 fresh DB seed 後の `HttpSqlNonMemberCheckoutFormTest`: green（1 test, 31 assertions）。SQL HTML context で非会員フォーム有効POST後、confirm画面にメールと `代金引換` が表示されることを確認した。
- 2026-06-11 fresh DB seed 後の実サーバー `127.0.0.1:18080`: GET `/shopping/non-member` -> 有効フォームPOST は 303、GET `/shopping/confirm?...` は 200。confirm HTML に入力メールと `代金引換(￥0)` を確認した。
- 2026-06-11 payment setup 削除後の `FlowCustomerPurchaseTest` + HTTP projection + `WorkflowBackdoorStateCoverageTest`: green（138 tests, 515 assertions）。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-non-member-browser-regression --base-url=http://127.0.0.1:18080 --limit=60 --skip-negative --no-probe-uncovered --no-update-matrix`: setup business state は pass。`POST /shopping/non-member` evidence は 303 Location、confirm finalUrl、非会員メール、`代金引換`、screenshot を保存した。feature summary は pass 58 / fail 126 / targetOut 2 だが、fail の大半は `--limit=60` による未実行行であり、この run は非会員購入境界の差し替え証跡として扱う。
- 2026-06-11 規格/規格分類 follow-up 後の `FlowAdminClassMaintenanceTest` + HTTP projection: green（30 tests, 180 assertions）。class-name/class-category の create -> readback -> update -> readback -> delete -> readback を、固定 unsafe URI ではなく `Link` / `Location` から確認した。
- 2026-06-11 規格/規格分類 follow-up 後の `AdminClassNameListHtmlRenderTest` + `AdminClassCategoryListHtmlRenderTest`: green（6 tests, 33 assertions）。HTML form が canonical field `classNameLabel` / `classCategoryName` を送信し、`backend_name` を送信しないことを確認した。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-admin-class-browser-regression-fixed --base-url=http://127.0.0.1:18080 --limit=83 --skip-negative --no-probe-uncovered --no-update-matrix`: rows 072-074 / 078-080 は pass。規格/規格分類 CRUD の HTTP status はすべて 303、`Location` readback とスクリーンショットを `docs/web-e2e/screenshots/20260611-admin-class-browser-regression-fixed/setup/` に保存した。061/067/076/082/083 は unsafe 未実行 fail のまま残す。
- 2026-06-11 規格/規格分類 follow-up 後の `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（323 tests, 1532 assertions）。
- 2026-06-11 規格/規格分類 follow-up 後の `vendor/bin/phpunit --testsuite http --no-progress`: green（266 tests, 1485 assertions）。
- 2026-06-11 規格/規格分類 follow-up 後の `vendor/bin/psalm --no-progress`: green（errors 0、info 180）。
- 2026-06-11 root/no-password DB URL（`mysql://root@127.0.0.1:3306/eccubedb_test?...`）での `HttpSqlNonMemberCheckoutFormTest`: green（3 tests, 74 assertions）。空POSTは `400` でフォーム内エラーを表示し、メール確認不一致は入力値を再表示する。
- 2026-06-11 root/no-password DB URL での `HttpSqlCustomerRegistrationFormTest`: green（2 tests, 82 assertions）。登録NGのinline error、登録成功、ログイン `303 /mypage`、`/mypage/change` での登録メール readback を確認した。
- 2026-06-11 root/no-password DB URL での registration/non-member 関連 targeted gate: `FlowCustomerRegistrationTest` + HTTP projection + `HttpCheckoutEntryFormTest` + `HttpSqlNonMemberCheckoutFormTest` + `HttpSqlCustomerRegistrationFormTest` は green（27 tests, 396 assertions）。
- 2026-06-11 root/no-password DB URL での `HttpSqlAdminCsvUploadFormTest`: green（2 tests, 51 assertions）。商品CSV multipart upload は 303 `/admin/product-list` 後に商品詳細で productCode/name をreadback、カテゴリCSV multipart upload は 303 `/admin/category/category-list` 後にカテゴリ一覧で categoryName をreadbackした。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-admin-csv-browser-regression --base-url=http://127.0.0.1:18080 --limit=83 --skip-negative --no-probe-uncovered --no-update-matrix`: row 061 商品CSV取込と row 067 カテゴリCSV取込は pass。`POST /admin/product-csv` は 303 + 商品詳細readback screenshot、`POST /admin/category/csv` は 303 + カテゴリ一覧readback screenshot を保存した。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-admin-class-csv-browser-regression --base-url=http://127.0.0.1:18080 --limit=83 --skip-negative --no-probe-uncovered --no-update-matrix`: row 076 規格CSV取込と row 082 規格分類CSV取込は pass。`POST /admin/product/csv-class-name` は 303 + 規格一覧readback screenshot、`POST /admin/product/csv-class-category` は 303 + 規格分類一覧readback screenshot を保存した。row 083 商品規格編集は、保存Resource/Be Finalがないため fail のまま。
- 2026-06-11 order-history maildate regression: まず `FlowCustomerPurchaseTest` に会員購入 -> admin配送先更新 -> admin追跡番号更新 -> admin受注メール送信 -> 会員注文履歴詳細のステップを追加し、`mailHistories[0].sendDate` schema error を再現した。`order_history_by_order_no.sql` 修正後、`FlowCustomerPurchaseTest` は green（45 tests, 177 assertions）、HTTP projection `tests/Http/FlowCustomerPurchaseTest.php` も green（45 tests, 177 assertions）。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-order-history-maildate-regression --base-url=http://127.0.0.1:18080 --limit=109 --skip-negative --no-probe-uncovered --no-update-matrix`: feature 102 pass / 82 fail / 2 targetOut、OpenAPI 103 pass / 131 fail / 3 targetOut。025 注文履歴一覧と 026 注文履歴詳細は 200 pass、105 受注メール送信も setup operation evidence で pass。この時点の fail は既知の 083/091/096/098/108 と `--limit=109` による未実行行が中心だったが、108 は `20260611-admin-order-shipping-csv-import-regression-fixed`、098 は `20260611-admin-order-bulk-delete-browser-regression` で後続修正済み。
- 2026-06-11 `tests/Resource/Sql/MypageHistoryResourceSqlTest.php` は `SqlFixturesTrait` が現 worktree に存在せず、PHPUnit load 前に停止した。`sendDate` assertion は追加したが、この suite は引き続き SQL-backed Resource suite restoration 境界として扱う。
- 2026-06-11 出荷CSV取込 follow-up 後の `FlowAdminOrderFulfillmentTest`: green（33 tests, 138 assertions）。`POST /admin/order/import-shipping` は受注flow由来の注文番号を使い、CSV upload 後に出荷CSV export で `TRK-CSV-*` の追跡番号を readback する。
- 2026-06-11 出荷CSV取込 follow-up 後の HTTP projection `tests/Http/FlowAdminOrderFulfillmentTest.php`: green（33 tests, 138 assertions）。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-admin-order-shipping-csv-import-regression-fixed --base-url=http://127.0.0.1:18080 --limit=109 --skip-negative --no-probe-uncovered --no-update-matrix`: row 108 出荷CSV取込は pass。`POST /admin/order/import-shipping` は `httpStatus=200`、import後の出荷CSV export に注文番号と `TRK-CSV-*` が含まれることを確認し、スクリーンショット `docs/web-e2e/screenshots/20260611-admin-order-shipping-csv-import-regression-fixed/setup/admin-order-shipping-csv-import.png` を保存した。
- 2026-06-11 受注削除 follow-up 後の `HttpSqlAdminOrderBulkDeleteFormTest`: green（1 test, 80 assertions）。SQL HTML context で商品作成 -> 非会員購入 -> 受注一覧 form `ids[]` / hidden `mode` / JS action readback -> `POST /admin/order/bulk-delete` -> `303 /admin/order-list` -> 受注詳細 `注文取消` readback を確認した。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-admin-order-bulk-delete-browser-regression --base-url=http://127.0.0.1:18087 --limit=98 --skip-negative --no-probe-uncovered --no-update-matrix`: row 098 受注削除は pass。`POST /admin/order/bulk-delete` は `httpStatus=303`、`Location=/admin/order-list`、`mode=order_bulk_delete_form`、対象注文番号、受注詳細 `注文取消` readback、スクリーンショット `docs/web-e2e/screenshots/20260611-admin-order-bulk-delete-browser-regression/setup/admin-order-bulk-delete.png` を保存した。
- 2026-06-11 特定商取引法 follow-up 後の `HttpSqlAdminTradeLawFormTest`: green（1 test, 30 assertions）。SQL HTML context で `GET /admin/trade-law` の行フォーム `trade_law_1_name` / `trade_law_1_description` と hidden CSRF を読み、`POST /admin/trade-law` 後に `303 /admin/trade-law`、再GETで更新行をreadbackした。
- 2026-06-11 browser runner regression `npm run web:e2e -- --run-id=20260611-admin-trade-law-browser-regression --base-url=http://127.0.0.1:18088 --limit=127 --skip-negative --no-probe-uncovered --no-update-matrix`: row 127 特定商取引法更新は pass。`POST /admin/trade-law` は `httpStatus=303`、`Location=/admin/trade-law`、EC-CUBE互換行フォームからの `tradeLawBody` 正規化、管理画面readback、スクリーンショット `docs/web-e2e/screenshots/20260611-admin-trade-law-browser-regression/setup/admin-trade-law-update.png` を保存した。
- 2026-06-11 form negative regression `npm run web:e2e -- --run-id=20260611-form-negative-regression --base-url=http://127.0.0.1:18080 --limit=43 --no-probe-uncovered --no-update-matrix`: negative cases 19 pass / 0 fail。entry/non-member の空POSTは `400` で同じフォーム画面に戻り、inline `入力してください。` を含む。login 認証失敗は `401` と日本語エラー文を記録した。
- 2026-06-11 form negative targeted gate: `HttpCheckoutEntryFormTest` + `HttpSqlNonMemberCheckoutFormTest` + `HttpSqlCustomerRegistrationFormTest`: green（13 tests, 344 assertions）。
- 2026-06-11 admin member form targeted gate: `HttpSqlAdminMemberFormTest`: green（1 test, 72 assertions）。create/detail/update/list/delete を SQL HTML context のフォーム action/CSRF/redirect/readback で確認した。
- 2026-06-11 admin member related gates: `AdminMemberHtmlRenderTest`: green（3 tests, 18 assertions）、`FlowAdminSystemOperationTest`: green（23 tests, 95 assertions）、HTTP projection `tests/Http/FlowAdminSystemOperationTest.php`: green（23 tests, 95 assertions）。HTTP projection の初回失敗は stale compiled DI と並列 PHPUnit 実行による DB deadlock で、cache cleanup 後に直列再実行して green。
- 2026-06-11 admin member combined targeted gate: `HttpSqlAdminMemberFormTest` + `AdminMemberHtmlRenderTest` + Hypermedia/HTTP `FlowAdminSystemOperationTest` を直列実行し green（51 tests, 285 assertions）。`BEAR.Dev.HtmlLinkAudit` warning は残るが failure ではないため、HTML link audit follow-up として別に扱う。
- 2026-06-11 admin system targeted gate: `HttpSqlAdminSystemFormTest` + `AdminSecurityResourceTest` + Hypermedia/HTTP `FlowAdminSystemOperationTest` を stale compiled context cleanup 後に直列実行し green（52 tests, 225 assertions）。Security form は実 CSRF token と `_method=put` を使い、`trustedHosts` の runtime readback を確認した。
- 2026-06-11 admin system browser regression `npm run web:e2e -- --run-id=20260611-admin-system-browser-regression-fixed --base-url=http://127.0.0.1:18080 --limit=150 --skip-negative --no-probe-uncovered --no-update-matrix`: feature summary は pass 134 / fail 50 / targetOut 2、OpenAPI は pass 135 / fail 99 / targetOut 3。146 セキュリティ設定更新と 150 二要素認証実行は setup operation evidence で pass。fail の大半は `--limit=150` による未実行行と既知の Admin unsafe operations であり、この run は admin system 146/150 の限定証跡として扱う。
- 2026-06-11 admin calendar targeted gate: `AdminCalendarResourceTest` + `AdminCalendarHtmlRenderTest` + Hypermedia/HTTP `FlowAdminShopConfigurationTest` + `HttpSqlAdminCalendarFormTest` を直列実行し green（55 tests, 375 assertions）。定休日 create -> update -> readback -> delete -> readback を Resource/HTTP/HTML form の全境界で確認した。
- 2026-06-11 admin calendar browser regression `npm run web:e2e -- --run-id=20260611-admin-calendar-browser-regression-fixed --base-url=http://127.0.0.1:18080 --limit=125 --skip-negative --no-probe-uncovered --no-update-matrix`: row 124/125 は setup operation evidence で pass。feature summary は pass 119 / fail 65 / targetOut 2、OpenAPI は pass 120 / fail 114 / targetOut 3。fail の大半は `--limit=125` による未実行行と既知の Admin unsafe operations であり、この run は calendar 124/125 の限定証跡として扱う。
- 2026-06-11 content operation targeted gate: `HttpSqlAdminContentOperationFormTest` + `AdminContentSideEffectResourceTest`: green（10 tests, 130 assertions）。Cache clear は form から 303 PRG、Maintenance は enable/disable の readback、CSS/JS は form submit 後の textarea readback を確認した。
- 2026-06-11 content operation related gate: Hypermedia/HTTP `FlowAdminSystemOperationTest`: green（46 tests, 190 assertions）。`BEAR.Dev.HtmlLinkAudit` warning は残るが failure ではないため、HTML link audit follow-up として別に扱う。
- `FlowCustomerInquiryTest` + `FlowCustomerRegistrationTest`: green。
- `FlowCustomerPurchaseTest` + `FlowCustomerRegistrationTest`: green。実行前後で `dtb_customer` 件数が増えないことを確認済み。
- `FlowAdminMasterDataUpdateTest`: green。
- `FlowAdminProductPublishTest`: green。admin product create -> update -> copy -> bulk status update -> delete -> storefront readback を Hypermedia + HTTP で確認済み。
- `FlowAdminCsvExchangeTest`: green。
- `FlowAdminMasterDataUpdateTest` + `FlowAdminCsvExchangeTest`: green。
- `FlowAdminCsvExchangeTest` + `FlowAdminShopConfigurationTest`: green。
- `FlowAdminOrderFulfillmentTest` + `FlowAdminProductPublishTest` + `FlowAdminShopConfigurationTest` + `FlowAdminSystemOperationTest`: green。
- `WorkflowBackdoorStateCoverageTest` + `WorkflowSkeletonCoverageTest`: green。
- `composer test -- --no-progress`: green（1935 tests, 27375 assertions, Memory 138.50 MB）。

修正済み:

- `WorkflowDbSession` は `html-prod-eccube-sql-hal-app` ではなく `html-eccube-sql-hal-app` を使う。in-process Hypermedia workflow は Resource 操作と readback query が同じ SQL connection/transaction を共有する必要があり、HTTP/prod 境界は HTTP workflow で別途確認する。
- 旧挙動では Resource POST 直後に transaction 側 connection から行が見えず、`restore()` 後に `dtb_customer` 行が残った。現在は `WorkflowDbSessionTest` と `FlowCustomerPurchaseTest` + `FlowCustomerRegistrationTest` で rollback を確認している。
- `FlowCustomerInquiryTest` は `test-hal-app` fake context から `WorkflowDbSession` の SQL-backed workflow へ移した。問い合わせ送信は NoopMailer 境界なので、Web+DB 主証跡として扱える。
- `FlowAdminMasterDataUpdateTest` は `admin-test-hal-app` fake context から `WorkflowDbSession` の SQL-backed workflow へ移した。`MasterDataWriterInterface` の実装は in-memory 境界で、破壊的 DB/file 更新は行わない。
- 旧再現の `FlowAdminMailTemplateMaintenanceTest` -> `FlowAdminMasterDataUpdateTest` と `FlowCustomerAccountMaintenanceTest` -> `FlowCustomerInquiryTest` はどちらも green。
- HTTP projection の `FlowAdminMasterDataUpdateTest` は、他の Admin HTTP workflow と同じ `prod-json-index.php` 境界へ揃えた。古い `admin-json-index.php` は `X-BeMart-Test-Admin-Id` / CSRF header を session へ反映しないため、PUT が 403 になっていた。
- `flow-admin-product-publish` に `doCopyProduct`、`doBulkUpdateProductStatus`、`doDeleteProduct` と削除後 readback を追加した。操作URLは admin product/list 表現の rel から取得し、固定URIや runner 専用 payload は追加していない。
- `product_copy.sql` と `product_status_bulk_update.sql` は、末尾 `SELECT` が semicolon で終わっておらず Ray MediaQuery の SQL split で最後の readback/count query が実行されていなかった。SQL 終端を修正し、copy readback と `changedCount` を SQL-backed workflow で確認した。
- Admin 商品一覧 HTML に、copy/delete/bulk status の unsafe action token と action を実 affordance として出した。delete は `_method=delete` を POST body で送る HTML 境界に直し、bulk status/delete は HTML context で 303 `/admin/product-list` へ PRG する契約にした。`AdminProductListHtmlRenderTest`、`AdminProductBulkStatusResourceTest`、`AdminProductResourceTest` と Web+DB runner で確認済み。
- `flow-admin-category-maintenance` と `flow-admin-tag-maintenance` を追加した。カテゴリは一覧の `doCreateCategory` rel から作成、`Location` の詳細 readback、詳細表現の `doUpdateCategory` / `doDeleteCategory` rel から更新・削除、削除後 list readback まで確認した。タグは一覧の `doCreateTag` rel から作成、一覧 readback、`doDeleteTag` rel から削除、削除後 list readback まで確認した。
- Category HTML に削除 affordance を追加し、HTML context の create/update/delete は 303 PRG として確認した。`tcategory_remove.sql` は末尾 semicolon 欠落により MediaQuery の最後の DELETE が実行されていなかったため修正した。
- Web+DB runner はカテゴリ/タグについても、HTML form action / anchor token / `Location` から unsafe operation を実行する。カテゴリ詳細 readback は本文ではなく `input[name="categoryName"]` の value を正として確認する。
- Web+DB runner は支払い方法についても、HTML form action / anchor token / `_method` / `Location` から unsafe operation を実行する。購入用の支払方法を削除しないため、maintenance 用の別支払方法を作成し、更新 readback と削除後 list readback を確認する。
- `MailTemplateStorageInterface::update()` が `void` になったため、戻り値を持つ fake fixture として残っていた `be/var/fake/query/tmail_template_update.jsonl` を削除した。これは fake を足す修正ではなく、MediaQuery coverage guard の「non-void DbQuery だけが fake fixture を持つ」契約に合わせた。
- 新規 `POST page://self/admin/mail-template/create` Resource に対応する smoke fixture を追加した。ResourceSmoke の母集団と Resource meta の不一致を解消するためで、業務状態作成の主証跡としては扱わない。
- `flow-admin-order-fulfillment` は admin `doCreateOrder` ではなく、admin affordance で支払方法と商品を作成し、storefront の商品一覧 -> 商品詳細 -> cart -> non-member checkout -> complete で得た `orderNo` を admin 受注更新/対応状況/配送先/追跡番号/メール/PDF/CSV に渡す形へ修正した。Hypermedia/HTTP targeted はどちらも green（31 tests, 118 assertions）。
- `flow-admin-mail-template-maintenance` の受注メール送信確認も admin `doCreateOrder` 依存を削除し、storefront checkout 由来の `orderNo` で `goOrderMail` / `doSendOrderMail` を確認する形へ修正した。Hypermedia/HTTP targeted はどちらも green（9 tests, 91 assertions）。
- `WorkflowBackdoorStateCoverageTest` に、required workflow で `doCreateOrder` を使った場合に fail する guard を追加した。手元の grep でも `tests/Hypermedia` / `tests/Http` / `tests/Support` の `doCreateOrder` 残存は 0 件。
- `WorkflowBackdoorStateCoverageTest` に、`flow-customer-purchase` が `doCreatePayment` を使った場合に fail する guard を追加した。支払方法CRUDは `flow-admin-shop-configuration` の責務で、購入flowの前提作成に使わない。
- `flow-admin-customer-maintenance` を追加し、admin customer list の `doCreateCustomer` rel から作成、`Location` の詳細 readback、`goCustomerList` での検索 readback、`doDeleteCustomer` rel での削除、削除後 list return まで確認した。Hypermedia/HTTP targeted はどちらも green（各 6 tests, 41 assertions; combined 12 tests, 82 assertions）。browser runner では `CreateCustomer` の `Location: /admin/customer?email=...` と一覧行から会員IDを観測して 087 を pass にし、`POST /admin/delete-customer` は HTML context の PRG 303 と削除後 0件 readback で 089 を pass にした。
- `CustomerList` の `doCreateCustomer` rel を実 Resource/OpenAPI と同じ `page://self/admin/create-customer` に直し、`doDeleteCustomer` / `doResendActivationMail` rel も Resource 表現に追加した。HTTP projection では stale compiled context が古い link を返したため、`var/tmp/*eccube-sql-hal-app` を消して再実行し、source と runtime の不一致であることを確認した。

現時点で targeted Hypermedia/HTTP suite reliability と Composer full suite の未解決項目はない。残る follow-up は Web+DB browser/HTTP 側の未実行 Admin unsafe operations、Admin 404 screen/action、SQL-backed Resource suite restoration、HTML link audit warning の整理。

### Full PHPUnit / PDF memory boundary

Raw `vendor/bin/phpunit --no-progress` は、2026-06-10 の follow-up 修正後も green ではない。停止箇所は `tests/Hypermedia/FlowAdminOrderFulfillmentTest.php:247` の `goExportOrderPdf` で、TCPDF が `vendor/tecnickcom/tcpdf/fonts/cid0jp.php` を読み込む途中に `Allowed memory size of 134217728 bytes exhausted` で PHP process が終了した。

ただし `composer.json` の既存 `test` script は `php -d memory_limit=512M ./vendor/bin/phpunit` を project test contract として定義している。この契約では `composer test -- --no-progress` が green（1935 tests, 27375 assertions）で完了したため、現時点では PDF renderer の実装変更は行わない。

確認済み:

- `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green。
- `FlowAdminOrderFulfillmentTest` を含む複数 Hypermedia workflow の targeted 実行: green。
- `vendor/bin/phpunit --testsuite http --no-progress`: green。
- `vendor/bin/psalm --no-progress`: green。
- `composer test -- --no-progress`: green。

未確認:

- raw `vendor/bin/phpunit` も green にすべきか、既存 `composer test` 契約を正式な full suite command として docs/AGENTS 側へ寄せるべきか。
- browser runner の PDF download evidence と PHPUnit PDF export の検証責務をどう分けるべきか。

禁止する回避:

- 新規の `memory_limit` 引き上げを、既存 `composer test` 契約の確認なしに追加する。
- `testExportsOrderPdf` を skip/incomplete にする。
- PDF export の検証を削除する。
- テスト専用の軽量 PDF renderer に差し替えて業務境界を隠す。

### HTML link audit warnings

Full suite green でも `BEAR.Dev.HtmlLinkAudit` warning は多数出力される。主な reason は `target-missing` と `html-missing`。これは今回の green 判定では failure ではないが、Hypermedia 完成判定では無視し続けるべきではない。

扱い方:

- warning を抑制して green 表示をきれいにするだけの変更はしない。
- Resource meta 上の URI、HTML template の form/action/link、ALPS rel のどれが正準かを確認してから、必要な `#[Link]` または HTML affordance を直す。
- Web+DB runner の Admin unsafe operation を閉じるときに、同じ rel/action の warning も一緒に減らす。

### SQL-backed Resource suite restoration

`tests/Resource/Sql/WithdrawResourceSqlTest.php` の直接実行では、既存の `SqlFixturesTrait` 定義がこの worktree で見つからず PHPUnit bootstrap 前に停止する。さらに `AbstractResourceSqlTestCase` が前提にする `be/tests/Sql/bootstrap.php` も現 worktree に存在しない。

確認済み:

- `composer.json` の `test:sql` は `SQL PHPUnit suite is disabled; Sql folders are ignored by phpunit.xml.` を表示するだけ。
- `phpunit.xml` は `tests/Resource/Sql` を fake suite から除外しているが、SQL suite を定義していない。
- 履歴では `c47dd51a Remove SQL PHPUnit suite` が `be/tests/Sql` 一式を削除している。復元する場合は履歴版の `bootstrap.php` / `SqlFixturesTrait.php` / `AbstractSqlTestCase.php` を現在の `sql/setup-db.sh` と docs に合わせて戻す。
- `sql/README.md` は `vendor/bin/phpunit --testsuite bemart-sql` と Resource SQL の実行を記載しているが、現 `composer.json` / `phpunit.xml` とは一致しない。
- `sql/README.md` は DB unreachable を fail fast とし、`docs/methodology/sql-test-baseline.md` は unreachable を skip としており方針が矛盾している。

この suite は Web+DB 完成判定の主証跡ではなく、SQL-backed Resource の境界テストである。直接 SQL fixture はここでは許容されうるが、Hypermedia/HTTP/browser の業務状態作成には使わない。

復元する場合の最小方針:

1. `be/tests/Sql/bootstrap.php` を履歴から戻し、現 `AbstractResourceSqlTestCase` が期待する `['skip' => bool, 'reason'?, 'pdo'?]` shape に合わせる。
2. `SqlFixturesTrait` は履歴版をベースに、現 `tests/Resource/Sql` が使う helper だけを schema/FK/NOT NULL に沿って復元する。空実装・固定dummy戻り値は禁止。
3. `phpunit.xml` / `composer test:sql` / `sql/README.md` の suite 名と skip/fail 方針を揃える。
4. Final-direct integration の再導入は G-23 の方針と衝突しうるため、Resource SQL 復元とは別PRで判断する。

### Admin catalog / master CRUD

対象: カテゴリ、タグ、規格、規格分類、商品規格、CSV取込。

商品作成、詳細 readback、編集、コピー、一括公開状態変更、削除は 2026-06-10 product follow-up で green。商品/カテゴリ/規格/規格分類CSV取込は 2026-06-11 follow-up で multipart upload と readback まで green。残る商品系は商品規格編集。

カテゴリ作成/編集/削除、タグ作成/削除、規格作成/編集/削除、規格分類作成/編集/削除は Hypermedia/HTTP と browser setup evidence の両方で green。操作URLは一覧/詳細の form action、`Location`、削除 anchor/token から取得し、作成/更新後の readback と削除後の一覧非表示を確認した。

残る商品カタログ系は商品規格編集。商品CSV取込、カテゴリCSV取込、規格CSV取込、規格分類CSV取込は 2026-06-11 admin CSV follow-up で、ブラウザでフォームとCSRFを取得し、multipart upload後に一覧/readbackで結果を確認済み。

商品規格編集は現状で止める。`src/Resource/Page/Admin/Product/ProductClass.php` は `onGet()` のみで、template は `POST /admin/product/product-class` を出しているが、Resource/OpenAPI 上の保存 transition は `PUT /admin/product/product-class` として未実装。Be 側にも `UpdateProductClassInput` / `ProductClassUpdated` は存在しない。ここで runner 専用の直PUTや空のFinalを作ると、商品規格の本質である class-name/class-category -> price/stock/product-code の行更新を隠すだけになる。次に進める条件は、ProductClass matrix の read model と update transition を設計し、class-name/class-category 由来のIDを商品規格UIへ流し、価格/在庫の更新後に商品詳細または管理画面で readback できる Hypermedia/HTTP workflow を先に追加すること。

### Admin customer workflow

対象: 会員作成、会員編集、会員削除、会員配送先編集、認証メール再送。

2026-06-10 追加 follow-up で、作成・検索 readback・削除は workflow 化した。`CustomerList` の `doCreateCustomer` rel を `page://self/admin/create-customer` に修正し、`doDeleteCustomer` / `doResendActivationMail` rel を Resource 表現へ追加した。`flow-admin-customer-maintenance` は admin customer list -> create -> `Location` detail -> list search readback -> delete -> list return を、固定 unsafe URI ではなく公開 rel から実行する。

確認済み:

- `FlowAdminCustomerMaintenanceTest`: green（6 tests, 41 assertions）。
- `Http\FlowAdminCustomerMaintenanceTest`: green（6 tests, 41 assertions）。
- `FlowAdminCustomerMaintenanceTest` + `Http\FlowAdminCustomerMaintenanceTest`: green（12 tests, 82 assertions）。
- `vendor/bin/phpunit --testsuite hypermedia --no-progress`: green（267 tests, 1293 assertions）。
- `vendor/bin/phpunit --testsuite http --no-progress`: green（235 tests, 1234 assertions）。

未完成として残す境界:

- `Admin\Customer` は `onGet()` のみで、HTML form は `/admin/customer?customerId=...` に POST する。OpenAPI 上も admin customer update operation はなく、ALPS でも admin 用の update transition は定義されていない。ここに `PUT /admin/customer` workflow をテスト都合で作らない。
- `Admin\CustomerDeliveryEdit` は GET renderer のみで、docblock も「この wave では永続化 ALPS transition なし」としている。HTML form は空 action の POST だが、OpenAPI/Resource 上の保存 operation はない。`PUT /admin/customer-delivery-edit` をテスト都合で追加しない。
- `ResendActivationMail` は Resource/OpenAPI/ALPS があるが、admin create と public entry はどちらも `status=2` の有効会員を作る。再送対象の仮登録会員（`customerStatus=1` かつ `secretKey`あり）を Web/HTTP 遷移で作る導線がまだない。直接DBで provisional customer を作らない。さらに public registration の完了画面は「仮登録完了」と表示する一方で、実際の registered customer は即時ログイン可能な本会員として保存される。この不一致を隠して再送だけ pass にしない。次に進めるなら、entry -> mail noop/secretKey -> activation -> login の email verification branch を先に workflow 化する。
- Web+DB browser runner は 237-operation 母集団で再実行済み。matrix の 087/089 は、実フォーム/HTTP境界の evidence と readback が取れたため pass に更新した。

次に実装する場合は、admin customer edit / delivery / resend の正準 transition を Resource/OpenAPI/ALPS/HTML form で揃える。そこまで確認できない場合、残りの admin customer 保守は browser fail のまま残す。

### Admin order fulfillment

対象: 受注編集、配送先編集、追跡番号更新、対応状況変更、出荷通知メール、受注メール、出荷CSV取込。

注文は admin direct create ではなく、customer purchase flow 由来の注文を使う。2026-06-10 follow-up で Hypermedia/HTTP workflow はこの形に修正済み。

2026-06-11 限定回帰 `20260611-admin-order-browser-regression-fixed4` では、Web/HTTP 購入で作った注文を管理画面側から操作し、次を pass とした。

- `PUT /admin/order`: 受注詳細フォームから保存し、受注詳細 readback を確認。
- `POST /admin/order-status`: 受注一覧のステータス変更 affordance から送信し、対象注文の状態更新を確認。
- `PUT /admin/order/shipping-address`: 配送先フォームから保存し、`GET /admin/order/export-shipping` のCSV readbackで住所・電話番号を確認。
- `PUT /admin/order/tracking-number`: 受注一覧の追跡番号 action から送信し、JSON `{"status":"OK"}` と出荷CSV readbackを確認。
- `POST /admin/order/shipping-notify-mail`: 出荷通知メール画面のフォームから fake/noop 境界として送信。
- `POST /admin/order/send-mail`: 受注メール確認画面のフォームから fake/noop 境界として送信。
- `GET /admin/order/export-order`, `GET /admin/order/export-shipping`, `GET /admin/order/export-order-pdf`: 同一認証CookieのHTTP境界でCSV/PDF downloadを確認。

この修正で、`shipping_put.sql` / `shipping_update_tracking.sql` が HTML/browser context で複数 statement の INSERT を実行できず、配送先行が作られない問題も直した。修正は runner の判定緩和ではなく、単一 statement の upsert にして実アプリ境界で永続化できるようにしたもの。

2026-06-11 限定回帰 `20260611-admin-order-bulk-delete-browser-regression` では、Web/HTTP 購入で作った非会員注文を使い、受注一覧の bulk delete form から削除を確認した。

- HTML form: `#form_bulk` が hidden `csrfToken` と `mode=order_bulk_delete_form`、対象行 checkbox `ids[]` を出すことを確認。
- Browser/HTTP: 受注一覧画面の JS affordance が公開する `/admin/order/bulk-delete` を action として使い、同一 cookie/CSRF で `ids[]` を POST、`303 /admin/order-list` を確認。
- Readback: `GET /admin/order?orderNo=...` で対象注文の対応状況が `注文取消` になったことを確認。スクリーンショットは `docs/web-e2e/screenshots/20260611-admin-order-bulk-delete-browser-regression/setup/admin-order-bulk-delete.png`。
- Regression: `HttpSqlAdminOrderBulkDeleteFormTest` は SQL HTML context で商品作成 -> 非会員購入 -> 受注一覧 form 検出 -> bulk delete -> 受注詳細 readback を直接SQL seedなしで確認する。

未完成として残す境界:

- `POST /admin/order/create`: 2026-06-11 follow-up で、受注0件の `/admin/order-list` にも新規作成リンクを表示し、blank editor の form action を `/admin/order/create` に切り替えた。`HttpSqlAdminOrderCreateFormTest` は SQL HTML context で `GET /admin/order-list` -> `GET /admin/order/edit` -> form action POST -> `303 /admin/order?orderNo=...` -> 受注詳細 readback と、数値項目NGの `400 {"code":400,"message":"paymentMethodId"}` を確認する。限定 browser run `20260611-admin-order-create-browser-regression-fixed2` では `POST /admin/order/create` が setup operation evidence として pass、詳細画面で customerId/productCode/productName/請求金額を readback した。ただし required workflow の注文作成は引き続き storefront checkout 由来を正とし、admin direct create を業務状態seedとして使わない。
- `POST /admin/order/import-shipping`: 2026-06-11 follow-up で CSV upload 後の永続化と出荷CSV readback を Hypermedia/HTTP/browser に戻し、pass に更新した。今後の全件runで matrix と OpenAPI operation 集計を確定する。
- 注文履歴一覧/詳細: 同じ `fixed4` run で `/mypage/history` と `/mypage/order-history` が `mailHistories[0].sendDate` の形式不正 400 になった。これは `FlowCustomerPurchaseTest` に会員購入 -> admin受注メール送信 -> 会員注文履歴詳細の regression を追加して再現した。原因は MariaDB `JSON_OBJECT` が `dtb_mail_history.send_date` を `2026-06-11 06:00:00.000000` のようにマイクロ秒付きで返し、schema が EC-CUBE/Fake 観察の秒までの日時を期待していたこと。`order_history_by_order_no.sql` で `DATE_FORMAT(..., '%Y-%m-%d %H:%i:%s')` に揃え、Hypermedia/HTTP/browser で 025/026 が green に戻った。

メールは実SMTPを targetOut にし、fake/noop 境界の契約を Hypermedia/HTTP で確認できる場合だけ pass にする。

### Shop/system singleton settings

対象: 基本情報、支払方法編集/削除、配送方法、税率、定休日、特定商取引法、受注ステータス、CSV設定、マスタデータ、メンバー、権限、セキュリティ、2FA。

現在値を実フォームから読み、同じ値または明確な可逆変更だけを送る。設定を壊す可能性がある場合は targetOut にする。メンバー/ニュース/ページの 404 は、先に create flow でIDを作ってから edit/delete に進む。

#### 2026-06-11 メンバーCRUD HTMLフォーム回帰

全件run `20260611-web-db-all-routes` では、メンバー作成が `unsafe operation not executed: POST /admin/member`、詳細/編集/削除が固定ID前提の `404 final=/admin/member` として fail のままだった。原因は、Resource workflow は admin member を作れていた一方で、HTML form 境界に action/CSRF/authority/passwordConfirm/delete POST affordance が不足しており、browser/HTTP で同じ業務状態を作れなかったこと。

修正後の確認:

- HTML form: 新規作成は `/admin/member` へ POST、編集は `/admin/member?_method=put`、削除は一覧の POST form `/admin/member?loginId=...&_method=delete` を使う。各フォームは `csrfToken` と `mode=member_form` を送る。
- HTTP/SQL: `HttpSqlAdminMemberFormTest` は admin login 後にフォームからメンバーを作成し、`Location` の詳細画面で loginId/name/authority を readback、編集で name を更新、一覧の削除フォームから soft delete して `303 /admin/member-list` を確認する。
- Hypermedia/HTTP projection: `FlowAdminSystemOperationTest` は従来の admin system workflow として green。HTML form regression はこの workflow が覆っていなかった browser boundary を補う。

残す境界:

- `department`、work、2FA、password change、編集時の authority 変更は今回実装しない。現 `UpdateMemberInput` は `name` 更新のみで、doc/comment でも department は Wave 8 外とされているため、テストを通すためだけの永続化列や dummy state は追加しない。
- SQL-backed Resource suite の直接実行は `SqlFixturesTrait` 不在で PHPUnit load 前に停止する。admin member の主証跡は Hypermedia/HTTP/HTML form regression とし、SQL Resource suite restoration は別 follow-up に残す。
- `20260611-content-operation-browser-regression` では 139-142 は fail だったが、原因は runner setup の未接続と stale compiled template/cache だった。`MemberList.html.twig` の外側 wrapper を `<form>` から `<div id="form1" data-form-name="form1">` に変え、削除 form をブラウザ DOM 上の affordance として見えるようにした後、`20260611-admin-member-browser-regression-emailfix` で 139-142 は pass に更新した。

#### 2026-06-11 配送方法CRUD回帰

全件run `20260611-web-db-all-routes` では配送方法作成/編集/削除が browser fail のままだった。原因は Web+DB runner が既存ID前提の画面到達だけを見ており、配送方法を実フォームから作成して、そのIDで編集/削除する業務状態を作っていなかったこと。

修正後の確認:

- Hypermedia: `FlowAdminShopConfigurationTest` に `testConfirmsDeliveryRemoved` を追加し、`goDeliveryList` で削除後一覧を readback する。
- HTTP projection: `tests/Http/FlowAdminShopConfigurationTest.php` は同じ workflow を継承し、Resource と同じ delivery create -> update -> delete -> list readback が green。
- Browser/Web+DB: 限定run `20260611-admin-delivery-browser-regression` で 117/118/119 が pass。runner は `/admin/delivery/delivery` の HTML form action から create/update を実行し、削除は一覧行の `data-url` と削除モーダル token を使う。削除後一覧に更新後配送名が残らないことを確認する。

証跡:

- 結果JSON: `docs/web-e2e/results/20260611-admin-delivery-browser-regression.json`
- レポート: `docs/web-e2e/20260611-admin-delivery-browser-regression-report.md`
- 作成: `docs/web-e2e/screenshots/20260611-admin-delivery-browser-regression/setup/admin-delivery-maintenance-create.png`
- 編集: `docs/web-e2e/screenshots/20260611-admin-delivery-browser-regression/setup/admin-delivery-maintenance-update.png`
- 削除: `docs/web-e2e/screenshots/20260611-admin-delivery-browser-regression/setup/admin-delivery-maintenance-delete.png`

この run は `--limit=120` の限定回帰であり、121 以降の `--limit により未実行` は新規failではない。次の全件runで matrix の配送行を全件run証跡へ置き換える。

#### 2026-06-11 基本情報更新回帰

全件run `20260611-web-db-all-routes` では基本情報更新が `unsafe operation not executed: POST /admin/base-info` のままだった。Hypermedia/HTTP workflow は `doUpdateBaseInfo` を通していたが、Web+DB runner が HTML の `#shop_master_form` から POST していなかった。

修正後の確認:

- Hypermedia/HTTP: `FlowAdminShopConfigurationTest` の `testUpdatesBaseInfo` が既存の正準契約。`doUpdateBaseInfo` rel から POST し、`shopName` readback を確認する。
- Browser/Web+DB: 限定run `20260611-admin-base-info-browser-regression` で 111 が pass。runner は `#shop_master_form` の action から POST し、HTML form の実フィールド名（`shop_name`, `shop_kana`, `phone_number` など）で送る。POST後に `/admin/base-info` を再GETし、`input[name="shop_name"]` が更新値になっていることを確認する。

証跡:

- 結果JSON: `docs/web-e2e/results/20260611-admin-base-info-browser-regression.json`
- レポート: `docs/web-e2e/20260611-admin-base-info-browser-regression-report.md`
- 更新: `docs/web-e2e/screenshots/20260611-admin-base-info-browser-regression/setup/admin-base-info-update.png`

この run は `--limit=112` の限定回帰であり、113 以降の `--limit により未実行` は新規failではない。次の全件runで matrix の基本情報更新行を全件run証跡へ置き換える。

#### 2026-06-11 特定商取引法更新回帰

全件run `20260611-web-db-all-routes` では特定商取引法更新が `unsafe operation not executed: POST /admin/trade-law` のままだった。原因は、Resource/Hypermedia は `tradeLawBody` 直POSTで通っていた一方、HTML form は EC-CUBE互換の行フィールド `trade_law_1_name` / `trade_law_1_description` を送っており、HTTP/browser 境界で `tradeLawBody` に接続されていなかったこと。

修正後の確認:

- Resource/HTML: `GET /admin/trade-law` は hidden `csrfToken` と `mode=trade_law_form` を出す。`POST /admin/trade-law` は既存の `tradeLawBody` 直POSTを維持しつつ、HTML行フィールドを `name: description` 形式の本文blobへ正規化する。HTML form mode では `303 /admin/trade-law` にPRGする。
- HTTP/SQL: `HttpSqlAdminTradeLawFormTest` は admin form action/CSRF/行フィールドからPOSTし、再GETで更新した販売業者・所在地をreadbackする。
- Browser/Web+DB: 限定run `20260611-admin-trade-law-browser-regression` で 127 が pass。runner は form action `/admin/trade-law` と hidden CSRFを使い、`POST /admin/trade-law` の `operationEvidence` に 303、Location、readback、screenshot を保存した。

証跡:

- 結果JSON: `docs/web-e2e/results/20260611-admin-trade-law-browser-regression.json`
- レポート: `docs/web-e2e/20260611-admin-trade-law-browser-regression-report.md`
- 更新: `docs/web-e2e/screenshots/20260611-admin-trade-law-browser-regression/setup/admin-trade-law-update.png`

残す境界: 公開側 `/help/trade-law` は `Help/TradeLaw` resource 側に「admin-editable TradeLaw store の aggregation は TODO」と明記されている。今回の完成扱いは admin 更新フォームと管理画面 readback までであり、公開ページへの行表示連動は別 follow-up とする。

この run は `--limit=127` の限定回帰であり、128 以降の `--limit により未実行` は新規failではない。次の全件runで matrix の特定商取引法更新行を全件run証跡へ置き換える。

#### 2026-06-11 税率設定CRUD回帰

全件run `20260611-web-db-all-routes` では税率設定作成/削除が browser fail のままだった。最初の限定runでは作成POSTが400、削除が503になった。原因は Hypermedia/HTTP workflow が Resource 直叩き用の値（`taxRate` を float、`applyDate` を日付のみ）で通っており、HTMLフォームが送る `datetime-local` 値（`YYYY-MM-DDTHH:mm`）とHTML contextのDELETE後遷移を代表していなかったこと。

修正後の確認:

- Hypermedia/HTTP: `FlowAdminShopConfigurationTest` の `testCreatesTaxRule` はHTMLフォームと同じ `taxRate` 文字列、`applyDate` の `YYYY-MM-DDTHH:mm` 形式で送る。Resourceは `applyDate` を `YYYY-MM-DD HH:mm:00` に正規化し、schemaが要求する正準日時で応答する。
- Resource: `TaxRule::onDelete` は支払/配送DELETEと同じく、HTML contextでは `303 /admin/tax-rule/tax-rule-list` に戻す。Resource/HAL contextでは従来どおり削除bodyを返す。
- Browser/Web+DB: 限定run `20260611-admin-tax-rule-browser-regression-fixed2` で 121/122 が pass。runner は `#form1` の action から作成POSTを実行し、作成後一覧の `#ex-tax_rule-{id}` を確認する。削除は行の削除モーダル内リンクから `_method=delete` を実行し、削除後一覧に行が残らないことを確認する。

証跡:

- 結果JSON: `docs/web-e2e/results/20260611-admin-tax-rule-browser-regression-fixed2.json`
- レポート: `docs/web-e2e/20260611-admin-tax-rule-browser-regression-fixed2-report.md`
- 作成: `docs/web-e2e/screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-tax-rule-create.png`
- 削除: `docs/web-e2e/screenshots/20260611-admin-tax-rule-browser-regression-fixed2/setup/admin-tax-rule-delete.png`

この run は `--limit=123` の限定回帰であり、124 以降の `--limit により未実行` は新規failではない。次の全件runで matrix の税率設定行を全件run証跡へ置き換える。

### Content / filesystem / plugin boundaries

対象: キャッシュ削除、メンテナンス切替、ニュース、ページ、ブロック、レイアウト、CSS/JavaScript、テンプレート追加、プラグイン操作。

ファイルシステムや運用状態を壊す操作は restore plan がない限り pass にしない。安全に検証できるものは一時名で create -> readback -> update -> delete を行い、削除後の404または一覧非表示を確認する。

#### Unsafe operation pass gate

`POST` / `PUT` / `DELETE` を含む feature は、画面表示だけでは pass にしない。次の全てを満たした場合だけ `✔ pass` または `✔ pass（setup operation evidence）` にする。

1. 直前画面が実際に公開している HTML form action / link href / Location から unsafe URL を取得する。
2. 同一 browser context の cookie と CSRF token で HTTP 境界を送信する。
3. HTML context では 303 PRG または期待される HTTP error を確認する。
4. 成功系は最終画面で DB readback された業務状態を確認する。削除は一覧非表示または 404 を確認する。
5. screenshot と結果JSONの `operationEvidence` に method/path/status/action/readback text を残す。

これを満たせない場合は、Resource単体や hardcoded URI のテストが green でも browser feature は fail のまま残す。手札がない状態で fixture / fake / direct SQL seed / runner 内の擬似成功処理を足して pass にしない。

この gate は `WorkflowBackdoorStateCoverageTest` で runner source も検査する。特に、unsafe operation は screenshot 付き setup evidence がない限り pass にならず、runner 証跡は local browser と同一視できない network boundary を必ず report/JSON に残す。

#### Stop/fail の例

- 画面 edit form が既存行を prefill できない場合: `GET` 用の Be Input / Final / SQL read model がないなら `PUT` feature は fail。テスト側だけで既存値を合成しない。
- 破壊的ファイル操作・外部SMTP・外部決済: restore plan または fake/noop contract がない場合は targetOut または fail。実運用境界を壊して pass にしない。
- Web/HTTP で業務状態を作れない場合: 直接 SQL seed で補わず fail として残す。

#### 2026-06-11 CSS/JavaScript更新の停止判断と解消

当初、CSS更新 row 172 / JavaScript更新 row 174 は pass にしなかった。Resource と HTML form はあるが、GET body に `csrfToken` がなく、writer は request 間 readback できない in-memory 境界で、実 `public/assets/css/customize.css` / `customize.js` へは書かない状態だったため。

今回の修正で、`Css` / `Js` GET は CSRF token を返し、HTML form は `mode=content_operation_form` を送る。PUT は HTML context では 303 PRG になり、`EccubeCustomizeAssetWriter` は public asset を壊さない ignored `var/tmp/customize-assets-<DATABASE_URL hash>.json` に保存する。`HttpSqlAdminContentOperationFormTest` は `GET form -> POST _method=put -> 303 -> GET textarea readback` を CSS/JS 両方で確認した。

限定 browser run `20260611-admin-content-css-js-browser-regression-fixed` でも row 172 / 174 は `setup operation evidence` で pass。証跡は `docs/web-e2e/results/20260611-admin-content-css-js-browser-regression-fixed.json`、screenshots `docs/web-e2e/screenshots/20260611-admin-content-css-js-browser-regression-fixed/setup/admin-content-css-update.png` / `admin-content-js-update.png`。

残る境界: 実 `public/assets/css/customize.css` / `customize.js` への反映は production-cutover residual。backup/restore plan なしでは実 public asset を書き換えて pass にしない。

#### 2026-06-11 Template lifecycle の停止判断

テンプレート追加 row 176 と削除 row 178 は、`20260611-admin-template-upload-browser-regression` で pass に更新した。TemplateAdd form は `templateCode` / `templateName` / `file` を出し、Resource は `#[InputFile] FileUpload|ErrorFileUpload|null $file` を受ける。Resource/Hypermedia は BEAR manual の `FileUpload::fromFile()`、HTTP projection と SQL HTML regression は multipart/form-data で同じ upload 境界を確認する。BEAR manual: https://bearsunday.github.io/manuals/1.0/ja/resource_param.html#%E3%83%95%E3%82%A1%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%83%E3%83%97%E3%83%AD%E3%83%BC%E3%83%89%E3%81%AE%E3%83%86%E3%82%B9%E3%83%88

row 176 は upload 後にテンプレート一覧でテンプレート名と radio value を readback し、row 178 は削除後の一覧非表示を readback した。証跡は `docs/web-e2e/results/20260611-admin-template-upload-browser-regression.json` と `docs/web-e2e/screenshots/20260611-admin-template-upload-browser-regression/setup/admin-template-upload.png` / `admin-template-delete.png`。

残る row 177 有効化は pass にしない。`EccubeTemplateCompatibility::select()` は no-op で、active template の readback がない。進める条件は、select 後に一覧または設定表示で active template を確認できる projection を追加し、`HttpSqlAdminTemplateFormTest` と Web+DB runner が PUT/PRG/readback/screenshot を残すこと。

#### 2026-06-11 Plugin lifecycle の対象外判断

プラグイン操作 rows 180-183 は、Web+DB 完成判定では targetOut とする。`flow-manage-plugin` は `docs/migration-status.md` でも out of scope で、実 plugin upload/install runtime（download/unzip/composer/migrate/cache）はこの migration の対象外。HTML も install form を持たず、プラグイン行がない fresh DB では enable/disable/delete の実 affordance を作れない。

ここで runner から `POST /admin/plugin-list` を直叩きして前提 plugin 行を作ると、「テストを通すための stub registry」になる。plugin lifecycle を将来 in-scope に戻すなら、まず EC-CUBE の plugin install/search subtree に対応する正規画面、CSRF、HTML 303 PRG、readback、fake/noop 境界の仕様を追加し、その後に browser evidence を取る。

#### 2026-06-11 Cache/Maintenance HTMLフォーム回帰

全件run `20260611-web-db-all-routes` では cache clear / maintenance toggle が `unsafe operation not executed` のままだった。原因は、GET画面にCSRF tokenがなく実フォーム送信が403になり、maintenance form は `maintenance=on/off` を送る一方で Resource は `enabled` を期待していたこと。また `EccubeMaintenanceMode` は in-process singleton だけで、PHP server の複数リクエスト間 readback ができなかった。

修正後の確認:

- HTML form: `/admin/content/cache?_method=put` と `/admin/content/maintenance?_method=put` は hidden `csrfToken` と `mode=content_operation_form` を送る。Maintenance は canonical field `enabled=1/0` を送る。
- HTTP/SQL: `HttpSqlAdminContentOperationFormTest` は cache clear の 303 PRG と readback、maintenance enable -> `無効にする` readback、disable -> `有効にする` readback、CSS/JS 更新後の textarea readback を確認する。
- Boundary: maintenance state は EC-CUBE 本番 marker file ではなく、ignored `var/tmp/maintenance-mode.flag` に限定する。実本番運用ファイルへの反映は引き続き targetOut。
- Resource/Hypermedia: Resource contract は `mode` なしでは従来どおり `200 OK` の action body を返し、HTML form submit のみ `303 See Other` にする。

証跡:

- `HttpSqlAdminContentOperationFormTest` + `AdminContentSideEffectResourceTest`: green（10 tests, 130 assertions）。
- Hypermedia/HTTP `FlowAdminSystemOperationTest`: green（46 tests, 190 assertions）。
- Limited Web+DB runner `20260611-content-operation-browser-regression`: rows 154/156 は setup operation evidence で pass。スクリーンショットは `screenshots/20260611-content-operation-browser-regression/setup/admin-cache-clear.png` と `screenshots/20260611-content-operation-browser-regression/setup/admin-maintenance-enable.png`。
- 結果JSON: `docs/web-e2e/results/20260611-content-operation-browser-regression.json`
- レポート: `docs/web-e2e/20260611-content-operation-browser-regression-report.md`

#### 2026-06-11 Block create/edit/delete HTMLフォーム回帰

全件run `20260611-web-db-all-routes` ではブロック作成/編集/削除が `unsafe operation not executed` のままだった。原因は、HTML form が EC-CUBE名（`name`, `file_name`, `block_html`）で送信され、Resource が要求する canonical 名（`blockName`, `blockFileName`）と一致していなかったこと、作成 form action が collection endpoint ではなく single endpoint を向いていたこと、削除SQLが複数 statement で実DB削除を確認できなかったこと。

修正後の確認:

- HTML form: `/admin/block/block` の新規作成フォームは `action="/admin/block/block-list"`、`name="blockName"` / `name="blockFileName"` を送る。`block_html` は現Resource contractでは保存対象外なので disabled とし、schema境界へ流さない。
- Resource: `BlockList::onPost` と `Block::onDelete` は HTML context で 303 PRG を返す。`Block::onDelete` の ALPS は `doDeleteBlock` に修正した。
- SQL: `tblock_remove.sql` は `dtb_block` と `dtb_block_position` を1つの multi-table DELETE で消す。
- Read model: `GetAdminBlockInput` / `AdminBlockFetched` を追加し、既存 `BlockStorageInterface::item()` を使って `GET /admin/block/block?blockId=...` が編集フォームを prefill する。新しい fake store や runner 合成値は追加しない。
- HTTP/SQL: `HttpSqlAdminBlockFormTest` は作成 form action、canonical field、作成後 `Location` の編集フォーム prefill、PUT 303、一覧 readback、削除後一覧非表示を確認する。
- Hypermedia/HTTP projection: `FlowAdminContentPublishTest` は block create 後の `Location` を辿り、作成した blockId/name を読んでから update する。
- Browser/Web+DB: 限定run `20260611-admin-block-edit-browser-regression` で 166/167/168 が pass。167 は `GET /admin/block/block?blockId=...` の prefill を確認し、フォーム action `/admin/block/block?blockId=...&_method=put` から PUT、一覧 readback と screenshot を残した。

証跡:

- 結果JSON: `docs/web-e2e/results/20260611-admin-block-edit-browser-regression.json`
- レポート: `docs/web-e2e/20260611-admin-block-edit-browser-regression-report.md`
- 作成: `docs/web-e2e/screenshots/20260611-admin-block-edit-browser-regression/setup/admin-block-create.png`
- 編集: `docs/web-e2e/screenshots/20260611-admin-block-edit-browser-regression/setup/admin-block-update.png`
- 削除: `docs/web-e2e/screenshots/20260611-admin-block-edit-browser-regression/setup/admin-block-delete.png`

この run は `--limit=168` の限定回帰であり、169 以降の未実行 fail は新規failではない。次の全件runで matrix のブロック行を全件run証跡へ置き換える。

#### 2026-06-11 Layout edit HTMLフォーム回帰

全件run `20260611-web-db-all-routes` ではレイアウト編集が `unsafe operation not executed` のままだった。原因は、`GET /admin/layout/layout?layoutId=...` が新規レイアウト用の空フォームを描き、既存 row の prefill と `_method=put` form action を出していなかったこと。レイアウトの block-position designer は引き続き deferred 境界だが、現 ALPS/Resource contract の保存対象である `layoutName` は Web/HTTP/DB で更新できるようにした。

修正後の確認:

- Read model: `GetAdminLayoutInput` / `AdminLayoutFetched` を追加し、既存 `LayoutStorageInterface::item()` を使って `GET /admin/layout/layout?layoutId=...` が編集フォームを prefill する。新しい fake store や runner 合成値は追加していない。
- Resource/HTML: `LayoutList` は `goLayout` rel を公開し、`Layout::onGet` は `goLayout` と `doUpdateLayout` を公開する。HTML form は `action="/admin/layout/layout?layoutId=...&_method=put"` を出し、EC-CUBE由来の `name` field を `layoutName` と同じ意味として受ける。
- HTTP/SQL: `HttpSqlAdminLayoutFormTest` は一覧の編集リンク、編集フォーム prefill、PUT 303、一覧 readback を確認する。
- Hypermedia/HTTP projection: `FlowAdminContentPublishTest` は `goLayoutList -> goLayout -> doUpdateLayout` の順で辿る。
- Browser/Web+DB: 限定run `20260611-admin-layout-browser-regression` で 170 が pass。`GET /admin/layout/layout?layoutId=1` の prefill を確認し、フォーム action から PUT、`303 Location=/admin/layout/layout-list`、一覧 readback と screenshot を残した。

証跡:

- 結果JSON: `docs/web-e2e/results/20260611-admin-layout-browser-regression.json`
- レポート: `docs/web-e2e/20260611-admin-layout-browser-regression-report.md`
- 編集: `docs/web-e2e/screenshots/20260611-admin-layout-browser-regression/setup/admin-layout-update.png`

この run は `--limit=170` の限定回帰であり、171 以降の未実行 fail は新規failではない。次の全件runで matrix のレイアウト行を全件run証跡へ置き換える。

## 次の実装単位

1. Admin customer edit / delivery / resend の rel と action を Resource/OpenAPI/ALPS/HTML で照合し、保存 transition または再送対象の作成導線がないものは引き続き fail として残す。
2. `scripts/web-e2e-runner.mjs` が browser 上の実フォームから admin create/delete/order/mail unsafe 操作を実行できるように、先に対応する Hypermedia/HTTP regression と HTML form/action 観測を追加する。
3. Browser で残った CRUD fail ごとに Hypermedia/HTTP regression を追加し、赤を確認してから実装を直す。
4. LinkAudit warning を rel 単位で分類し、Resource meta / HTML / profile docs のどれが正かを確認してから failure gate 化する。
