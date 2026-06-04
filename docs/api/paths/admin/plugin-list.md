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


### Request

_No parameters required_

### Response

_Not available_
## POST
Wave 8: every form field is admin-form input.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pluginCode | string | プラグインコード |  | Required |  |  |
| pluginName | string | プラグイン名 |  | Required |  |  |
| pluginVersion | string | プラグインバージョン |  | Required |  |  |


### Response

_Not available_