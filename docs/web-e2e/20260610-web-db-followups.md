# 20260610 Web+DB follow-ups

`20260610-web-db-all-routes` で fail として残した項目です。ここにあるものは、runner側で成功扱いにするのではなく、先に Hypermedia/HTTP workflow へ戻してから実装修正・ブラウザ再検証を行います。

## 現在の完成判定

- Feature matrix: pass 117 / fail 64 / targetOut 5
- OpenAPI operations: pass 169 / fail 65 / targetOut 3
- NG cases: pass 19 / fail 0
- PHPUnit targeted gates: `hypermedia` / `http` / Psalm は green。
- Full PHPUnit: 既存の `composer test -- --no-progress` 契約は green（1935 tests, 27375 assertions）。
- Current generated API docs: `composer doc:api` 後は 237 operations / audit gaps 0。`20260610-web-db-all-routes` は 237 operations 母集団で再実行済み。
- 20260608 で既知 fail だった `注文履歴詳細` / `再注文` は、Web購入flow由来の注文で green。
- 全 feature fail は Admin 側。内訳は `unsafe operation not executed` 55件、404 screen/action 9件。
- 2026-06-10 follow-up で、required workflow 内の admin direct `doCreateOrder` 使用は 0 件になった。注文を必要とする workflow は storefront checkout 由来の `orderNo` を使う。
- 2026-06-10 追加 follow-up で、admin customer create -> detail readback -> list search -> delete は `flow-admin-customer-maintenance` として Hypermedia/HTTP green になった。237-operation Web+DB browser runner では 087 会員作成と 089 会員削除を `Location`、一覧 readback、削除後 0件表示で pass に更新した。
- 2026-06-10 product follow-up で、admin product create/read/update/copy/bulk status/delete は Hypermedia/HTTP と Web+DB browser runner の両方で green。商品一覧 HTML には unsafe action token、`productCodes[]`、copy/delete/bulk status action を実 affordance として出し、HTML context の bulk status / delete は 303 PRG として確認した。
- 2026-06-10 category/tag follow-up で、admin category create/update/delete と tag create/delete は Hypermedia/HTTP と Web+DB browser runner の両方で green。操作URLは一覧/詳細の form action、`Location`、削除 anchor/token から取得し、カテゴリ詳細は input value readback、削除は一覧から消えたことを確認した。
- 2026-06-10 payment follow-up で、admin payment create/update/delete は Hypermedia/HTTP と Web+DB browser runner の両方で green。フォーム名は Resource の `paymentMethodName` / `ruleMin` / `ruleMax` に揃え、create/update/delete は HTML context で 303 PRG、`Location` readback、削除後一覧から消えたことを確認した。

## 止める基準

- 実フォーム、`_links`、HTML form action、`Location`、ALPS rel から操作URLを得られない unsafe 操作は実行しない。
- 業務状態を作るために直接SQL seed、fixture boundary、runner専用の直POST bodyを追加しない。
- 画面到達だけで副作用を pass にしない。作成・更新・削除は readback 画面または別ロール画面で postcondition を確認する。
- 外部決済、実SMTP、本番ファイル破壊、実プラグインインストールなどは targetOut とし、fake/noop 境界を確認できる場合だけ別途 pass にする。
- テストを通すためだけの ResourceObject wrapper、URI route mapper、固定dummy fixture、DBリセット、強制ID採番補正は追加しない。根拠が不足している場合は fail/follow-up として残す。

## 自信がない/証拠不足として残す箇所

次の項目は、現時点で「作れば通る」実装をしてはいけない。標準の Hypermedia/HTTP workflow に戻せる証拠を集められない場合は、fail または targetOut として残して止める。

- Admin unsafe operation 55件は、画面到達だけでは成功扱いにしない。実フォームまたは表現上の action/link から payload を復元できるまで、runner 専用の直POST/PUT/DELETEは作らない。
- `POST /admin/delete-customer` の HTML 境界 503 は修正済み。対応では `HttpAdminCustomerDeleteHtmlTest` を先に追加し、HTML context では 303 `/admin/customer-list` へ PRG する契約として確認した。以後も template missing を隠すだけの ResourceObject wrapper や runner 側成功扱いは追加しない。
- Admin 404 9件は、固定IDを仮定しない。create flow 由来のIDで edit/delete/readback へ進める workflow ができるまで、404をマスタデータ不足として握りつぶさない。
- SQL-backed Resource suite は、履歴・schema・fixture helper の整合が取れるまで復元しない。`SqlFixturesTrait` の空実装、固定dummy値、NOT NULL/FK を満たすだけの最小行投入はしない。
- HTTP projection で Resource workflow と違う挙動が出た場合は、HTTP 側を正として扱う。ただし HTTP を通すために test context や session/fake state を追加する前に、HTML form/CSRF/Cookie/redirect の観測結果を記録する。
- Noop/fake 境界は、境界契約が表現またはテストで観測できる場合だけ pass にする。実SMTP、実決済、破壊的ファイル操作は、観測不能なまま成功扱いにしない。
- Raw `vendor/bin/phpunit --no-progress` は、Composer script が指定する 512MB memory limit を通らないため TCPDF PDF export で停止する。これは project test contract の差として扱い、調査前に skip や軽量 renderer で隠さない。
- `BEAR.Dev.HtmlLinkAudit` warning は full suite green でも大量に残る。warning を成功扱いから消す前に、Resource meta の target 判定と HTML/profile 表現のどちらが正かを確認する。

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
- `flow-admin-customer-maintenance` を追加し、admin customer list の `doCreateCustomer` rel から作成、`Location` の詳細 readback、`goCustomerList` での検索 readback、`doDeleteCustomer` rel での削除、削除後 list return まで確認した。Hypermedia/HTTP targeted はどちらも green（各 6 tests, 41 assertions; combined 12 tests, 82 assertions）。browser runner では `CreateCustomer` の `Location: /admin/customer?email=...` と一覧行から会員IDを観測して 087 を pass にし、`POST /admin/delete-customer` は HTML context の PRG 303 と削除後 0件 readback で 089 を pass にした。
- `CustomerList` の `doCreateCustomer` rel を実 Resource/OpenAPI と同じ `page://self/admin/create-customer` に直し、`doDeleteCustomer` / `doResendActivationMail` rel も Resource 表現に追加した。HTTP projection では stale compiled context が古い link を返したため、`var/tmp/*eccube-sql-hal-app` を消して再実行し、source と runtime の不一致であることを確認した。

現時点で targeted Hypermedia/HTTP suite reliability と Composer full suite の未解決項目はない。残る follow-up は Web+DB browser/HTTP 側の未実行 Admin unsafe operations 55件、Admin 404 9件、SQL-backed Resource suite restoration、HTML link audit warning の整理。

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

商品作成、詳細 readback、編集、コピー、一括公開状態変更、削除は 2026-06-10 product follow-up で green。残る商品系は CSV取込と商品規格編集。

必要な順序は create -> readback -> update -> readback -> delete -> 404。カテゴリ編集のような 404 は、先に作成flow由来のIDを使う Hypermedia/HTTP regression を追加する。CSV取込はブラウザでフォームとCSRFを取得し、アップロード後に一覧/readbackで結果を確認できる形にする。

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
- `ResendActivationMail` は Resource/OpenAPI/ALPS があるが、admin create と public entry はどちらも `status=2` の有効会員を作る。再送対象の仮登録会員（`customerStatus=1` かつ `secretKey`あり）を Web/HTTP 遷移で作る導線がまだない。直接DBで provisional customer を作らない。
- Web+DB browser runner は 237-operation 母集団で再実行済み。matrix の 087/089 は、実フォーム/HTTP境界の evidence と readback が取れたため pass に更新した。

次に実装する場合は、admin customer edit / delivery / resend の正準 transition を Resource/OpenAPI/ALPS/HTML form で揃える。そこまで確認できない場合、残りの admin customer 保守は browser fail のまま残す。

### Admin order fulfillment

対象: 受注編集、配送先編集、追跡番号更新、対応状況変更、出荷通知メール、受注メール、出荷CSV取込。

注文は admin direct create ではなく、customer purchase flow 由来の注文を使う。2026-06-10 follow-up で Hypermedia/HTTP workflow はこの形に修正済み。Web+DB browser runner の unsafe operation 結果はまだ再実行していないため、matrix の browser fail は次回 run で閉じる。
メールは実SMTPを targetOut にし、fake/noop 境界の契約を Hypermedia/HTTP で確認できる場合だけ pass にする。

### Shop/system singleton settings

対象: 基本情報、支払方法編集/削除、配送方法、税率、定休日、特定商取引法、受注ステータス、CSV設定、マスタデータ、メンバー、権限、セキュリティ、2FA。

現在値を実フォームから読み、同じ値または明確な可逆変更だけを送る。設定を壊す可能性がある場合は targetOut にする。メンバー/ニュース/ページの 404 は、先に create flow でIDを作ってから edit/delete に進む。

### Content / filesystem / plugin boundaries

対象: キャッシュ削除、メンテナンス切替、ニュース、ページ、ブロック、レイアウト、CSS/JavaScript、テンプレート追加、プラグイン操作。

ファイルシステムや運用状態を壊す操作は restore plan がない限り pass にしない。安全に検証できるものは一時名で create -> readback -> update -> delete を行い、削除後の404または一覧非表示を確認する。

## 次の実装単位

1. Admin customer edit / delivery / resend の rel と action を Resource/OpenAPI/ALPS/HTML で照合し、保存 transition または再送対象の作成導線がないものは引き続き fail として残す。
2. `scripts/web-e2e-runner.mjs` が browser 上の実フォームから admin create/delete/order/mail unsafe 操作を実行できるように、先に対応する Hypermedia/HTTP regression と HTML form/action 観測を追加する。
3. Browser で残った CRUD fail ごとに Hypermedia/HTTP regression を追加し、赤を確認してから実装を直す。
4. LinkAudit warning を rel 単位で分類し、Resource meta / HTML / profile docs のどれが正かを確認してから failure gate 化する。
