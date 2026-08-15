<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/tax-rule/tax-rule
EC-CUBE doDeleteTaxRule — single-row endpoint (Wave 9θ).

- DELETE → doDeleteTaxRule (admin removes a tax rule — idempotent)

There is intentionally no `onPut` here: the alps.json profile has no
`doUpdateTaxRule` transition, so edits are required to flow as
delete-then-create.




## DELETE
ALPS `doDeleteTaxRule` に対応する DELETE 操作。

**ALPS**: `doDeleteTaxRule` - 税率ルールを削除する



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| taxRuleId | string | 税率ルールID（入力） - dtb_tax_rule.id の不透明な文字列ハンドル。BeMart の TaxRuleEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_tax_rule.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTaxRuleStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TaxRuleDeleted) を踏むため、シードハンドル（`nonexistent-zzz` 等）は Fake / SQL 双方で 404 が同形 Fake観察文字長 5〜6; 観察値 'tax-10', 'tax-8'。 |  | Required | {"minLength":0,"maxLength":128,"$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} | tax-10 |


### Response

[Object: DELETE /admin/tax-rule/tax-rule response](../schemas/delete-admin-tax-rule-tax-rule.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| taxRuleId | string|null | 税率ルールID - dtb_tax_rule.id の不透明な文字列ハンドル。BeMart の TaxRuleEntity 層は数値ではなく文字列として保持する。Fake 実装は 32桁hex を生成し、SQL 実装は dtb_tax_rule.id (int unsigned AUTO_INCREMENT) を文字列化して使用（同インターフェイス・異 ID 形状）。非数値 ID は SqlTaxRuleStorage では miss として扱われ getById / put / remove のいずれも 404 経路 (TaxRuleDeleted) を踏むため、シードハンドル（`nonexistent-zzz` 等）は Fake / SQL 双方で 404 が同形 Fake観察文字長 5〜6; 観察値 'tax-10', 'tax-8'。 | Required | {"minLength":0,"maxLength":128,"pattern":"^[A-Za-z0-9._:@/-]*$","$comment":"BeMart/Fake\u5883\u754c\u3067\u89b3\u5bdf\u3055\u308c\u308b\u4e0d\u900f\u660e\u306a\u6587\u5b57\u5217ID\u3002DB\u63a1\u756a\u5024\u3068\u3057\u3066\u306e\u6570\u5024\u6f14\u7b97\u306b\u306f\u4f7f\u308f\u306a\u3044\u3002"} | tax-10 |

#### Links

| Relation | URL |
|----------|-----|
| goTaxRuleList | [<code>page://self/admin/tax-rule/tax-rule-list</code>](/admin/tax-rule/tax-rule-list.md) |
| goCalendar | [<code>page://self/admin/calendar</code>](/admin/calendar.md) |