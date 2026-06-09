---
layout: default
title: "CSRF protection"
---

# CSRF protection

BeMart の CSRF 対応は、現時点では「今すぐ実装で直すバグ」ではなく、標準境界を確認してから扱う既知負債として管理する。

## 現状

mutating Resource method には `#[CsrfProtected]` を付け、手書き guard ではなく属性で CSRF 必須を宣言している。

`CsrfProtectedInterceptor` は Ray AOP の `MethodInvocation` だけでは request query を直接参照できないため、現在は `RequestQueryCapturingInvoker` が Resource 呼び出し中だけ query を push/pop し、`RequestQueryContext` に積まれた BEAR.Resource request query から token を読む。

この `RequestQueryCapturingInvoker` / `RequestQueryContext` は既知負債である。`RequestQueryContext` は同期 PHP request model を前提に singleton stack として束縛されており、長寿命 worker や fiber/parallel 実行に移行する場合は request-local / fiber-local な context binding へ差し替える必要がある。

ただし、現状の BeMart ではこの仕組みで動いている。したがって、これは即時修正が必要な実行時バグではなく、BEAR/Ray.Csrf/BEAR.Sunday の標準境界を調べたうえで解消すべき設計負債である。

## 境界原則

CSRF は Web / HTTP boundary concern であり、Resource の意味的契約ではない。

Resource は業務操作の意味を表す。CSRF token はブラウザフォームや HTTP request の安全性を検証するための境界情報であり、商品、注文、会員といったドメイン意味を持たない。そのため、Resource method の引数、JsonSchema、ApiDoc に `csrfToken` を漏らしてはいけない。

## 不採用案

#61 の変更案のように、Resource 引数へ `csrfToken` を追加して CSRF を通す案は不採用とする。

不採用理由は次の通り。

- Resource method の意味的契約に Web boundary の token が混入する。
- JsonSchema / ApiDoc に `csrfToken` が現れ、API の意味を誤って記述する。
- Resource を HTML form 由来の呼び出しに過度に結びつける。
- BEAR.Sunday の標準的な Resource 境界を調査する前に、独自都合の引数設計を固定してしまう。

## 当面の禁止事項

標準境界を確認するまで、次を禁止する。

- Resource method 引数に `csrfToken` / `_token` / `_csrf_token` を追加する。
- JsonSchema に CSRF token 用プロパティを追加する。
- ApiDoc / OpenAPI に CSRF token を Resource 契約として記載する。
- CSRF のために Resource の業務入力名を変更する。
- `RequestQueryCapturingInvoker` / `RequestQueryContext` を別の独自 boundary 実装へ置き換える。
- #61 と同じ方向、つまり Resource 契約へ CSRF token を漏らす修正を再導入する。

## 次に調査すべき標準参照

将来対応は、次の標準参照を調べた後に行う。

1. BEAR.Skeleton の Bootstrap / Injector / AppModule における Web boundary と Resource 呼び出しの分離。
2. MyVendor.Cms の Bootstrap / Injector / AppModule / conventions における form token と Resource 境界の扱い。
3. `bear/package` の Injector / Module / Context 実装。
4. Ray.Csrf の想定する token 発行・検証境界。
5. BEAR.Sunday 標準の Transfer / Responder / Router / Resource 呼び出し経路。
6. BeMart 既存実装で、標準境界から外れている箇所と、それを残す場合の理由。

調査後に、標準実装で解けるなら標準へ寄せる。標準実装では解けない場合だけ、理由を ADR または PR 本文に明記し、最小限の独自境界として実装する。

## 例外変換

現在の実装では、`RequestQueryCapturingInvoker` が ResourceObject の `code/body` に変換するのは `CsrfTokenInvalidException` のみ。他の `BadRequestException` は BEAR.Resource 側の標準処理に委ねる。
