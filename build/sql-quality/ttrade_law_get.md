# SQL Performance Analysis
- **SQL File:** `ttrade_law_get.sql`
- **Cost:** N/A

## SQL
```sql
SELECT
  COALESCE(
    (
      SELECT
        description
      FROM
        dtb_tradelaw
      WHERE
        id = 1
      LIMIT
        1
    ), '販売業者: 株式会社EC-CUBE
所在地: 大阪市北区梅田1-1-1
連絡先: 06-1234-5678'
  ) AS description

```

## Detected Issues


## Explain Tree
```
Message
info            No tables used
```
## Analysis Detail

### Schema
{"dtb_tradelaw":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"display_order_screen","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":0,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}}}

### EXPLAIN JSON
{"select_id":1,"message":"No tables used","optimized_away_subqueries":[{"dependent":false,"cacheable":true,"query_block":{"select_id":2,"message":"no matching row in const table"}}]}

### EXPLAIN ANALYZE
-> Rows fetched before execution  (cost=0..0 rows=1) (actual time=84e-6..125e-6 rows=1 loops=1)
-> Select #2 (subquery in projection; run only once)
    -> Zero rows (no matching row in const table)  (cost=0..0 rows=0) (actual time=41e-6..41e-6 rows=0 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。