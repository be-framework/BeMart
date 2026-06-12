# SQL Performance Analysis
- **SQL File:** `product_class_get.sql`
- **Cost:** 0.42

## SQL
```sql
SELECT
  pc.product_code,
  p.name AS product_name,
  pc.stock,
  pc.stock_unlimited,
  pc.sale_limit,
  pc.price02,
  pc.delivery_fee,
  COALESCE(pc.sale_type_id, 1) AS sale_type_id,
  COALESCE(st.name, '通常商品') AS sale_type_name
FROM
  dtb_product_class pc
  INNER JOIN dtb_product p ON p.id = pc.product_id
  LEFT JOIN mtb_sale_type st ON st.id = pc.sale_type_id
WHERE
  pc.product_code = :productCode
  AND p.product_status_id <> 3
  AND pc.visible = 1
ORDER BY
  pc.id ASC
LIMIT
  1

```

## Detected Issues


## Explain Tree
```
Sort (using filesort)
```
## Analysis Detail

### Schema
{"dtb_product_class":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sale_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id1","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id2","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_duration_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock_unlimited","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"sale_limit","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price01","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price02","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_fee","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_product_class_price02_idx","COLUMN_NAME":"price02","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock_unlimited","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":500},{"INDEX_NAME":"IDX_1A11D1BA248D128","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"IDX_1A11D1BA4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_1A11D1BA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BA9B418092","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BAB0524E01","COLUMN_NAME":"sale_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BABA4269E","COLUMN_NAME":"delivery_duration_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_code","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":5,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":196608,"index_length":606208,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}},"dtb_product":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_list","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_detail","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"search_word","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"free_area","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_C49DE22F557B630","COLUMN_NAME":"product_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2},{"INDEX_NAME":"IDX_C49DE22F61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":212992,"index_length":98304,"auto_increment":2001,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"mtb_sale_type":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2}],"status":{"table_rows":2,"data_length":16384,"index_length":0,"auto_increment":null,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"0.42"},"ordering_operation":{"using_filesort":true,"nested_loop":[{"table":{"table_name":"pc","access_type":"ref","possible_keys":["IDX_1A11D1BA4584665A","idx_bemart_pc_code_default"],"key":"idx_bemart_pc_code_default","used_key_parts":["product_code"],"key_length":"1023","ref":["const"],"rows_examined_per_scan":1,"rows_produced_per_join":0,"filtered":"10.00","index_condition":"(`eccubedb_test`.`pc`.`product_id` is not null)","cost_info":{"read_cost":"0.25","eval_cost":"0.01","prefix_cost":"0.35","data_read_per_join":"314"},"used_columns":["id","product_id","sale_type_id","product_code","stock","stock_unlimited","sale_limit","price02","delivery_fee","visible"],"attached_condition":"(`eccubedb_test`.`pc`.`visible` = 1)"}},{"table":{"table_name":"st","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"2","ref":["eccubedb_test.pc.sale_type_id"],"rows_examined_per_scan":1,"rows_produced_per_join":0,"filtered":"100.00","cost_info":{"read_cost":"0.03","eval_cost":"0.01","prefix_cost":"0.39","data_read_per_join":"205"},"used_columns":["id","name"]}},{"table":{"table_name":"p","access_type":"eq_ref","possible_keys":["PRIMARY","IDX_C49DE22F557B630"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":0,"filtered":"100.00","cost_info":{"read_cost":"0.03","eval_cost":"0.01","prefix_cost":"0.42","data_read_per_join":"212"},"used_columns":["id","product_status_id","name"],"attached_condition":"(`eccubedb_test`.`p`.`product_status_id` <> 3)"}}]}}

### EXPLAIN ANALYZE
-> Limit: 1 row(s)  (cost=0.51 rows=1) (actual time=0.0199..0.02 rows=1 loops=1)
    -> Nested loop inner join  (cost=0.51 rows=1) (actual time=0.0193..0.0193 rows=1 loops=1)
        -> Nested loop left join  (cost=0.385 rows=1) (actual time=0.0154..0.0154 rows=1 loops=1)
            -> Sort: pc.id  (cost=0.26 rows=1) (actual time=0.0136..0.0136 rows=1 loops=1)
                -> Filter: (pc.`visible` = 1)  (cost=0.26 rows=1) (actual time=0.0103..0.011 rows=1 loops=1)
                    -> Index lookup on pc using idx_bemart_pc_code_default (product_code='CODE000001'), with index condition: (pc.product_id is not null)  (cost=0.26 rows=1) (actual time=0.00975..0.0104 rows=1 loops=1)
            -> Single-row index lookup on st using PRIMARY (id=pc.sale_type_id)  (cost=1.25 rows=1) (actual time=0.00104..0.00104 rows=0 loops=1)
        -> Filter: (p.product_status_id <> 3)  (cost=1.25 rows=1) (actual time=0.00375..0.00375 rows=1 loops=1)
            -> Single-row index lookup on p using PRIMARY (id=pc.product_id)  (cost=1.25 rows=1) (actual time=0.00342..0.00342 rows=1 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。