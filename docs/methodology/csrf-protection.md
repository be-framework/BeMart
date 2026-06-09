---
layout: default
title: "CSRF protection"
---

# CSRF protection

BeMart の CSRF 対応は、Resource の業務入力へ token を混ぜず、HTML/HTTP boundary の検証として扱う。

## 現状

mutating Resource method には `#[CsrfProtected]` を付け、手書き guard ではなく属性で CSRF 必須を宣言している。

`CsrfProtectedInterceptor` は対象 `ResourceObject->uri->query[$bodyField]` から token を読む。標準の Resource invocation が request body/query を ResourceObject の URI query として保持するため、Resource method の引数へ `csrfToken` を追加する必要はない。

missing / invalid token は interceptor が `403 Forbidden` と `['message' => 'Invalid or missing CSRF token.']` に変換する。専用例外による変換は使わない。

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

## 次に調査すべき標準参照

将来 Ray.Csrf 等へ寄せる場合は、次の標準参照を調べた後に行う。

1. BEAR.Skeleton の Bootstrap / Injector / AppModule における Web boundary と Resource 呼び出しの分離。
2. MyVendor.Cms の Bootstrap / Injector / AppModule / conventions における form token と Resource 境界の扱い。
3. `bear/package` の Injector / Module / Context 実装。
4. Ray.Csrf の想定する token 発行・検証境界。
5. BEAR.Sunday 標準の Transfer / Responder / Router / Resource 呼び出し経路。
6. BeMart 既存実装で、標準境界から外れている箇所と、それを残す場合の理由。

標準実装で解けるなら標準へ寄せる。標準実装では解けない場合だけ、理由を ADR または PR 本文に明記し、最小限の独自境界として実装する。
