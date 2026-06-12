# SQL Performance Analysis
- **SQL File:** `tlogin_history_list.sql`
- **Cost:** 1.35

## SQL
```sql
SELECT
  lh.create_date,
  lh.user_name AS login_id,
  (lh.login_history_status_id = 1) AS success,
  lh.client_ip
FROM
  dtb_login_history lh
ORDER BY
  lh.create_date DESC,
  lh.id DESC
LIMIT
  :limit

```

## Detected Issues
- フルテーブルスキャンが検出されました。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/FullTableScan)

## Explain Tree
```
Sort (using filesort)
sort_cost       1.00
+- Table scan
   +- Table
      table           lh
      rows            1
      filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_login_history":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"login_history_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"member_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"user_name","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"client_ip","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_6191DD4F7597D3FE","COLUMN_NAME":"member_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_6191DD4F9FA62FDD","COLUMN_NAME":"login_history_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":32768,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"1.35"},"ordering_operation":{"using_filesort":true,"cost_info":{"sort_cost":"1.00"},"table":{"table_name":"lh","access_type":"ALL","rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"1K"},"used_columns":["id","login_history_status_id","user_name","client_ip","create_date"]}}}

### EXPLAIN ANALYZE
-> Limit: 20 row(s)  (cost=0.35 rows=1) (actual time=0.00462..0.00462 rows=0 loops=1)
    -> Sort row IDs: lh.create_date DESC, lh.id DESC, limit input to 20 row(s) per chunk  (cost=0.35 rows=1) (actual time=0.00429..0.00429 rows=0 loops=1)
        -> Table scan on lh  (cost=0.35 rows=1) (actual time=0.00246..0.00246 rows=0 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。