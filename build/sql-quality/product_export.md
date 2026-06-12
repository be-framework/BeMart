# SQL Performance Analysis
- **SQL File:** `product_export.sql`
- **Cost:** 13.75

## SQL
```sql
SELECT
  b.product_code,
  b.product_name,
  b.price02,
  b.stock,
  b.product_status_id,
  b.description_detail,
  b.search_word,
  b.note,
  NULL AS image_file_name,
  JSON_ARRAY() AS category_names_json,
  JSON_ARRAY() AS tag_names_json,
  JSON_ARRAY() AS class_names_json
FROM
  (
    SELECT
      pc.id,
      pc.product_code,
      p.name AS product_name,
      pc.price02,
      pc.stock,
      p.product_status_id,
      p.description_detail,
      p.search_word,
      p.note
    FROM
      dtb_product_class pc FORCE INDEX (idx_bemart_pc_default_order)
      INNER JOIN dtb_product p ON p.id = pc.product_id
    WHERE
      pc.class_category_id1 IS NULL
      AND pc.class_category_id2 IS NULL
    ORDER BY
      pc.id ASC
    LIMIT
      :limit
    OFFSET
      :offset
  ) b

```

## Detected Issues
- フルテーブルスキャンが検出されました。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/FullTableScan)
- グループ化のために一時テーブルが必要です。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/TemporaryTableGrouping)

## Explain Tree
```
Table scan
+- Table
   table           b
   rows            100
   filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_product_class":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sale_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id1","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id2","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_duration_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock_unlimited","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"sale_limit","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price01","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price02","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_fee","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_product_class_price02_idx","COLUMN_NAME":"price02","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock_unlimited","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":500},{"INDEX_NAME":"IDX_1A11D1BA248D128","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"IDX_1A11D1BA4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_1A11D1BA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BA9B418092","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BAB0524E01","COLUMN_NAME":"sale_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BABA4269E","COLUMN_NAME":"delivery_duration_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_code","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":5,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":196608,"index_length":606208,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}},"dtb_product":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_list","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_detail","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"search_word","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"free_area","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_C49DE22F557B630","COLUMN_NAME":"product_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2},{"INDEX_NAME":"IDX_C49DE22F61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":212992,"index_length":98304,"auto_increment":2001,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"13.75"},"table":{"table_name":"b","access_type":"ALL","rows_examined_per_scan":100,"rows_produced_per_join":100,"filtered":"100.00","cost_info":{"read_cost":"3.75","eval_cost":"10.00","prefix_cost":"13.75","data_read_per_join":"205K"},"used_columns":["id","product_code","product_name","price02","stock","product_status_id","description_detail","search_word","note"],"materialized_from_subquery":{"using_temporary_table":true,"dependent":false,"cacheable":true,"query_block":{"select_id":2,"cost_info":{"query_cost":"459.00"},"ordering_operation":{"using_filesort":false,"nested_loop":[{"table":{"table_name":"pc","access_type":"ref","possible_keys":["idx_bemart_pc_default_order"],"key":"idx_bemart_pc_default_order","used_key_parts":["class_category_id1","class_category_id2"],"key_length":"10","ref":["const","const"],"rows_examined_per_scan":1000,"rows_produced_per_join":1000,"filtered":"100.00","index_condition":"(((`eccubedb_test`.`pc`.`class_category_id1` is null) and (`eccubedb_test`.`pc`.`class_category_id2` is null)) and (`eccubedb_test`.`pc`.`product_id` is not null))","cost_info":{"read_cost":"9.00","eval_cost":"100.00","prefix_cost":"109.00","data_read_per_join":"2M"},"used_columns":["id","product_id","class_category_id1","class_category_id2","product_code","stock","price02"]}},{"table":{"table_name":"p","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1000,"filtered":"100.00","cost_info":{"read_cost":"250.00","eval_cost":"100.00","prefix_cost":"459.00","data_read_per_join":"2M"},"used_columns":["id","product_status_id","name","note","description_detail","search_word"]}}]}}}}}

### EXPLAIN ANALYZE
-> Table scan on b  (cost=469..473 rows=100) (actual time=0.339..0.355 rows=100 loops=1)
    -> Materialize  (cost=469..469 rows=100) (actual time=0.338..0.338 rows=100 loops=1)
        -> Limit: 100 row(s)  (cost=459 rows=100) (actual time=0.123..0.259 rows=100 loops=1)
            -> Nested loop inner join  (cost=459 rows=1000) (actual time=0.122..0.254 rows=100 loops=1)
                -> Index lookup on pc using idx_bemart_pc_default_order (class_category_id1=NULL, class_category_id2=NULL), with index condition: (((pc.class_category_id1 is null) and (pc.class_category_id2 is null)) and (pc.product_id is not null))  (cost=109 rows=1000) (actual time=0.117..0.138 rows=100 loops=1)
                -> Single-row index lookup on p using PRIMARY (id=pc.product_id)  (cost=0.25 rows=1) (actual time=973e-6..0.001 rows=1 loops=100)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。