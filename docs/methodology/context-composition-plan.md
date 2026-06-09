---
layout: default
title: "Context composition plan"
---

# Context composition plan

BeMart の `src/Injector.php` は現在、`APP_CONTEXT` 文字列を `match` で個別 Module に変換している。BEAR.Package 標準の `BEAR\Package\Injector` / `BEAR\Package\Module` へ戻すには、context 名を `-` 区切りの token として読み、右から左へ `*Module` を合成する形へ寄せる。

この文書は調査メモであり、PHP 実装は変更しない。CSRF は今回の対象外とする。特に CSRF token を Resource 引数へ出す案は採用しない。

## 参照した標準構成

| 対象 | 確認内容 |
|---|---|
| `/Users/akihito/git/BEAR.Skeleton/src/Injector.php` | アプリ側 `Injector` は `BEAR\Package\Injector::getInstance(__NAMESPACE__, $context, dirname(__DIR__))` へ委譲するだけ。 |
| `/Users/akihito/git/BEAR.Skeleton/src/Module/AppModule.php` | `AppModule` は `PackageModule` を install するアプリ共通 composition root。 |
| `/Users/akihito/git/MyVendor.Cms/src/Injector.php` | Skeleton と同じ委譲形。 |
| `/Users/akihito/git/MyVendor.Cms/src/Module/AppModule.php` | 実 DB / MediaQuery / JSON Schema / 認証などの production default を `AppModule` に置き、`fake-` / `test-` token で差し替える。 |
| `/Users/akihito/git/MyVendor.Cms/docs/conventions.md` | `hal-api-app`, `cli-hal-api-app`, `fake-hal-api-app`, `test-hal-api-app` を標準 context として扱い、`fake-` と `test-` は canonical prefix とする。 |
| `/Users/akihito/git/BeMart/vendor/bear/package/src/Injector.php` | `Meta` と cache を作り、`PackageInjector` へ渡す。 |
| `/Users/akihito/git/BeMart/vendor/bear/package/src/Module.php` | `explode('-', $context)` を `array_reverse()` し、`AppModule` → `ApiModule` → `HalModule` → `CliModule` / `ProdModule` のように token module を合成する。 |
| `/Users/akihito/git/BeMart/vendor/bear/package/src/Context/*.php` | 標準 token は `app`, `api`, `hal`, `cli`, `prod`。`api` は scheme、`hal` は HAL renderer、`cli` は CLI router/responder、`prod` は prod error/cache/compile を担う。 |

注: この作業ツリーには `vendor/` が無かったため、BEAR.Package の vendor 参照は `/Users/akihito/git/BeMart/vendor/bear/package/` で確認した。

## 1. 現在使われる APP_CONTEXT 一覧

`composer.json` scripts、`bin/*.php`、`public/*.php`、`tests/Http/*.php`、`tests/*` の `APP_CONTEXT` 参照と `Bootstrap::normalizeContext()` から抽出した。

| Context / alias | 主な出所 | 現在の意味 |
|---|---|---|
| `app` | `Bootstrap::normalizeContext()` escape hatch | `hal-api-app` へ正規化。 |
| `fake` | `Bootstrap::normalizeContext()` / `composer fake` entry | `fake-hal-api-app` へ正規化。 |
| `dev` | `Bootstrap::normalizeContext()` / `composer dev` entry | `dev-fake-hal-api-app` へ正規化。 |
| `test` | `Bootstrap::normalizeContext()` | `test-hal-api-app` へ正規化。 |
| `html` | `Bootstrap::normalizeContext()` | `html-hal-app` へ正規化。 |
| `html-test` | `Bootstrap::normalizeContext()` / entrypoint tests | `html-test-hal-api-app` へ正規化。 |
| `prod` | `Bootstrap::normalizeContext()` / `composer prod` entry | `prod-hal-api-app` へ正規化。 |
| `html-prod` | `Bootstrap::normalizeContext()` | `html-prod-hal-api-app` へ正規化。 |
| `hal-api-app` | `public/index.php` の cli-server default | SQL-backed JSON/HAL API。 |
| `cli-hal-api-app` | `bin/app.php`, `composer app` | CLI 版 SQL-backed API。 |
| `fake-hal-api-app` | `bin/fake.php`, `composer fake` | FakeQuery + fake service。 |
| `cli-fake-hal-api-app` | `bin/fake.php` default | CLI 版 fake context。 |
| `dev-fake-hal-api-app` | `Bootstrap` alias | Fake + dev logging。 |
| `cli-dev-fake-hal-api-app` | `bin/dev.php`, `composer dev` | CLI 版 Fake + dev logging。 |
| `test-hal-api-app` | PHPUnit resource/module tests | Fake + dev diagnostics。 |
| `cli-test-hal-api-app` | `src/Injector.php` match | CLI 版 test context。現 grep では直接使用は少ない。 |
| `admin-test-hal-api-app` | `tests/Http/admin-json-index.php` | Test + HAL + logged-in admin session。 |
| `cli-admin-test-hal-api-app` | `src/Injector.php` match | CLI 版 admin test。現 grep では直接使用は少ない。 |
| `http-test-hal-api-app` | `tests/Http/json-index.php` | HTTP workflow 用 Test + HAL。 |
| `cli-http-test-hal-api-app` | `src/Injector.php` match | CLI 版 http test。現 grep では直接使用は少ない。 |
| `http-prod-hal-api-app` | `tests/Http/prod-json-index.php` | HTTP workflow 用 Prod + HAL + PHP session auth。 |
| `cli-http-prod-hal-api-app` | `src/Injector.php` match | CLI 版 http prod test。現 grep では直接使用は少ない。 |
| `html-hal-app` | `public/page.php`, `bin/page.php`, `composer page` | SQL-backed HTML/Page。 |
| `cli-html-hal-app` | `bin/page.php` default | CLI 版 HTML/Page。 |
| `html-test-hal-api-app` | `tests/Http/index.php`, Auth tests | Test + HTML presentation。 |
| `cli-html-test-hal-api-app` | `src/Injector.php` match | CLI 版 html test。現 grep では直接使用は少ない。 |
| `prod-hal-api-app` | `bin/prod.php`, `composer prod` | Prod session/CSRF + SQL。 |
| `cli-prod-hal-api-app` | `bin/prod.php` default | CLI 版 prod API。 |
| `html-prod-hal-api-app` | `Bootstrap` alias | Prod + HTML presentation。 |
| `cli-html-prod-hal-api-app` | `src/Injector.php` match | CLI 版 html prod。現 grep では直接使用は少ない。 |
| `nope` | entrypoint negative tests | unknown context の異常系確認。 |

## 2. 現在の context → Module 対応

`src/Injector.php` の `match` は次の対応を持つ。

| Context | Module | Composition 実体 |
|---|---|---|
| `hal-api-app`, `cli-hal-api-app` | `HalApiModule` | `AppModule` + `SqlModule`。 |
| `fake-hal-api-app`, `cli-fake-hal-api-app` | `FakeModule` | `AppModule` + `FakeQueryModule` + fake service bindings。 |
| `dev-fake-hal-api-app`, `cli-dev-fake-hal-api-app` | `DevFakeHalApiModule` | `FakeModule` + `DevModule`。 |
| `admin-test-hal-api-app`, `cli-admin-test-hal-api-app` | `AdminTestModule` | `TestModule` + BEAR Package `HalModule` + admin session override。 |
| `http-prod-hal-api-app`, `cli-http-prod-hal-api-app` | `HttpProdHalTestModule` | `ProdModule` + BEAR Package `HalModule` + HTTP session adapters。 |
| `http-test-hal-api-app`, `cli-http-test-hal-api-app` | `HttpTestModule` | `TestModule` + BEAR Package `HalModule`。 |
| `test-hal-api-app`, `cli-test-hal-api-app` | `TestModule` | `FakeModule` + `DevModule`。 |
| `html-hal-app`, `cli-html-hal-app` | `HtmlHalModule` | `HalApiModule` + `HtmlModule`。 |
| `html-test-hal-api-app`, `cli-html-test-hal-api-app` | BEAR.Package context composition + test fixture `MyVendor\BeMart\Tests\Support\HtmlTestModule` | `TestModule` + `HtmlModule(debug/cache off)`。 |
| `prod-hal-api-app`, `cli-prod-hal-api-app` | `ProdModule` | `AppModule` + prod session override + prod CSRF override + `SqlModule`。 |
| `html-prod-hal-api-app`, `cli-html-prod-hal-api-app` | `HtmlProdModule` | `ProdModule` + `HtmlModule`。 |

## 3. BEAR.Package 標準 composition で置換可能なもの

BEAR.Package の `Module` は context token を次の順で合成する。

- `hal-api-app` → `AppModule` → `ApiModule` → `HalModule`
- `cli-hal-api-app` → `AppModule` → `ApiModule` → `HalModule` → `CliModule`
- `prod-hal-api-app` → `AppModule` → `ApiModule` → `HalModule` → `ProdModule`

そのまま置換できる、または置換しやすい領域は次の通り。

| 現在 | 標準 composition での候補 | 理由 |
|---|---|---|
| `src/Injector.php` の手書き `match` | `BEAR\Package\Injector::getInstance(__NAMESPACE__, $context, dirname(__DIR__))` | Skeleton / MyVendor.Cms と同じ薄い委譲形に戻せる。 |
| `HalApiModule` の `Hal` / `Api` 意味 | package `ApiModule` + `HalModule` token | context 名に含まれる `api` / `hal` は BEAR.Package が既に提供する。 |
| `cli-*` context | package `CliModule` token | CLI router/responder/http-cache は標準 token にある。 |
| `AppMetaModule` の明示 override | package `Module` の最後の `AppMetaModule` override | `new Injector(new *Module(...))` を直接使うテストを整理すれば、標準 factory 側に寄せられる。 |
| `HttpTestModule` の HAL override | `http-test-hal-api-app` を token module 化したうえで `HalModule` を標準に任せる | `hal` token は package 標準で十分。 |

注意点: BeMart の現在の `ProdModule` はアプリ固有の「prod session/CSRF/SQL」module であり、BEAR.Package の `Context\ProdModule` と同名 token で衝突する。標準 `prod` token を使うなら、アプリ固有の production 差し替えは別 token へ退避する必要がある。

## 4. token module へ分解すべきもの

手書き `match` を消すために、現在の複合 Module を token 単位へ分ける。

| token 候補 | Module 候補 | 移す内容 |
|---|---|---|
| `app` | `AppModule` | `PackageModule`、Be Framework、JSON Schema、router/error、domain default service。SQL/Fake/Dev/HTML/HTTP test 固有は置かない。 |
| `sql` または production default in `AppModule` | `SqlModule` | `MediaQueryRuntimeModule`。MyVendor.Cms と同じ方針なら production default として `AppModule` へ寄せ、fake/test で override する案もある。 |
| `fake` | `FakeModule` | Ray.FakeQuery と fake external services。MyVendor.Cms 同様 canonical prefix として維持。 |
| `test` | `TestModule` | `FakeModule` + `DevModule`、または test-only override。 |
| `dev` | `DevModule` | dev logging / semantic logger wrapper。 |
| `html` | `HtmlModule` | Twig / WebForm / HTML session adapter。 |
| `admin` | `AdminModule` or `AdminTestModule` split | logged-in admin test session。今の `AdminTestModule` は `test` + `hal` + admin を内包しているため token 化対象。 |
| `http` | `HttpModule` / `HttpProdModule` / `HttpTestModule` split | HTTP workflow fixture 用 session adapter・server bootstrap 前提。`http` が本当に DI token か、test entrypoint の env だけで足りるかは要整理。 |
| `eccube-session` / `session` | new module | `ProdSessionOverrideModule` と HTML/admin session adapter の境界。prod token 名衝突を避けるため、BEAR.Package `prod` とは分ける。 |
| `eccube-csrf` / `csrf` | existing `ProdCsrfOverrideModule` | 今回は対象外。将来 token 化する場合も Resource 引数へ出さず、DI/interceptor 境界に留める。 |

## 5. まだ残すべき / 要調査のもの

| 項目 | 判断 |
|---|---|
| `CanonicalResourceRouterModule` | AppModule 内に残すか、標準 router へ戻せるか要調査。BEAR.Package 標準へ戻す目的からは red flag なので、別 PR で router 差分の必要性を監査する。 |
| `RequestQueryCapturingInvoker` | Resource invoker override は framework boundary。標準で代替できるか要調査。 |
| `AppErrorModule` | アプリ固有 error handler として残す余地はあるが、BEAR.Package prod error handling と重なる部分を確認する。 |
| `MediaQueryRuntimeModule::queryClasses()` | 手動 class list。MyVendor.Cms は `MediaQuerySqlModule` の directory scan を使う。BeMart 側も convention scan へ寄せられるか別調査。 |
| `ProdModule` というクラス名 | BEAR.Package `Context\ProdModule` を shadow するため、標準 `prod` token 復帰時の最大の衝突点。アプリ固有 production binding を `SqlModule` / session token へ逃がす計画が必要。 |
| `HtmlProdModule` / `HttpProdHalTestModule` | prod token と HTML/HTTP test token の責務が混ざっている。小 PR で分解してから context 名を再定義する。 |
| direct `new Injector(new *Module(...))` tests | 標準 `PackageInjector` へ寄せると `AppMetaModule` 明示 override が不要になる可能性がある。先に該当テストを棚卸しする。 |
| CSRF | 今回対象外。Resource 引数化は採用しない。現在の DI/interceptor 境界のまま別論点として扱う。 |

## 6. 次の小 PR 単位

1. **Injector 委譲の足場 PR**
   `src/Injector.php` を Skeleton / MyVendor.Cms 型へ戻す前提で、現在の context と token module の対応表をテスト化する。まだ実装切替はしない。

2. **Prod token 衝突解消 PR**
   アプリ固有 `ProdModule` の責務を `SqlModule`、session module、対象外の CSRF module へ分解し、BEAR.Package `prod` token を使える名前空間に戻す。

3. **HAL/API/CLI 標準 token 採用 PR**
   `HalApiModule` / `HttpTestModule` などが内包する `HalModule` 相当を package `hal` token に任せる。`cli-*` も package `CliModule` に任せる。

4. **Fake/Test/Dev prefix 整理 PR**
   MyVendor.Cms の convention と同じく `fake-` / `test-` を canonical prefix とし、`dev-fake-` が本当に必要か、`test-` に含めるべきかを決める。

5. **HTML token PR**
   `HtmlModule` を `html` token として独立させ、`html-hal-app` / `html-test-hal-api-app` / `html-prod-hal-api-app` が BEAR.Package composition で読めることを確認する。

6. **HTTP workflow fixture PR**
   `http-*` context が DI token として必要か、`tests/Http/*-index.php` の session/bootstrap fixture だけで表現できるかを切り分ける。

7. **framework-boundary 監査 PR**
   router / invoker / MediaQuery manual list / error handler を標準参照と比較し、残す理由を ADR または methodology 文書へ記録する。

## 7. CSRF の扱い

CSRF は今回の context composition 調査の対象外とする。将来扱う場合も、token module や interceptor/adapter の DI 境界で扱い、Resource メソッド引数へ token を露出する案は採用しない。
