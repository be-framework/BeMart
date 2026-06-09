---
layout: default
title: "TaxRule"
---

# TaxRule

TaxRule は EC-CUBE の税率ルールを表す。ALPS の `TaxRule` descriptor は、`dtb_tax_rule` の全体適用ルールを BeMart の `TaxRuleEntity` として投影する。

## Meaning

`TaxRule` は admin が税率、丸め方法、適用開始日時を保守するためのルールである。購入・受注計算では、適用日時に応じて税額計算の前提になる。

## EC-CUBE Schema Projection

BeMart の `TaxRuleEntity` は次の 4 項目を保持する。

- `taxRuleId`
- `taxRate`
- `roundingType`
- `applyDate`

EC-CUBE の per-scope 列である `product_class_id` / `product_id` / `country_id` / `pref_id` と、`tax_adjust` / `creator_id` は Phase 2 スコープ外とする。書き込み時は NULL または 0 を使う。

## Migration Decisions

`tax_rate` は `decimal(10,0) unsigned` である。EC-CUBE 4.3 の schema 制約により小数部は保持できず、整数パーセントのみを扱う。

FK 5 本のうち、`rounding_type_id` は `mtb_rounding_type`、`country_id` は `mtb_country`、`pref_id` は `mtb_pref` を参照する。構造ダンプ上はマスタが空のため、テストでは NULL で seed する。

`SqlTaxRuleStorage` は `rounding_type_id` に常に NULL を書く。hydrate では `roundingType = 1` にフォールバックし、`CreateTaxRuleInput` の roundingType デフォルト 1 と一致させる。ここでの 1 は STD_ROUND、つまり四捨五入を表す。

## Date Handling

`apply_date` は MySQL `datetime` で、タイムゾーンを持たない。`SqlTaxRuleStorage` は BeMart の ISO-8601 文字列を `Y-m-d H:i:s` に直列化し、read 時に Asia/Tokyo として ISO-8601 `+09:00` に戻す。これにより Fake 投影と SQL 投影の形を一致させる。

## Persistence Behavior

`doUpdateTaxRule` 遷移は存在しない。編集は delete + create フローとし、`applyDate` の進行を監査ログ的に保存する。

## Implementation References

- Phase 2b
- `TaxRuleEntity`
- `CreateTaxRuleInput`
- `SqlTaxRuleStorage`
