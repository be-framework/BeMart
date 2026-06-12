# SQL Performance Analysis
- **SQL File:** `tpayment_get.sql`
- **Cost:** 1.00

## SQL
```sql
SELECT
  CAST(id AS CHAR),
  payment_method,
  CAST(charge AS SIGNED),
  CAST(rule_min AS SIGNED),
  CAST(rule_max AS SIGNED),
  visible
FROM
  dtb_payment
WHERE
  :paymentId REGEXP '^[0-9]+$'
  AND id = CAST(:paymentId AS UNSIGNED)
LIMIT
  1

```

## Detected Issues


## Explain Tree
```
Table scan
+- Table
   table           dtb_payment
   rows            1
   filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_payment":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"payment_method","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"charge","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"rule_max","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"fixed","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"payment_image","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"rule_min","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"method_class","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_7AFF628F61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2}],"status":{"table_rows":2,"data_length":16384,"index_length":16384,"auto_increment":3,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"1.00"},"table":{"table_name":"dtb_payment","access_type":"const","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["const"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.00","eval_cost":"0.10","prefix_cost":"0.00","data_read_per_join":"4K"},"used_columns":["id","payment_method","charge","rule_max","rule_min","visible"]}}

### EXPLAIN ANALYZE
-> Limit: 1 row(s)  (cost=0..0 rows=1) (actual time=417e-6..459e-6 rows=1 loops=1)
    -> Rows fetched before execution  (cost=0..0 rows=1) (actual time=125e-6..125e-6 rows=1 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。