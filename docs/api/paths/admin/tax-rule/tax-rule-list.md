---
layout: default
title: "/admin/tax-rule/tax-rule-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

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


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| taxRate | float | 適用税率 |  | Required |  |  |
| applyDate | string | 適用日 |  | Required |  |  |
| roundingType | int | 端数処理 | 1 | Optional |  |  |


### Response

_Not available_