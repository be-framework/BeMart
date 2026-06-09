---
layout: default
title: "Context composition plan"
---

# Context composition plan

この文書は、BeMart の Injector / context composition を BEAR.Package 標準へ戻すための調査メモである。PR #76 時点では、主要な計画項目は実装済みであり、ここでは現在の構成と残作業を記録する。

## 現在の方針

- `src/Injector.php` は `BEAR\Package\Injector` へ委譲する薄い wrapper に戻す。
- context 名は `-` 区切り token として扱い、`BEAR\Package\Module` の標準 composition に任せる。
- `api` segment は外す。BeMart は Page resource 前提で、HAL 表現は `hal` token で表す。
- アプリ固有の production session / CSRF binding は `EccubeModule` に集約する。
- SQL persistence は `SqlModule` を context segment として使う。
- BEAR.Package 標準 `prod` token を shadow しない。アプリ固有の旧 `ProdModule` は削除済み。

## 参照した標準構成

| 対象 | 確認内容 |
|---|---|
| `/Users/akihito/git/BEAR.Skeleton/src/Injector.php` | アプリ側 `Injector` は `BEAR\Package\Injector::getInstance(__NAMESPACE__, $context, dirname(__DIR__))` へ委譲するだけ。 |
| `/Users/akihito/git/BEAR.Skeleton/src/Module/AppModule.php` | `AppModule` は `PackageModule` を install するアプリ共通 composition root。 |
| `/Users/akihito/git/MyVendor.Cms/src/Injector.php` | Skeleton と同じ委譲形。 |
| `/Users/akihito/git/MyVendor.Cms/src/Module/AppModule.php` | 実 DB / MediaQuery / JSON Schema / 認証などの production default を `AppModule` に置き、`fake-` / `test-` token で差し替える。 |
| `/Users/akihito/git/MyVendor.Cms/docs/conventions.md` | `fake-` と `test-` は canonical prefix として扱う。 |
| `/Users/akihito/git/BeMart/vendor/bear/package/src/Injector.php` | `Meta` と cache を作り、`PackageInjector` へ渡す。 |
| `/Users/akihito/git/BeMart/vendor/bear/package/src/Module.php` | `explode('-', $context)` を `array_reverse()` し、`AppModule` から token module を合成する。 |
| `/Users/akihito/git/BeMart/vendor/bear/package/src/Context/*.php` | 標準 token は `app`, `hal`, `cli`, `prod` など。 |

## 現在使う context

| Context | 主な出所 | 意味 |
|---|---|---|
| `sql-hal-app` | `public/index.php` cli-server default | SQL-backed HAL/Page resource。 |
| `cli-sql-hal-app` | `bin/app.php` | CLI 版 SQL-backed HAL/Page resource。 |
| `prod-eccube-sql-hal-app` | `public/index.php`, `tests/Http/prod-json-index.php` | BEAR prod token + EC-CUBE session/CSRF + SQL + HAL。 |
| `cli-prod-eccube-sql-hal-app` | `bin/prod.php` | CLI 版 prod/eccube/sql/hal。 |
| `html-eccube-sql-hal-app` | `public/page.php` | HTML + EC-CUBE session/CSRF + SQL + HAL。 |
| `cli-html-eccube-sql-hal-app` | `bin/page.php` | CLI 版 HTML/Page。 |
| `fake-hal-app` | `bin/fake.php` | FakeQuery + fake external services + HAL。 |
| `cli-fake-hal-app` | `bin/fake.php` | CLI 版 fake context。 |
| `dev-fake-hal-app` | alias / direct test use | Fake + dev logging。 |
| `cli-dev-fake-hal-app` | `bin/dev.php` | CLI 版 Fake + dev logging。 |
| `test-hal-app` | PHPUnit / `tests/Http/json-index.php` | Test + Fake + dev diagnostics + HAL。 |
| `admin-test-hal-app` | `tests/Http/admin-json-index.php` | Test + HAL + logged-in admin session。 |
| `html-test-hal-app` | `tests/Http/index.php` | Test + HTML presentation。 |

## 削除済みの旧 wrapper / boundary

次の複合 wrapper module は BEAR.Package の token composition へ置き換え済みである。

- `HalApiModule`
- `HtmlHalModule`
- `HtmlProdModule`
- `HtmlTestModule`
- `HttpTestModule`
- `HttpProdHalTestModule`
- `DevFakeHalApiModule`
- 旧アプリ固有 `ProdModule`
- `ProdSessionOverrideModule`
- `ProdCsrfOverrideModule`

また、CSRF のために Resource invocation を横取りしていた `RequestQueryCapturingInvoker` / `RequestQueryContext` も削除済みである。現在の CSRF interceptor は `ResourceObject->uri->query` から token を読む。

## 現在残すもの

| 項目 | 判断 |
|---|---|
| `CanonicalResourceRouterModule` | `_method` / CLI / legacy form 互換が残るため、template 側の整理後に別 PR で削る。 |
| `DownloadResponder` | CSV/PDF/ZIP の標準 streaming 化後に別 PR で削る。 |
| `MediaQueryRuntimeModule` / `MediaQueryProxyModule` | Query discovery は `MediaQueryQueries::fromAppRoot()` に寄せ済み。runtime DB 接続 module として残す。 |
| `AppErrorModule` | アプリ固有 error handler として残す。BEAR.Package prod error handling との重複は別途確認する。 |

## 残作業

1. `CanonicalResourceRouter` を不要にするため、template / form 側の legacy compatibility を削る。
2. `DownloadResponder` を標準 streaming responder へ寄せる。
3. ActionRedirect 互換層を Resource / schema / smoke fixture / apidoc と同時に整理する。
4. BEAR.DevTools の workflow-test contract がタグリリースされたら、`bear/devtools` の dev branch 依存をタグへ戻す。
