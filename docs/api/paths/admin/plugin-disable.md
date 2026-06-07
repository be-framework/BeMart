<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/plugin-disable
EC-CUBE doDisablePlugin — プラグインを無効化する (Wave 8).

Sub-resource mirror of {@see \PluginEnable}. Same failure ladder,
same idempotency convention.




## POST
ALPS `doDisablePlugin` に対応する POST 操作。

**ALPS**: `doDisablePlugin`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pluginCode | string | プラグインコード（入力） - プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする Fake観察文字長 19〜21; 観察値 'Sample/SamplePlugin', 'Sample/DisabledPlugin'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Sample/SamplePlugin |


### Response

[Object: POST /admin/plugin-disable response](../schemas/post-admin-plugin-disable.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| changed | boolean|null | 処理状態フラグ - Fake観察数値 1〜1; 観察値 '1'。 | Required |  | 1 |
| pluginCode | string|null | プラグインコード - プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする Fake観察文字長 19〜21; 観察値 'Sample/SamplePlugin', 'Sample/DisabledPlugin'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/+-]*$"} | Sample/SamplePlugin |
| enabled | boolean|null | 処理状態フラグ - 観察値 'true', 'false'。 | Required |  | true |

#### Links

| Relation | URL |
|----------|-----|
| goPluginList | [<code>page://self/admin/plugin-list</code>](/admin/plugin-list.md) |