<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/system
EC-CUBE システム情報 — Setting/System Tier-2.

Thin GET renderer for `Setting/System/system.twig`. The screen is
operational metadata rather than an ALPS domain transition, but the
body is still shaped explicitly so the HTML template does not invent
server facts.




## GET
ALPS `goSystemInfo` に対応する GET 操作。

**ALPS**: `goSystemInfo`



### Request

_No parameters required_

### Response

[Object: GET /admin/system response](../schemas/get-admin-system.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| info | array|null | システム情報一覧 - /admin/system のレスポンスで扱うシステム情報一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Optional | {"items":{"type":["object","null"],"title":"\u30b7\u30b9\u30c6\u30e0\u60c5\u5831","description":"/admin/system \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30b7\u30b9\u30c6\u30e0\u60c5\u5831\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `info` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/admin/system \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"value":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30b7\u30b9\u30c6\u30e0\u60c5\u5831\u5024","description":"/admin/system \u306e\u30b7\u30b9\u30c6\u30e0\u60c5\u5831\u884c\u306b\u8868\u793a\u3055\u308c\u308b\u5024\u3002PHP/\u74b0\u5883/\u8a2d\u5b9a\u306a\u3069\u306e\u8868\u793a\u7528\u6587\u5b57\u5217\u3002"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| phpinfoEnabled | boolean|null | PHP情報表示可否 - /admin/system の処理状態を示すPHP情報表示可否。画面表示や冪等処理結果の分岐に使う真偽値。 | Required |  |  |

#### Links

| Relation | URL |
|----------|-----|
| doAdminLogout | [<code>page://self/admin/logout</code>](/admin/logout.md) |