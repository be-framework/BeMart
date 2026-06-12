# SQL Performance Analysis
- **SQL File:** `tpage_get.sql`
- **Cost:** N/A

## SQL
```sql
SELECT
  CAST(id AS CHAR),
  page_name,
  url,
  file_name,
  CAST(edit_type AS UNSIGNED)
FROM
  dtb_page
WHERE
  :pageId REGEXP '^[0-9]+$'
  AND id = CAST(:pageId AS UNSIGNED)
LIMIT
  1

```

## Detected Issues


## Explain Tree
```
Message
info            no matching row in const table
```
## Analysis Detail

### Schema
{"dtb_page":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"master_page_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"page_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"url","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"file_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"edit_type","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"author","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"keyword","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"meta_robots","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"meta_tags","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_page_url_idx","COLUMN_NAME":"url","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_E3951A67D0618E8C","COLUMN_NAME":"master_page_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":32768,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}}}

### EXPLAIN JSON
{"select_id":1,"message":"no matching row in const table"}

### EXPLAIN ANALYZE
-> Zero rows (no matching row in const table)  (cost=0..0 rows=0) (actual time=82e-6..82e-6 rows=0 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。