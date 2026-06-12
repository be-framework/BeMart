# SQL Performance Analysis
- **SQL File:** `tcategory_list.sql`
- **Cost:** 8.12

## SQL
```sql
SELECT
  c.id,
  c.category_name,
  c.parent_category_id,
  c.sort_no
FROM
  (
    SELECT
      CAST(id AS CHAR) AS id,
      category_name,
      CAST(parent_category_id AS CHAR) AS parent_category_id,
      sort_no
    FROM
      dtb_category
    ORDER BY
      sort_no ASC,
      id ASC
    LIMIT
      18446744073709551615
  ) c

```

## Detected Issues
- フルテーブルスキャンが検出されました。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/FullTableScan)
- グループ化のために一時テーブルが必要です。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/TemporaryTableGrouping)

## Explain Tree
```
Table scan
+- Table
   table           c
   rows            50
   filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_category":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"parent_category_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"category_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"hierarchy","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_5ED2C2B61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_5ED2C2B796A8F92","COLUMN_NAME":"parent_category_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":50}],"status":{"table_rows":50,"data_length":16384,"index_length":32768,"auto_increment":51,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"8.12"},"table":{"table_name":"c","access_type":"ALL","rows_examined_per_scan":50,"rows_produced_per_join":50,"filtered":"100.00","cost_info":{"read_cost":"3.12","eval_cost":"5.00","prefix_cost":"8.12","data_read_per_join":"54K"},"used_columns":["id","category_name","parent_category_id","sort_no"],"materialized_from_subquery":{"using_temporary_table":true,"dependent":false,"cacheable":true,"query_block":{"select_id":2,"cost_info":{"query_cost":"55.25"},"ordering_operation":{"using_filesort":true,"cost_info":{"sort_cost":"50.00"},"table":{"table_name":"dtb_category","access_type":"ALL","rows_examined_per_scan":50,"rows_produced_per_join":50,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"5.00","prefix_cost":"5.25","data_read_per_join":"101K"},"used_columns":["id","parent_category_id","category_name","sort_no"]}}}}}}

### EXPLAIN ANALYZE
-> Table scan on c  (cost=10.3..13.4 rows=50) (actual time=0.212..0.218 rows=50 loops=1)
    -> Materialize  (cost=10.2..10.2 rows=50) (actual time=0.211..0.211 rows=50 loops=1)
        -> Sort: dtb_category.sort_no, id  (cost=5.25 rows=50) (actual time=0.185..0.187 rows=50 loops=1)
            -> Table scan on dtb_category  (cost=5.25 rows=50) (actual time=0.00458..0.163 rows=50 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。