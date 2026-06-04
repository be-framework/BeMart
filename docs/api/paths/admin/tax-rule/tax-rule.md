---
layout: default
title: "/admin/tax-rule/tax-rule"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/tax-rule/tax-rule
EC-CUBE doDeleteTaxRule — single-row endpoint (Wave 9θ).

- DELETE → doDeleteTaxRule (admin removes a tax rule — idempotent)

There is intentionally no `onPut` here: the alps.json profile has no
`doUpdateTaxRule` transition, so edits are required to flow as
delete-then-create.




## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| taxRuleId | string | 税率ルールID |  | Required |  |  |


### Response

_Not available_