---
layout: default
title: "/admin/plugin"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/plugin
EC-CUBE doUninstallPlugin — プラグインをアンインストールする (Wave 8).

DELETE on the Plugin sub-resource. Per ALPS the doUninstallPlugin
transition lives on `#Plugin` (the per-plugin descriptor), so it
makes sense to expose it as DELETE on a per-plugin URI. Enable /
disable are separate sub-resources (PluginEnable / PluginDisable)
to mirror the ALPS-level separation of the three idempotent
transitions.

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (pluginCode format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)

Idempotency (ALPS `type=idempotent`): unknown / already-uninstalled
plugins resolve to a 200 with `wasInstalled=false` rather than 404 —
the request itself is well-formed and the post-condition (plugin not
installed) is satisfied. Same convention as AdminCustomerDeleted's
`alreadyDeleted` (Wave 6).

STUB: the real EC-CUBE pipeline reverses migrations + deletes files
+ clears cache. Migration scope STUBS this — the storage simply
drops the row.




## DELETE
Wave 8: pluginCode is admin-form input (selected from the grid).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pluginCode | string | プラグインコード |  | Required |  |  |


### Response

_Not available_