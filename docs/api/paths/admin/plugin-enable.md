<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/plugin-enable
EC-CUBE doEnablePlugin — プラグインを有効化する (Wave 8).

Sub-resource of the plugin, parallel to PluginDisable. Lives at
`page://self/admin/plugin-enable`. Same `sub-resource of Plugin`
pattern as Wave 7's OrderStatus (sub-resource of Order).

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (pluginCode format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - PluginNotFoundException               → 404 (unknown pluginCode)
  - PluginNotInstalledException           → 409 (uninstalled row)

Idempotency: the Final reports `changed=false` when the plugin was
already enabled at the time of the request. ALPS `type=idempotent`
— repeats are safe.




## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pluginCode | string | プラグインコード |  | Required |  |  |


### Response

_Not available_