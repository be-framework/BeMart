<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/tax-rule/tax-rule-list
EC-CUBE goTaxRuleList + doCreateTaxRule — collection endpoint
(Wave 9θ).

- GET  → goTaxRuleList    (admin lists tax rules — safe read)
  - POST → doCreateTaxRule  (admin adds a new tax rule)

Per the alps.json profile, there is NO `doUpdateTaxRule` — edits flow
as delete + create so the applyDate audit trail remains explicit.
The single-row affordance (`doDeleteTaxRule`) lives at
`page://self/admin/tax-rule/tax-rule`.




## GET
ALPS `goTaxRuleList` に対応する GET 操作。

**ALPS**: `goTaxRuleList`



### Request

_No parameters required_

### Response

[Object: GET /admin/tax-rule/tax-rule-list response](../schemas/get-admin-tax-rule-tax-rule-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| taxRules | array|null | 税ルール一覧 - /admin/tax-rule/tax-rule-list のレスポンスで扱う税ルール一覧。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。 | Required | {"items":{"type":["object","null"],"title":"\u7a0e\u30eb\u30fc\u30eb","description":"/admin/tax-rule/tax-rule-list \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u7a0e\u30eb\u30fc\u30eb\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `taxRules` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"roundingType":{"type":["integer","null"],"minimum":1,"maximum":99,"title":"\u7aef\u6570\u51e6\u7406","description":"1=\u56db\u6368\u4e94\u5165, 2=\u5207\u308a\u6368\u3066, 3=\u5207\u308a\u4e0a\u3052\u3002\u53d7\u6ce8\u660e\u7d30\u306e\u7a0e\u984d\u8a08\u7b97\u6642\u306e\u7aef\u6570\u51e6\u7406\u65b9\u5f0f\u3002TaxRule\u3067\u8a2d\u5b9a Fake\u89b3\u5bdf\u6570\u5024 1\u301c1; \u89b3\u5bdf\u5024 '1'\u3002","example":1},"taxRate":{"type":["number","null"],"minimum":0,"maximum":100,"title":"\u7a0e\u7387","description":"\u7a0e\u30eb\u30fc\u30eb\u306b\u9069\u7528\u3059\u308b\u7a0e\u7387\u30028.0\u308410.0\u306e\u3088\u3046\u306a\u5c0f\u6570\u8868\u73fe\u3092\u542b\u3080\u767e\u5206\u7387\u3002","example":10},"taxRuleId":{"type":["string","null"],"title":"\u7a0e\u7387\u30eb\u30fc\u30ebID","description":"dtb_tax_rule.id \u306e\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217\u30cf\u30f3\u30c9\u30eb\u3002BeMart \u306e TaxRuleEntity \u5c64\u306f\u6570\u5024\u3067\u306f\u306a\u304f\u6587\u5b57\u5217\u3068\u3057\u3066\u4fdd\u6301\u3059\u308b\u3002Fake \u5b9f\u88c5\u306f 32\u6841hex \u3092\u751f\u6210\u3057\u3001SQL \u5b9f\u88c5\u306f dtb_tax_rule.id (int unsigned AUTO_INCREMENT) \u3092\u6587\u5b57\u5217\u5316\u3057\u3066\u4f7f\u7528\uff08\u540c\u30a4\u30f3\u30bf\u30fc\u30d5\u30a7\u30a4\u30b9\u30fb\u7570 ID \u5f62\u72b6\uff09\u3002\u975e\u6570\u5024 ID \u306f SqlTaxRuleStorage \u3067\u306f miss \u3068\u3057\u3066\u6271\u308f\u308c getById / put / remove \u306e\u3044\u305a\u308c\u3082 404 \u7d4c\u8def (TaxRuleDeleted) \u3092\u8e0f\u3080\u305f\u3081\u3001\u30b7\u30fc\u30c9\u30cf\u30f3\u30c9\u30eb\uff08`nonexistent-zzz` \u7b49\uff09\u306f Fake / SQL \u53cc\u65b9\u3067 404 \u304c\u540c\u5f62 Fake\u89b3\u5bdf\u6587\u5b57\u9577 5\u301c6; \u89b3\u5bdf\u5024 'tax-10', 'tax-8'\u3002","example":"tax-10","minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"},"applyDate":{"title":"\u9069\u7528\u65e5","description":"\u3053\u306e\u7a0e\u7387\u30eb\u30fc\u30eb\u304c\u6709\u52b9\u306b\u306a\u308b\u65e5\u6642\u3002\u9069\u7528\u65e5\u4ee5\u964d\u306e\u6ce8\u6587\u306b\u3053\u306e\u7a0e\u7387\u304c\u9069\u7528\u3055\u308c\u308b\u3002\u8907\u6570\u306e\u7a0e\u7387\u30eb\u30fc\u30eb\u304c\u3042\u308b\u5834\u5408\u3001\u6ce8\u6587\u65e5\u6642\u70b9\u3067\u6700\u3082\u65b0\u3057\u3044\u9069\u7528\u65e5\u306e\u30eb\u30fc\u30eb\u304c\u4f7f\u7528\u3055\u308c\u308b\u3002\u904e\u53bb\u306e\u53d7\u6ce8\u306b\u306f\u5f71\u97ff\u3057\u306a\u3044 Fake\u89b3\u5bdf\u6587\u5b57\u9577 25\u301c25; \u89b3\u5bdf\u5024 '2024-04-01T00:00:00+09:00', '2023-10-01T00:00:00+09:00'\u3002","type":"string","example":"2024-04-01T00:00:00+09:00","$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"}},"additionalProperties":false,"$comment":"\u914d\u5217\u8981\u7d20\u306fFake/Resource\u3067\u89b3\u5bdf\u3055\u308c\u305f\u65e2\u77e5property\u306b\u56fa\u5b9a\u3059\u308b\u3002\u65b0\u3057\u3044\u5217\u304c\u5fc5\u8981\u306b\u306a\u3063\u305f\u5834\u5408\u306fSemantic-Ex\u89b3\u5bdf\u306b\u8ffd\u52a0\u3057\u3066schema\u3092\u66f4\u65b0\u3059\u308b\u3002"},"minItems":0} |  |
| count | int|null | 件数 - /admin/tax-rule/tax-rule-list のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| doCreateTaxRule | [<code>page://self/admin/tax-rule/tax-rule-list</code>](/admin/tax-rule/tax-rule-list.md) |
| doDeleteTaxRule | [<code>page://self/admin/tax-rule/tax-rule</code>](/admin/tax-rule/tax-rule.md) |
## POST
ALPS `doCreateTaxRule` に対応する POST 操作。

**ALPS**: `doCreateTaxRule`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| taxRate | float | 税率 - 税ルールに適用する税率。8.0や10.0のような小数表現を含む百分率。 |  | Required | {"$comment":"\u7a0e\u7387\u306f\u672c\u6765\u6570\u5024/\u5217\u6319\u306e\u696d\u52d9\u5024\u3060\u304c\u3001HTTP\u30d5\u30a9\u30fc\u30e0\u3067\u306f\u6587\u5b57\u5217\u3068\u3057\u3066\u5c4a\u304f\u3002Resource/Semantic\u5c64\u306e400\u5fdc\u7b54\u3092\u596a\u308f\u306a\u3044\u305f\u3081transport schema\u3067\u306f\u6587\u5b57\u5217\u5165\u529b\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 10.0 |
| applyDate | string | 適用日（入力） - この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される。複数の税率ルールがある場合、注文日時点で最も新しい適用日のルールが使用される。過去の受注には影響しない Fake観察文字長 25〜25; 観察値 '2024-04-01T00:00:00+09:00', '2023-10-01T00:00:00+09:00'。 |  | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 2024-04-01T00:00:00+09:00 |
| roundingType | int | 端数処理（入力） - 1=四捨五入, 2=切り捨て, 3=切り上げ。受注明細の税額計算時の端数処理方式。TaxRuleで設定 Fake観察数値 1〜1; 観察値 '1'。 | 1 | Optional | {"default":1,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | 1 |


### Response

[Object: POST /admin/tax-rule/tax-rule-list response](../schemas/post-admin-tax-rule-tax-rule-list.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| roundingType | int|null | 端数処理 - 1=四捨五入, 2=切り捨て, 3=切り上げ。受注明細の税額計算時の端数処理方式。TaxRuleで設定 Fake観察数値 1〜1; 観察値 '1'。 | Required | {"minimum":1,"maximum":99} | 1 |
| taxRate | number|null | 税率 - 税ルールに適用する税率。8.0や10.0のような小数表現を含む百分率。 | Required | {"minimum":0,"maximum":100} | 10 |
| taxRuleId | string|null | 税率ルールID - dtb_tax_rule.id の不透明な文字列ハンドル。BeMart の TaxRuleEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_tax_rule.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTaxRuleStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TaxRuleDeleted) を踏むため、シードハンドル（`nonexistent-zzz` 等）は Fake / SQL 双方で 404 が同形 Fake観察文字長 5〜6; 観察値 'tax-10', 'tax-8'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tax-10 |
| applyDate | string | 適用日 - この税率ルールが有効になる日時。適用日以降の注文にこの税率が適用される。複数の税率ルールがある場合、注文日時点で最も新しい適用日のルールが使用される。過去の受注には影響しない Fake観察文字長 25〜25; 観察値 '2024-04-01T00:00:00+09:00', '2023-10-01T00:00:00+09:00'。 | Required | {"$comment":"\u672a\u5165\u91d1\u30fb\u672a\u767a\u9001\u30fb\u672a\u516c\u958b\u306a\u3069\u672a\u78ba\u5b9a\u65e5\u6642\u306fEC-CUBE\u5883\u754c\u3067\u7a7a\u6587\u5b57\u3068\u3057\u3066\u73fe\u308c\u308b\u305f\u3081\u3001\u65e5\u4ed8/\u65e5\u6642\u6587\u5b57\u5217\u306b\u52a0\u3048\u3066\u7a7a\u6587\u5b57\u3092\u8a31\u5bb9\u3059\u308b\u3002","pattern":"^$|\\d{4}-\\d{2}-\\d{2}([ T]\\d{2}:\\d{2}:\\d{2}([+-]\\d{2}:?\\d{2}|Z)?)?$"} | 2024-04-01T00:00:00+09:00 |

#### Links

| Relation | URL |
|----------|-----|
| goTaxRuleList | [<code>page://self/admin/tax-rule/tax-rule-list</code>](/admin/tax-rule/tax-rule-list.md) |