---
layout: default
title: "CSRF protection"
---

# CSRF protection

BeMart の CSRF 対応は [BEAR.Csrf](https://github.com/bearsunday/BEAR.Csrf)（`bear/csrf`）に全面的に委ね、BEAR.Kata の `csrf-same-origin-protection` と同じ形に揃えている。Resource の業務入力へ token を混ぜず、HTML/HTTP boundary の検証として扱うという境界原則は変わらない。

## 構成

**ゲートは属性で宣言する。** `page://` の unsafe verb（`onPost` / `onPut` / `onPatch` / `onDelete`）には `#[CsrfToken]` と `#[SameOrigin]` の両方を付ける。`app://` にはどちらも付けない（ブラウザが直接叩く面ではない）。この規則は `tests/Resource/CsrfProtectionCoverageTest.php` が全 Resource を走査して強制する。

**配線は `AppModule` の `CsrfModule` 1 行。** wire field 名は BEAR.Csrf の既定値 `_csrf_token` ではなく、テンプレートと admin JS が送る `csrfToken` を `tokenField` で指定する。token の抽出は同梱の `CompositeRequestToken`（header → resource query → post body）、検証は同梱の `StoredCsrfToken`（`issue()` / `verify()` / `clear()`）、拒否は同梱の `CsrfTokenInterceptor` / `SameOriginInterceptor` が投げる `ForbiddenException` 系例外で、`ExceptionStatusMapper` がそのまま 403 に写す。BeMart 側の interceptor や adapter は無い。

**BeMart が持つのは store だけ。** 同梱の `SessionCsrfStore` は `session_start()` を直接呼ぶため、EC-CUBE の cookie 名と開始方針を決めている `SessionStarterInterface` を迂回する。`src/Auth/SessionStarterCsrfStore.php` は同じ `CsrfStoreInterface` を `SessionStarterInterface` 経由で実装し、`AppModule` が全 context に bind する。starter は既定が `NullSessionStarter`、`HtmlModule` / `EccubeModule` が EC-CUBE の cookie を開く `CookieSessionStarter` に差し替える。どちらも積まない context（CLI、`prod-hal-app` のような素の JSON）ではトークンがリクエストをまたいで保存されないため、`#[CsrfToken]` を持つ unsafe verb は常に 403 になる。fail-closed であり、出荷している入口にそうした context はない。セッションの slot は BEAR.Csrf の既定 `CsrfSessionKey::DEFAULT_NAME`（`bear_csrf_token`）。

**同一オリジンの基準は `AllowedOriginModule`。** `AppModule` は常に `CsrfModule::withoutSameOriginCheck(tokenField: 'csrfToken')` を install し、同一オリジン検査はデフォルトで off（`AllowedOrigin` が null）にする——共有モジュールは env を読まず context 非依存のまま。ゲートを on にするのは、正準な origin を知っている context 側（`ProdModule`、hypermedia/HTTP テストハーネス）が `src/Module/AllowedOriginModule.php` を `install()`/override module として注入すること。`ProdModule` は compile 時に環境変数 `BEMART_ALLOWED_ORIGIN`（`src/Module/AllowedOriginEnv.php`）を読み、未設定・空なら `MissingAllowedOriginException` で起動を拒む。本番で沈黙が起きて same-origin gate が全リクエストで降りたままになる事故を、起動時エラーに変えている。

**prod の値はコンパイル時に焼き込まれる。** prod context は `PackageInjector` が injector を直列化して `var/tmp/<context>/` にキャッシュするため、`ProdModule` の起動拒否も `AllowedOrigin` の値も、キャッシュが空のときの環境で決まる。`BEMART_ALLOWED_ORIGIN` を変えたら `var/tmp/<context>/` を消して再コンパイルする。古い値のまま動いても fail-open にはならない（新しいオリジンからの正規リクエストが 403 になる）。

**token の受け渡しは Twig の `csrf_field()`。** `BeMartTwigExtension` が BEAR.Csrf の `CsrfHiddenInput` を Twig 関数 `csrf_field()` として公開し、POST するフォームはすべて `{{ csrf_field() }}` で `<input type="hidden" name="csrfToken" value="…">` を描画する。field 名とエスケープは `CsrfHiddenInput` が持ち、テンプレートは input を手書きしない。fetch() で送る admin のスクリプトだけが `csrf_token()` で値を受け取る。Resource の representation body に `csrfToken` は載せない（JSON 表現からも消えている）。ログイン・ログアウトの session writer は `CsrfTokenInterface::clear()` で token を廃棄し、次の描画で新しい token が発行される。

## テスト

- **Fake / SQL の in-process context** — `FakeModule` が両ゲートを降ろす。`FakeRequestToken` が `FakeCsrfToken` の token を返し、`AllowedOrigin` は null。Resource テストは `csrfToken` に触れない。ゲート自体は `tests/Resource/CsrfGateWiringTest.php` が override module で武装させて 1 か所で検証する。
- **`http` tier** — `tests/Http/html-sql-index.php` と `prod-json-index.php` から立てるサーバだけが両ゲートを本物で動かす。テストは `X-BeMart-Test-Csrf-Token` ヘッダで session slot に token を鏡写しし、`csrfToken` として送信し、ブラウザと同じ `Sec-Fetch-Site: same-origin` を付ける。token 無しと cross-site 送信が 403 になることは `tests/Http/HttpSqlCsrfGateTest.php` が確かめる。他の front controller（`index.php` など）は `FakeModule` 付きの context で起動するので、ゲートは降りている。
- **`hypermedia` tier** — token gate は本物、same-origin gate には `FakeRequestOrigin(fetchSite: 'same-origin')` で「HTTP 層が送るのと同じ申告」を与える（in-process にはリクエストヘッダが無い）。
- `phpunit.xml` の `<env>` が `BEMART_ALLOWED_ORIGIN` を立てる。値は比較されない（全リクエストが same-origin を申告する）が、無いと prod context がコンパイルできない。
- `tests/Html/CsrfTokenRenderedTest.php` が描画される全フォームの hidden field に token が入ることを確認する。

## 境界原則

CSRF は Web / HTTP boundary concern であり、Resource の意味的契約ではない。

Resource は業務操作の意味を表す。CSRF token はブラウザフォームや HTTP request の安全性を検証するための境界情報であり、商品、注文、会員といったドメイン意味を持たない。そのため、mutating Resource method の引数や request params schema に `csrfToken` を漏らしてはいけない。token を Resource body に載せる以前の許容も廃止した。token はテンプレート層が `csrf_field()` / `csrf_token()` で取る。

## 不採用案

#61 の変更案のように、Resource 引数へ `csrfToken` を追加して CSRF を通す案は不採用とする。

- Resource method の意味的契約に Web boundary の token が混入する。
- params JsonSchema / ApiDoc に `csrfToken` が現れ、API の意味を誤って記述する。
- Resource を HTML form 由来の呼び出しに過度に結びつける。
- BEAR.Sunday の標準的な Resource 境界より、独自都合の引数設計を優先してしまう。

独自の interceptor（旧 `CsrfForbiddenInterceptor`）と独自の token adapter（旧 `EccubeSharedCsrfTokenAdapter`）も廃止した。BEAR.Csrf 側に足りなかった点（session key の注入、store の分離、欠落 token の扱い）は上流で解消済みで、BeMart が差分を持つ理由が無くなった。

## 禁止事項

- Resource method 引数に `csrfToken` / `_token` / `_csrf_token` を追加する。
- request params JsonSchema / ApiDoc / OpenAPI に CSRF token を Resource 契約として記載する。
- Resource の representation body に `csrfToken` を載せる。
- `page://` の unsafe verb から `#[CsrfToken]` / `#[SameOrigin]` を外す、または `app://` に付ける。
- BEAR.Csrf の interceptor を置き換える独自 interceptor、`CsrfTokenInterface` を置き換える独自 adapter を再導入する。
- 本番/開発コンテキストで `BEAR\Csrf\SessionCsrfStore` を bind する（`session_start()` を直接呼び、`SessionStarterInterface` の境界を壊す）。
- `RequestQueryCapturingInvoker` / `RequestQueryContext` または同等の request capture stack を再導入する。
