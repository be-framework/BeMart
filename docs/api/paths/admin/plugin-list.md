<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/plugin-list
EC-CUBE goPluginList + doInstallPlugin — プラグイン一覧 (Wave 8).

Two affordances on one URI:
  - GET  → list every plugin in the registry (goPluginList, safe)
  - POST → install a new plugin (doInstallPlugin, unsafe + CSRF)

Same URI / two verbs pattern as
{@see \MyVendor\BeMart\Resource\Page\Admin\CustomerList} (GET list,
POST does NOT live on CustomerList — but on this resource POST is
the install affordance per ALPS, which puts `doInstallPlugin` directly
on `#PluginList`).

Admin-only. Both verbs surface
UnauthorizedAdminAccessException as 403.

INSTALL STUB: see {@see \MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface}.
The real EC-CUBE install pipeline (download / unzip / composer / migrate /
cache) is STUBBED — the storage simply flips `installed=true`.

Hypermedia: GET lists every plugin and forward-declares the per-plugin
sub-resource affordances (enable / disable / uninstall).




## GET
ALPS `goPluginList` に対応する GET 操作。

**ALPS**: `goPluginList`



### Request

_No parameters required_

### Response

[Object: GET /admin/plugin-list response](../schemas/get-admin-plugin-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| plugins | array|null | プラグイン一覧 - /admin/plugin-list のレスポンスで扱うプラグイン一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30d7\u30e9\u30b0\u30a4\u30f3","description":"/admin/plugin-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30d7\u30e9\u30b0\u30a4\u30f3\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `plugins` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"pluginCode":{"type":["string","null"],"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/+-]*$","title":"\u30d7\u30e9\u30b0\u30a4\u30f3\u30b3\u30fc\u30c9","description":"\u30d7\u30e9\u30b0\u30a4\u30f3\u306e\u4e00\u610f\u8b58\u5225\u5b50\u3002dtb_plugin.code \u306b\u683c\u7d0d\u3059\u308b\u81ea\u7136\u30ad\u30fc \u2014 \u5217\u540d\u306f `code` \u3067\u3042\u3063\u3066 `plugin_code` \u3067\u306f\u306a\u3044\uff08dtb_plugin \u306f EC-CUBE \u5f8c\u767a\u306e dtb_*_code \u547d\u540d\u898f\u7d04\u3088\u308a\u53e4\u3044\uff09\u3002findByCode / install / uninstall / setEnabled \u306e\u5168\u30e9\u30a4\u30d5\u30b5\u30a4\u30af\u30eb\u30e1\u30bd\u30c3\u30c9\u304c\u3053\u306e\u5217\u3092\u30d7\u30ed\u30fc\u30d6\u3059\u308b\u3002dtb_plugin \u306f FK \u5236\u7d04\u3092\u6301\u305f\u306a\u3044\u304c structure-only \u30c0\u30f3\u30d7\u3067\u306f\u7a7a\u306e\u305f\u3081\u3001SQL \u30cf\u30a4\u30d1\u30fc\u30e1\u30c7\u30a3\u30a2\u30c6\u30b9\u30c8\u306f seedPlugins \u30672\u3064\u306e\u30c7\u30e2\u30d7\u30e9\u30b0\u30a4\u30f3\uff08Sample/SamplePlugin, Sample/DisabledPlugin\uff09\u3092\u30b7\u30fc\u30c9\u3059\u308b Fake\u89b3\u5bdf\u6587\u5b57\u9577 19\u301c21; \u89b3\u5bdf\u5024 'Sample/SamplePlugin', 'Sample/DisabledPlugin'\u3002","example":"Sample/SamplePlugin"},"installed":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"Fake\u89b3\u5bdf\u6570\u5024 1\u301c1; \u89b3\u5bdf\u5024 'true', '1'\u3002","example":"true"},"pluginName":{"type":["string","null"],"minLength":0,"maxLength":128,"title":"\u30d7\u30e9\u30b0\u30a4\u30f3\u540d","description":"\u30d7\u30e9\u30b0\u30a4\u30f3\u306e\u8868\u793a\u540d\u3002dtb_plugin.name \u306b\u683c\u7d0d\u3002PluginEntity \u306e pluginName \u306b\u5bfe\u5fdc Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c22; \u89b3\u5bdf\u5024 'Sample Plugin', 'Disabled Sample Plugin'\u3002","example":"Sample Plugin"},"enabled":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true"},"pluginVersion":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30d7\u30e9\u30b0\u30a4\u30f3\u30d0\u30fc\u30b8\u30e7\u30f3","description":"\u30d7\u30e9\u30b0\u30a4\u30f3\u306e\u30d0\u30fc\u30b8\u30e7\u30f3\u6587\u5b57\u5217\u3002dtb_plugin.version \u306b\u683c\u7d0d\u3002PluginEntity \u306e version \u306b\u5bfe\u5fdc"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/plugin-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| doEnablePlugin | [<code>page://self/admin/plugin-enable</code>](/admin/plugin-enable.md) |
| doDisablePlugin | [<code>page://self/admin/plugin-disable</code>](/admin/plugin-disable.md) |
| doUninstallPlugin | [<code>page://self/admin/plugin</code>](/admin/plugin.md) |
## POST
Wave 8: every form field is admin-form input.

**ALPS**: `doInstallPlugin`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pluginCode | string | プラグインコード（入力） - プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする Fake観察文字長 19〜21; 観察値 'Sample/SamplePlugin', 'Sample/DisabledPlugin'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Sample/SamplePlugin |
| pluginName | string | プラグイン名（入力） - プラグインの表示名。dtb_plugin.name に格納。PluginEntity の pluginName に対応 Fake観察文字長 13〜22; 観察値 'Sample Plugin', 'Disabled Sample Plugin'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | Sample Plugin |
| pluginVersion | string | プラグインバージョン（入力） - プラグインのバージョン文字列。dtb_plugin.version に格納。PluginEntity の version に対応 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: POST /admin/plugin-list response](../schemas/post-admin-plugin-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| pluginCode | string|null | プラグインコード - プラグインの一意識別子。dtb_plugin.code に格納する自然キー — 列名は `code` であって `plugin_code` ではない（dtb_plugin は EC-CUBE 後発の dtb_*_code 命名規約より古い）。findByCode / install / uninstall / setEnabled の全ライフサイクルメソッドがこの列をプローブする。dtb_plugin は FK 制約を持たないが structure-only ダンプでは空のため、SQL ハイパーメディアテストは seedPlugins で2つのデモプラグイン（Sample/SamplePlugin, Sample/DisabledPlugin）をシードする Fake観察文字長 19〜21; 観察値 'Sample/SamplePlugin', 'Sample/DisabledPlugin'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/+-]*$"} | Sample/SamplePlugin |
| alreadyInstalled | boolean|null | 既インストールフラグ - /admin/plugin-list の処理状態を示す既インストールフラグ。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |
| installed | boolean|null | 処理状態フラグ - Fake観察数値 1〜1; 観察値 'true', '1'。 | Required |  | true |
| pluginName | string|null | プラグイン名 - プラグインの表示名。dtb_plugin.name に格納。PluginEntity の pluginName に対応 Fake観察文字長 13〜22; 観察値 'Sample Plugin', 'Disabled Sample Plugin'。 | Required | {"minLength":0,"maxLength":128} | Sample Plugin |
| enabled | boolean|null | 処理状態フラグ - 観察値 'true', 'false'。 | Required |  | true |
| pluginVersion | string|null | プラグインバージョン - プラグインのバージョン文字列。dtb_plugin.version に格納。PluginEntity の version に対応 | Required | {"minLength":0,"maxLength":255} |  |

#### Links

| Relation | URL |
|----------|-----|
| goPluginList | [<code>page://self/admin/plugin-list</code>](/admin/plugin-list.md) |