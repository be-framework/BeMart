---
layout: default
title: "CSRF protection"
---

# CSRF protection

BeMart の mutating Resource method は、手書き guard ではなく `#[CsrfProtected]` で CSRF 必須を宣言する。

## 規約

- `onPost()` / `onPut()` / `onPatch()` / `onDelete()` は原則 `#[CsrfProtected]` を付ける。
- Resource method の引数に `csrfToken` を受け取らない。
- wire name は `csrfToken` を維持する。`_token` / `_csrf_token` alias は既存の bootstrap 正規化に任せる。
- GET 側の token 発行は Resource 本体に残す。発行用属性は導入しない。

## 実装境界

`CsrfProtectedInterceptor` は Ray AOP の `MethodInvocation` ではなく、`RequestQueryContext` に積まれた BEAR.Resource request query から token を読む。`RequestQueryCapturingInvoker` が Resource 呼び出し中だけ query を push/pop する。

`RequestQueryContext` は現在の同期 PHP request model を前提に singleton stack として束縛している。長寿命 worker や fiber/parallel 実行に移行する場合は、request-local / fiber-local な context binding へ差し替える。

## 例外変換

`RequestQueryCapturingInvoker` が ResourceObject の `code/body` に変換するのは `CsrfTokenInvalidException` のみ。他の `BadRequestException` は BEAR.Resource 側の標準処理に委ねる。
