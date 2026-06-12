# SQL Performance Analysis
- **SQL File:** `favorite_has.sql`
- **Cost:** 0.70

## SQL
```sql
SELECT
  1 AS found
FROM
  dtb_customer_favorite_product fav
  INNER JOIN dtb_product_class pc ON pc.product_id = fav.product_id
WHERE
  :customerId REGEXP '^[0-9]+$'
  AND fav.customer_id = CAST(:customerId AS UNSIGNED)
  AND pc.product_code = :productCode
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
LIMIT
  1

```

## Detected Issues


## Explain Tree
```
JOIN
+- Index lookup
|  key             IDX_ED6313839395C3F3
|  rows            1
|  filtered        100.00
|  +- Table
|     table           fav
+- Index lookup
   key             IDX_1A11D1BA4584665A
   rows            1
   filtered        5.00
   +- Table
      table           pc
```
## Analysis Detail

### Schema
{"dtb_customer_favorite_product":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"customer_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_ED6313834584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_ED6313839395C3F3","COLUMN_NAME":"customer_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":32768,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"dtb_product_class":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sale_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id1","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id2","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_duration_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock_unlimited","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"sale_limit","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price01","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price02","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_fee","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_product_class_price02_idx","COLUMN_NAME":"price02","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock_unlimited","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":500},{"INDEX_NAME":"IDX_1A11D1BA248D128","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"IDX_1A11D1BA4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_1A11D1BA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BA9B418092","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BAB0524E01","COLUMN_NAME":"sale_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BABA4269E","COLUMN_NAME":"delivery_duration_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_code","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":5,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":196608,"index_length":606208,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"0.70"},"nested_loop":[{"table":{"table_name":"fav","access_type":"ref","possible_keys":["IDX_ED6313839395C3F3","IDX_ED6313834584665A"],"key":"IDX_ED6313839395C3F3","used_key_parts":["customer_id"],"key_length":"5","ref":["const"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"1K"},"used_columns":["customer_id","product_id"],"attached_condition":"(`eccubedb_test`.`fav`.`product_id` is not null)"}},{"table":{"table_name":"pc","access_type":"ref","possible_keys":["IDX_1A11D1BA4584665A","IDX_1A11D1BA248D128","IDX_1A11D1BA9B418092","idx_bemart_pc_code_default","idx_bemart_pc_default_order"],"key":"IDX_1A11D1BA4584665A","used_key_parts":["product_id"],"key_length":"5","ref":["eccubedb_test.fav.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":0,"filtered":"5.00","cost_info":{"read_cost":"0.25","eval_cost":"0.01","prefix_cost":"0.70","data_read_per_join":"157"},"used_columns":["product_id","class_category_id1","class_category_id2","product_code"],"attached_condition":"((`eccubedb_test`.`pc`.`product_code` = 'CODE000001') and (`eccubedb_test`.`pc`.`class_category_id1` is null) and (`eccubedb_test`.`pc`.`class_category_id2` is null))"}}]}

### EXPLAIN ANALYZE
-> Limit: 1 row(s)  (cost=0.7 rows=0.05) (actual time=0.00688..0.00688 rows=0 loops=1)
    -> Nested loop inner join  (cost=0.7 rows=0.05) (actual time=0.00646..0.00646 rows=0 loops=1)
        -> Filter: (fav.product_id is not null)  (cost=0.35 rows=1) (actual time=0.00533..0.00533 rows=0 loops=1)
            -> Index lookup on fav using IDX_ED6313839395C3F3 (customer_id=cast(1 as unsigned))  (cost=0.35 rows=1) (actual time=0.00483..0.00483 rows=0 loops=1)
        -> Filter: ((pc.product_code = 'CODE000001') and (pc.class_category_id1 is null) and (pc.class_category_id2 is null))  (cost=0.255 rows=0.05) (never executed)
            -> Index lookup on pc using IDX_1A11D1BA4584665A (product_id=fav.product_id)  (cost=0.255 rows=1) (never executed)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。