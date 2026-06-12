# SQL Performance Analysis
- **SQL File:** `tclass_category_list_by_class_name.sql`
- **Cost:** 0.90

## SQL
```sql
SELECT
  CAST(id AS CHAR) AS classCategoryId,
  CAST(class_name_id AS CHAR) AS classNameId,
  name
FROM
  dtb_class_category
WHERE
  :classNameId REGEXP '^[0-9]+$'
  AND class_name_id = CAST(:classNameId AS UNSIGNED)
ORDER BY
  sort_no ASC,
  id ASC

```

## Detected Issues


## Explain Tree
```
Sort (using filesort)
+- Table scan
   +- Table
      table           dtb_class_category
      rows            4
      filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_class_category":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"class_name_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"backend_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_9B0D1DBA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_9B0D1DBAB462FB2A","COLUMN_NAME":"class_name_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":5},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":20}],"status":{"table_rows":20,"data_length":16384,"index_length":32768,"auto_increment":21,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"0.90"},"ordering_operation":{"using_filesort":true,"table":{"table_name":"dtb_class_category","access_type":"ref","possible_keys":["IDX_9B0D1DBAB462FB2A"],"key":"IDX_9B0D1DBAB462FB2A","used_key_parts":["class_name_id"],"key_length":"5","ref":["const"],"rows_examined_per_scan":4,"rows_produced_per_join":4,"filtered":"100.00","cost_info":{"read_cost":"0.50","eval_cost":"0.40","prefix_cost":"0.90","data_read_per_join":"12K"},"used_columns":["id","class_name_id","name","sort_no"]}}}

### EXPLAIN ANALYZE
-> Sort: dtb_class_category.sort_no, dtb_class_category.id  (cost=0.9 rows=4) (actual time=0.0165..0.0168 rows=4 loops=1)
    -> Index lookup on dtb_class_category using IDX_9B0D1DBAB462FB2A (class_name_id=cast(1 as unsigned))  (cost=0.9 rows=4) (actual time=0.00929..0.012 rows=4 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。