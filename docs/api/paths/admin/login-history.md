---
layout: default
title: "/admin/login-history"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/login-history
EC-CUBE goLoginHistoryList — 管理画面ログイン履歴 (Wave 8).

Safe read. No CSRF (read-only). Admin-only — the Be Final raises
{@see \UnauthorizedAdminAccessException} when the admin session is
empty (mapped to 403).

ALPS doc: 成功/失敗・IP アドレス・User-Agent を記録. Wave 8
surfaces timestamp / loginId / success / clientIp; the User-Agent
field is Phase 2 (the fake storage seeds a sample of the four
surfaced fields only).

Failure mapping:
  - SemanticVariableException             → 400 (limit format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| limit | int |  | 50 | Optional |  |  |


### Response

_Not available_