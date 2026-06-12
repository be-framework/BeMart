# SQL Performance Analysis
- **SQL File:** `ttemplate_list.sql`
- **Cost:** 0.35

## SQL
```sql
SELECT
  id,
  template_name,
  device_type_id
FROM
  dtb_template
ORDER BY
  id ASC

```

## Detected Issues


## Explain Tree
```
Sort
+- Table scan
   +- Table
      table           dtb_template
      rows            1
      filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_template":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"device_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"template_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"template_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_94C12A694FFA550E","COLUMN_NAME":"device_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":16384,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"0.35"},"ordering_operation":{"using_filesort":false,"table":{"table_name":"dtb_template","access_type":"index","key":"PRIMARY","used_key_parts":["id"],"key_length":"4","rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"3K"},"used_columns":["id","device_type_id","template_name"]}}}

### EXPLAIN ANALYZE
-> Index scan on dtb_template using PRIMARY  (cost=0.35 rows=1) (actual time=0.00167..0.00167 rows=0 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。