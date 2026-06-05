---
layout: default
title: "CsvColumnConfig"
---

# CsvColumnConfig

CsvColumnConfig は EC-CUBE の CSV 出力列設定を表す。ALPS の `CsvColumnConfig` descriptor は、`dtb_csv` の 1 行を BeMart の `CsvColumnConfigEntity` として投影する。

## Meaning

`CsvColumnConfig` は 1 つの CSV 出力列の設定エントリである。どの列を出力に含めるか、どの順番で出力するかを保持する。

## EC-CUBE Schema Projection

BeMart の `CsvColumnConfigEntity` は次の 4 項目を保持する。

- `csvType`
- `columnName`
- `enabled`
- `sortNo`

1 つの `csvType` が複数の `dtb_csv` 行を所有する。各行が列の有効/無効と列順を表す。

## Migration Decisions

`columnName` は `dtb_csv.field_name` に対応する。

EC-CUBE の NOT NULL 列である `entity_name` / `disp_name` は Wave 9 の投影外である。`SqlCsvColumnConfigStorage` の INSERT は `field_name` をこれらの列にもエコーする。実体の値を供給する列カタログは Phase 2 の後続スコープである。

`csv_type_id` は `mtb_csv_type` への enforced FK である。structure-only ダンプではマスタが空のため、SQL テストは `seedCsvTypes` でシードする。これは `seedSaleTypes` と同じ空マスタ FK シード規約である。

## Persistence Behavior

`doUpdateCsv` は 1 つの `csvType` の列ベクタ全体を一括 POST する。storage は per-type 行集合をアトミックに置換する。

置換手順は、対象 `csvType` の全行 DELETE の後に新しい vector を INSERT する形である。savepoint 対応トランザクション内で実行し、`SqlCartCommand` と同形の扱いをする。

## Implementation References

- Phase 2b
- `CsvColumnConfigEntity`
- `SqlCsvColumnConfigStorage`
- `seedCsvTypes`
