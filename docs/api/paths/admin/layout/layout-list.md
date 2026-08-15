<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/layout/layout-list
EC-CUBE goLayoutList — list endpoint (Wave 9 CMS).

Layouts have no create / delete affordances per ALPS — only list and
update.




## GET
ALPS `goLayoutList` に対応する GET 操作。

**ALPS**: `goLayoutList` - レイアウト一覧を見る



### Request

_No parameters required_

### Response

[Object: GET /admin/layout/layout-list response](../schemas/get-admin-layout-layout-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| count | int|null | 件数 - /admin/layout/layout-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |
| layouts | array|null | レイアウト一覧 - /admin/layout/layout-list のレスポンスで扱うレイアウト一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u30ec\u30a4\u30a2\u30a6\u30c8","description":"/admin/layout/layout-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u30ec\u30a4\u30a2\u30a6\u30c8\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `layouts` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"deviceType":{"type":["integer","null"],"title":"\u30c7\u30d0\u30a4\u30b9\u7a2e\u5225","description":"\u30c7\u30d0\u30a4\u30b9\u7a2e\u5225\u30de\u30b9\u30bf\uff08EC-CUBE 2.x\u304b\u3089\u306e\u540d\u6b8b\uff09\u3002\u5024: 2=\u30e2\u30d0\u30a4\u30eb, 10=PC\u3002\u975e\u9023\u756a\u306eID\u306f\u65e7\u30d0\u30fc\u30b8\u30e7\u30f3\u306e\u30c7\u30d0\u30a4\u30b9\u30b5\u30dd\u30fc\u30c8\uff08\u30ac\u30e9\u30b1\u30fc\u7b49\uff09\u306b\u7531\u6765\u3002\u30da\u30fc\u30b8\u30ec\u30a4\u30a2\u30a6\u30c8\u306e\u30c7\u30d0\u30a4\u30b9\u5225\u8868\u793a\u306b\u4f7f\u7528 Fake\u89b3\u5bdf\u6570\u5024 2\u301c10; \u89b3\u5bdf\u5024 '10', '2'\u3002","example":10,"minimum":0},"layoutId":{"type":["string","null"],"title":"\u30ec\u30a4\u30a2\u30a6\u30c8ID","description":"CMS\u30ec\u30a4\u30a2\u30a6\u30c8\u3092\u8b58\u5225\u3059\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002seed \u6e08\u307f\u30ec\u30a4\u30a2\u30a6\u30c8\u3092 Fake/SQL \u540c\u578b\u306b\u6271\u3046\u3002 Fake\u89b3\u5bdf\u6587\u5b57\u9577 13\u301c13; \u89b3\u5bdf\u5024 'lo-pc-default', 'lo-sp-default'\u3002","example":"lo-pc-default","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"layoutName":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u30ec\u30a4\u30a2\u30a6\u30c8\u540d","description":"\u30ec\u30a4\u30a2\u30a6\u30c8\u306e\u8868\u793a\u540d Fake\u89b3\u5bdf\u6587\u5b57\u9577 4\u301c5; \u89b3\u5bdf\u5024 'PC\u6a19\u6e96', '\u30b9\u30de\u30db\u6a19\u6e96'\u3002","example":"PC\u6a19\u6e96"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |

#### Links

| Relation | URL |
|----------|-----|
| goLayout | [<code>page://self/admin/layout/layout{?layoutId}</code>](/admin/layout/layout.md) |
| doUpdateLayout | [<code>page://self/admin/layout/layout</code>](/admin/layout/layout.md) |