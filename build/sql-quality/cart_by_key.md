# SQL Performance Analysis
- **SQL File:** `cart_by_key.sql`
- **Cost:** 0.70

## SQL
```sql
SELECT
  c.cart_key,
  c.sale_type_id,
  st.name AS sale_type_name,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            JSON_OBJECT(
              'productCode',
              pc.product_code,
              'quantity',
              ci.quantity,
              'price',
              ci.price,
              'productClassId',
              pc.id,
              'productId',
              p.id,
              'productName',
              p.name,
              'mainImage',
              (
                SELECT
                  pi.file_name
                FROM
                  dtb_product_image pi
                WHERE
                  pi.product_id = p.id
                ORDER BY
                  pi.sort_no ASC,
                  pi.id ASC
                LIMIT
                  1
              ), 'classCategoryName1',
              cc1.name,
              'className1',
              cn1.name,
              'classCategoryName2',
              cc2.name,
              'className2',
              cn2.name
            )
            ORDER BY
              ci.id ASC SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      dtb_cart_item ci
      INNER JOIN dtb_product_class pc ON pc.id = ci.product_class_id
      INNER JOIN dtb_product p ON p.id = pc.product_id
      LEFT JOIN dtb_class_category cc1 ON cc1.id = pc.class_category_id1
      LEFT JOIN dtb_class_name cn1 ON cn1.id = cc1.class_name_id
      LEFT JOIN dtb_class_category cc2 ON cc2.id = pc.class_category_id2
      LEFT JOIN dtb_class_name cn2 ON cn2.id = cc2.class_name_id
    WHERE
      ci.cart_id = c.id
  ) AS items_json,
  c.total_price,
  c.delivery_fee_total,
  c.pre_order_id
FROM
  (
    SELECT
      cart.*,
      CAST(
        SUBSTRING_INDEX(cart.cart_key, '_', -1) AS UNSIGNED
      ) AS sale_type_id
    FROM
      dtb_cart cart
    WHERE
      cart.cart_key = :cartKey
  ) c
  LEFT JOIN mtb_sale_type st ON st.id = c.sale_type_id
LIMIT
  1

```

## Detected Issues
- フルテーブルスキャンが検出されました。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/FullTableScan)

## Explain Tree
```
JOIN
+- Table scan
   rows            1
   +- Table
      table           cart
      condition       (`eccubedb_test`.`cart`.`cart_key` = 'sample-session_1')
```
## Analysis Detail

### Schema
{"dtb_product_image":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"file_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_3267CC7A4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_3267CC7A61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pi_product_sort","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pi_product_sort","COLUMN_NAME":"sort_no","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pi_product_sort","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":163840,"index_length":180224,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}},"dtb_cart_item":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_class_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"cart_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"quantity","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_B0228F741AD5CDBF","COLUMN_NAME":"cart_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_B0228F7421B06187","COLUMN_NAME":"product_class_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":32768,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"dtb_product_class":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sale_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id1","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id2","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_duration_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock_unlimited","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"sale_limit","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price01","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price02","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_fee","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_product_class_price02_idx","COLUMN_NAME":"price02","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock_unlimited","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":500},{"INDEX_NAME":"IDX_1A11D1BA248D128","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"IDX_1A11D1BA4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_1A11D1BA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BA9B418092","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BAB0524E01","COLUMN_NAME":"sale_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BABA4269E","COLUMN_NAME":"delivery_duration_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_code","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":5,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":196608,"index_length":606208,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}},"dtb_product":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_list","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_detail","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"search_word","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"free_area","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_C49DE22F557B630","COLUMN_NAME":"product_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2},{"INDEX_NAME":"IDX_C49DE22F61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":212992,"index_length":98304,"auto_increment":2001,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_class_category":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"class_name_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"backend_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_9B0D1DBA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_9B0D1DBAB462FB2A","COLUMN_NAME":"class_name_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":5},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":20}],"status":{"table_rows":20,"data_length":16384,"index_length":32768,"auto_increment":21,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_class_name":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"backend_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_187C95AD61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":5}],"status":{"table_rows":5,"data_length":16384,"index_length":16384,"auto_increment":6,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_cart":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"customer_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"cart_key","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pre_order_id","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"UNI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"total_price","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"delivery_fee_total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"add_point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"use_point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_cart_pre_order_id_idx","COLUMN_NAME":"pre_order_id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_cart_update_date_idx","COLUMN_NAME":"update_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_FC3C24F09395C3F3","COLUMN_NAME":"customer_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":49152,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"mtb_sale_type":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2}],"status":{"table_rows":2,"data_length":16384,"index_length":0,"auto_increment":null,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"0.70"},"nested_loop":[{"table":{"table_name":"cart","access_type":"ALL","rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"3K"},"used_columns":["id","cart_key","pre_order_id","total_price","delivery_fee_total"],"attached_condition":"(`eccubedb_test`.`cart`.`cart_key` = 'sample-session_1')"}},{"table":{"table_name":"st","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"2","ref":["func"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.70","data_read_per_join":"2K"},"used_columns":["id","name"],"attached_condition":"<if>(is_not_null_compl(st), (`eccubedb_test`.`st`.`id` = cast(substring_index(`eccubedb_test`.`cart`.`cart_key`,'_',<cache>(-(1))) as unsigned)), true)"}}],"select_list_subqueries":[{"dependent":true,"cacheable":false,"query_block":{"select_id":2,"cost_info":{"query_cost":"2.45"},"nested_loop":[{"table":{"table_name":"ci","access_type":"ref","possible_keys":["IDX_B0228F7421B06187","IDX_B0228F741AD5CDBF"],"key":"IDX_B0228F741AD5CDBF","used_key_parts":["cart_id"],"key_length":"5","ref":["func"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","index_condition":"(`eccubedb_test`.`ci`.`cart_id` = `eccubedb_test`.`cart`.`id`)","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"1K"},"used_columns":["id","product_class_id","cart_id","price","quantity"],"attached_condition":"(`eccubedb_test`.`ci`.`product_class_id` is not null)"}},{"table":{"table_name":"pc","access_type":"eq_ref","possible_keys":["PRIMARY","IDX_1A11D1BA4584665A"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.ci.product_class_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.70","data_read_per_join":"3K"},"used_columns":["id","product_id","class_category_id1","class_category_id2","product_code"],"attached_condition":"(`eccubedb_test`.`pc`.`product_id` is not null)"}},{"table":{"table_name":"cc1","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc.class_category_id1"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"1.05","data_read_per_join":"3K"},"used_columns":["id","class_name_id","name"]}},{"table":{"table_name":"cn1","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.cc1.class_name_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"1.40","data_read_per_join":"3K"},"used_columns":["id","name"]}},{"table":{"table_name":"cc2","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc.class_category_id2"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"1.75","data_read_per_join":"3K"},"used_columns":["id","class_name_id","name"]}},{"table":{"table_name":"cn2","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.cc2.class_name_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"2.10","data_read_per_join":"3K"},"used_columns":["id","name"]}},{"table":{"table_name":"p","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"2.45","data_read_per_join":"2K"},"used_columns":["id","name"]}}],"select_list_subqueries":[{"dependent":true,"cacheable":false,"query_block":{"select_id":3,"cost_info":{"query_cost":"0.35"},"ordering_operation":{"using_filesort":true,"table":{"table_name":"pi","access_type":"ref","possible_keys":["IDX_3267CC7A4584665A","idx_bemart_pi_product_sort"],"key":"IDX_3267CC7A4584665A","used_key_parts":["product_id"],"key_length":"5","ref":["eccubedb_test.p.id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"2K"},"used_columns":["id","product_id","file_name","sort_no"]}}}}]}}]}

### EXPLAIN ANALYZE
-> Limit: 1 row(s)  (cost=0.7 rows=1) (actual time=0.00304..0.00304 rows=0 loops=1)
    -> Nested loop left join  (cost=0.7 rows=1) (actual time=0.00283..0.00283 rows=0 loops=1)
        -> Filter: (cart.cart_key = 'sample-session_1')  (cost=0.35 rows=1) (actual time=0.00246..0.00246 rows=0 loops=1)
            -> Table scan on cart  (cost=0.35 rows=1) (actual time=0.00221..0.00221 rows=0 loops=1)
        -> Filter: (st.id = cast(substring_index(cart.cart_key,'_',<cache>(-(1))) as unsigned))  (cost=0.35 rows=1) (never executed)
            -> Single-row index lookup on st using PRIMARY (id=cast(substring_index(cart.cart_key,'_',<cache>(-(1))) as unsigned))  (cost=0.35 rows=1) (never executed)
-> Select #2 (subquery in projection; dependent)
    -> Aggregate: group_concat(json_object('productCode',pc.product_code,'quantity',ci.quantity,'price',ci.price,'productClassId',pc.id,'productId',p.id,'productName',p.`name`,'mainImage',(select #3),'classCategoryName1',cc1.`name`,'className1',cn1.`name`,'classCategoryName2',cc2.`name`,'className2',cn2.`name`) order by ci.id ASC separator ',')  (cost=2.55 rows=1) (never executed)
        -> Nested loop inner join  (cost=2.45 rows=1) (never executed)
            -> Nested loop left join  (cost=2.1 rows=1) (never executed)
                -> Nested loop left join  (cost=1.75 rows=1) (never executed)
                    -> Nested loop left join  (cost=1.4 rows=1) (never executed)
                        -> Nested loop left join  (cost=1.05 rows=1) (never executed)
                            -> Nested loop inner join  (cost=0.7 rows=1) (never executed)
                                -> Filter: (ci.product_class_id is not null)  (cost=0.35 rows=1) (never executed)
                                    -> Index lookup on ci using IDX_B0228F741AD5CDBF (cart_id=cart.id), with index condition: (ci.cart_id = cart.id)  (cost=0.35 rows=1) (never executed)
                                -> Filter: (pc.product_id is not null)  (cost=0.35 rows=1) (never executed)
                                    -> Single-row index lookup on pc using PRIMARY (id=ci.product_class_id)  (cost=0.35 rows=1) (never executed)
                            -> Single-row index lookup on cc1 using PRIMARY (id=pc.class_category_id1)  (cost=0.35 rows=1) (never executed)
                        -> Single-row index lookup on cn1 using PRIMARY (id=cc1.class_name_id)  (cost=0.35 rows=1) (never executed)
                    -> Single-row index lookup on cc2 using PRIMARY (id=pc.class_category_id2)  (cost=0.35 rows=1) (never executed)
                -> Single-row index lookup on cn2 using PRIMARY (id=cc2.class_name_id)  (cost=0.35 rows=1) (never executed)
            -> Single-row index lookup on p using PRIMARY (id=pc.product_id)  (cost=0.35 rows=1) (never executed)
    -> Select #3 (subquery in projection; dependent)
        -> Limit: 1 row(s)  (cost=0.35 rows=1) (never executed)
            -> Sort: pi.sort_no, pi.id, limit input to 1 row(s) per chunk  (cost=0.35 rows=1) (never executed)
                -> Index lookup on pi using IDX_3267CC7A4584665A (product_id=p.id)  (cost=0.35 rows=1) (never executed)
    -> Select #3 (subquery in projection; dependent)
        -> Limit: 1 row(s)  (cost=0.35 rows=1) (never executed)
            -> Sort: pi.sort_no, pi.id, limit input to 1 row(s) per chunk  (cost=0.35 rows=1) (never executed)
                -> Index lookup on pi using IDX_3267CC7A4584665A (product_id=p.id)  (cost=0.35 rows=1) (never executed)

### SHOW WARNINGS
[{"Level":"Note","Code":1276,"Message":"Field or reference 'eccubedb_test.p.id' of SELECT #3 was resolved in SELECT #2"},{"Level":"Note","Code":1276,"Message":"Field or reference 'c.id' of SELECT #2 was resolved in SELECT #1"}]

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。