<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

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

**ALPS**: `doUninstallPlugin`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pluginCode | string | プラグインコード（入力） - プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする Fake観察文字長 19〜21; 観察値 'Sample/SamplePlugin', 'Sample/DisabledPlugin'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Sample/SamplePlugin |


### Response

[Object: DELETE /admin/plugin response](../schemas/delete-admin-plugin.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| pluginCode | string|null | プラグインコード - プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする Fake観察文字長 19〜21; 観察値 'Sample/SamplePlugin', 'Sample/DisabledPlugin'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/+-]*$"} | Sample/SamplePlugin |
| wasInstalled | boolean|null | インストール済み結果 - /admin/plugin の処理状態を示すインストール済み結果。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |

#### Links

| Relation | URL |
|----------|-----|
| goPluginList | [<code>page://self/admin/plugin-list</code>](/admin/plugin-list.md) |