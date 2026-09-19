---
layout: default
title: "CSRF protection"
---

# CSRF protection

BeMart の CSRF 対応は、Resource の業務入力へ token を混ぜず、HTML/HTTP boundary の検証として扱う。この境界原則は Ray.Csrf 移行後も変わらない。

## 現状（Ray.Csrf 移行後）

mutating Resource method には `Ray\Csrf\Attribute\CsrfToken` を付け、手書き guard ではなく属性で CSRF 必須を宣言している（旧 `#[CsrfProtected]` から 1:1 で置き換え済み）。

token の発行・検証は `Ray\Csrf\CsrfTokenInterface`（`issue()` / `verify(string)` / `clear()`）が境界になる。旧 `MyVendor\BeMart\Be\Reason\Service\CsrfToken`（`$token` プロパティ + `isValid()`）port は削除済みで、Resource は `Ray\Csrf\CsrfTokenInterface` を直接注入され、`$this->csrf->issue()` で token を form affordance に載せる。

**セッションキーは EC-CUBE 互換を維持するため、Ray.Csrf 標準の `SessionCsrfToken` は使わない。** `SessionCsrfToken` は `ray_csrf_token` という private const のセッションキーに固定されており、設定変更もサブクラス化（`final`）もできない。EC-CUBE 側が `_csrf_token` へ Symfony Forms token を mirror する契約（Slice 8）と衝突するため、`EccubeModule` は `CsrfTokenInterface` を `EccubeSharedCsrfTokenAdapter`（`_csrf_token` を読み書き）へ直接 bind し、`SessionCsrfToken` は本番/開発いずれの実行コンテキストにも登場しない。Fake context（`FakeModule`）は同様に `Fake\Service\NullCsrfToken` / `FakeCsrfToken` を bind する。

token の wire field 名は Ray.Csrf の既定値 `_csrf_token` ではなく、既存テンプレート/JS が送る `csrfToken` を `AppModule` で `CsrfTokenField` に明示的に設定して維持している。個別の Resource が別名を使う場合は `#[CsrfToken(field: '...')]` で上書きできる（Ray.Csrf の機能そのまま）。

missing / invalid token の扱いは、`MyVendor\BeMart\Interceptor\CsrfForbiddenInterceptor` が `403 Forbidden` と `['message' => 'Invalid or missing CSRF token.']` に変換する。Ray.Csrf 標準の `Interceptor\CsrfTokenInterceptor` は例外を投げる設計だが、採用していない。理由は二つ:

1. mutating Resource の既存テストは一貫して `$ro->code` / `$ro->body['message']` を直接検証しており、`expectException()` を使う設計ではない。例外化すると呼び出し側の契約が二種類に分裂する。
2. Ray.Csrf の `CsrfTokenInterceptor` は token が **欠落している** 場合、`CsrfTokenInterface::verify()` を一切呼ばずに常に拒否する。これは `Fake\Service\NullCsrfToken`（CSRF が主題でないテスト用に「何を送っても通す」ことを契約とするフェイク）の前提と衝突する。`CsrfForbiddenInterceptor` は token 抽出には Ray.Csrf の `Http\RequestTokenInterface`（header → resource query → post の順で検索する `CompositeRequestToken`）と `Http\CsrfTokenField` をそのまま再利用しつつ、欠落時も空文字列として必ず `verify()` へ渡し、最終判断を bound された `CsrfTokenInterface` に委ねる — この「port が最終判断者」という構造は移行前の `CsrfProtectedInterceptor` と同じである。

`Ray\Csrf\Attribute\SameOrigin` / `Interceptor\SameOriginInterceptor`（Origin/Referer/`Sec-Fetch-Site` ベースの二重防御）はライブラリに同梱されているが、このリリースでは配線していない。導入は新機能の追加であり、本移行のスコープ外。

`RequestQueryCapturingInvoker` / `RequestQueryContext` は削除済みである。Resource invocation 境界を横取りして request query を singleton stack に積む独自実装は再導入しない。

## 境界原則

CSRF は Web / HTTP boundary concern であり、Resource の意味的契約ではない。

Resource は業務操作の意味を表す。CSRF token はブラウザフォームや HTTP request の安全性を検証するための境界情報であり、商品、注文、会員といったドメイン意味を持たない。そのため、mutating Resource method の引数や request params schema に `csrfToken` を漏らしてはいけない。

一方で、HTML form affordance として Resource representation body に `csrfToken` を載せることは許容する。これは「次の form submit に使う境界 token」を表現するためであり、業務入力の Semantic ではない。

## 不採用案

#61 の変更案のように、Resource 引数へ `csrfToken` を追加して CSRF を通す案は不採用とする。

不採用理由は次の通り。

- Resource method の意味的契約に Web boundary の token が混入する。
- params JsonSchema / ApiDoc に `csrfToken` が現れ、API の意味を誤って記述する。
- Resource を HTML form 由来の呼び出しに過度に結びつける。
- BEAR.Sunday の標準的な Resource 境界より、独自都合の引数設計を優先してしまう。

## 禁止事項

- Resource method 引数に `csrfToken` / `_token` / `_csrf_token` を追加する。
- request params JsonSchema に CSRF token 用プロパティを追加する。
- ApiDoc / OpenAPI に CSRF token を Resource 契約として記載する。
- CSRF のために Resource の業務入力名を変更する。
- `RequestQueryCapturingInvoker` / `RequestQueryContext` または同等の request capture stack を再導入する。
- #61 と同じ方向、つまり Resource 契約へ CSRF token を漏らす修正を再導入する。
- 本番/開発コンテキストで `Ray\Csrf\SessionCsrfToken` を bind する（EC-CUBE のセッションキー契約を壊すため）。
