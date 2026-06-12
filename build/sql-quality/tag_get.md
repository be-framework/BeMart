# SQL Performance Analysis
- **SQL File:** `tag_get.sql`
- **Cost:** 1.00

## SQL
```sql
SELECT
  CAST(id AS CHAR) AS tagId,
  name AS tagName
FROM
  dtb_tag
WHERE
  :tagId REGEXP '^[0-9]+$'
  AND id = CAST(:tagId AS UNSIGNED)
LIMIT
  1

```

## Detected Issues


## Explain Tree
```
Table scan
+- Table
   table           dtb_tag
   rows            1
   filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_tag":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":20}],"status":{"table_rows":20,"data_length":16384,"index_length":0,"auto_increment":21,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"1.00"},"table":{"table_name":"dtb_tag","access_type":"const","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["const"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.00","eval_cost":"0.10","prefix_cost":"0.00","data_read_per_join":"2K"},"used_columns":["id","name"]}}

### EXPLAIN ANALYZE
-> Limit: 1 row(s)  (cost=0..0 rows=1) (actual time=459e-6..501e-6 rows=1 loops=1)
    -> Rows fetched before execution  (cost=0..0 rows=1) (actual time=125e-6..125e-6 rows=1 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。