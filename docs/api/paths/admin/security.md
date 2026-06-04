---
layout: default
title: "/admin/security"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/security
EC-CUBE セキュリティ管理 — Setting/System Tier-2.

Hard ActionRedirect completion: `onGet` renders the current settings
read through the {@see \SecurityConfigWriterInterface} boundary, and
`onPut` drives the Be `doUpdateSecurity` transition
({@see \UpdateSecurityInput} → {@see \SecuritySettingsUpdated}) — the host
allow/deny lists and trusted-hosts pattern are written behind that
boundary (config/firewall side-effect isolated).




## GET


### Request

_No parameters required_

### Response

_Not available_
## PUT
Updates the security settings (doUpdateSecurity). ALPS marks this
`idempotent` → PUT.

Failure mapping:
- Invalid CSRF                     → 403 (interceptor)
- SemanticVariableException        → 400
- UnauthorizedAdminAccessException → 403 (no admin session)



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| adminAllowHosts | string |  |  | Optional |  |  |
| adminDenyHosts | string |  |  | Optional |  |  |
| frontAllowHosts | string |  |  | Optional |  |  |
| frontDenyHosts | string |  |  | Optional |  |  |
| trustedHosts | string |  |  | Optional |  |  |


### Response

_Not available_