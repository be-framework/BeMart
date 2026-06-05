---
layout: default
title: "/mypage"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage
EC-CUBE goMypage — 会員マイページのダッシュボード.

Safe read. No CSRF (read-only). AUTHN required — Be Final raises
UnauthenticatedException when the session has no customerId, which
we map to 401. Aggregates basic profile + recent orders +
favorite count into a flat dashboard projection.

Failure mapping:
  - SemanticVariableException → 400 (parameter format invalid)
  - UnauthenticatedException  → 401 (no / stale session)

Coexists with `Resource\Page\Mypage\` namespace (Change, Favorite,
…) — PHP allows a file and a sibling directory of the same name to
share a namespace prefix.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderLimit | int |  | 5 | Optional |  |  |


### Response

_Not available_