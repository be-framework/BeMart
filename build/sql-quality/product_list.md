# SQL Performance Analysis
- **SQL File:** `product_list.sql`
- **Cost:** 8.12

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
  (
    SELECT
      pi.file_name
    FROM
      dtb_product_image pi
    WHERE
      pi.product_id = b.product_id
    ORDER BY
      pi.sort_no ASC,
      pi.id ASC
    LIMIT
      1
  ) AS image_file_name,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            JSON_QUOTE(c.category_name)
            ORDER BY
              c.hierarchy ASC,
              c.sort_no DESC,
              c.id ASC SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      dtb_product_category pcat
      INNER JOIN dtb_category c ON c.id = pcat.category_id
    WHERE
      pcat.product_id = b.product_id
  ) AS category_names_json,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            JSON_QUOTE(t.name)
            ORDER BY
              t.sort_no ASC,
              t.id ASC SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      dtb_product_tag pt
      INNER JOIN dtb_tag t ON t.id = pt.tag_id
    WHERE
      pt.product_id = b.product_id
  ) AS tag_names_json,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            DISTINCT JSON_QUOTE(cn.name)
            ORDER BY
              cn.name SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      (
        SELECT
          cn1.name AS name,
          pc1.product_id
        FROM
          dtb_product_class pc1
          INNER JOIN dtb_class_category cc1 ON cc1.id = pc1.class_category_id1
          INNER JOIN dtb_class_name cn1 ON cn1.id = cc1.class_name_id
        UNION
        SELECT
          cn2.name AS name,
          pc2.product_id
        FROM
          dtb_product_class pc2
          INNER JOIN dtb_class_category cc2 ON cc2.id = pc2.class_category_id2
          INNER JOIN dtb_class_name cn2 ON cn2.id = cc2.class_name_id
      ) cn
    WHERE
      cn.product_id = b.product_id
  ) AS class_names_json
FROM
  (
    SELECT
      pc.id,
      pc.product_id,
      pc.product_code,
      pc.price02,
      pc.stock,
      p.name AS product_name,
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
|  table           b
|  rows            50
|  filtered        100.00
+- Subquery (cn)
   access_type     ref
   key             <auto_key1>
   rows            563
   filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_product_image":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"file_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_3267CC7A4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_3267CC7A61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pi_product_sort","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pi_product_sort","COLUMN_NAME":"sort_no","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pi_product_sort","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":163840,"index_length":180224,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}},"dtb_product_category":{"columns":[{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"category_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_B057789112469DE2","COLUMN_NAME":"category_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":50},{"INDEX_NAME":"IDX_B05778914584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"product_id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"category_id","NON_UNIQUE":0,"SEQ_IN_INDEX":2,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":131072,"index_length":114688,"auto_increment":null,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:14"}},"dtb_category":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"parent_category_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"category_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"hierarchy","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_5ED2C2B61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_5ED2C2B796A8F92","COLUMN_NAME":"parent_category_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":50}],"status":{"table_rows":50,"data_length":16384,"index_length":32768,"auto_increment":51,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_product_tag":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"tag_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_4433E7214584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_4433E72161220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_4433E721BAD26311","COLUMN_NAME":"tag_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":20},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":131072,"index_length":163840,"auto_increment":2001,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:14"}},"dtb_tag":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":20}],"status":{"table_rows":20,"data_length":16384,"index_length":0,"auto_increment":21,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_product_class":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sale_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id1","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_id2","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_duration_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"stock_unlimited","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"sale_limit","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price01","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price02","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_fee","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_product_class_price02_idx","COLUMN_NAME":"price02","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"dtb_product_class_stock_stock_unlimited_idx","COLUMN_NAME":"stock_unlimited","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":500},{"INDEX_NAME":"IDX_1A11D1BA248D128","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"IDX_1A11D1BA4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"IDX_1A11D1BA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BA9B418092","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BAB0524E01","COLUMN_NAME":"sale_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_1A11D1BABA4269E","COLUMN_NAME":"delivery_duration_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_code","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_code_default","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":5,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id1","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"class_category_id2","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":11},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":2000},{"INDEX_NAME":"idx_bemart_pc_default_order","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":4,"CARDINALITY":2000},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":196608,"index_length":606208,"auto_increment":2001,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}},"dtb_class_category":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"class_name_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"backend_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_9B0D1DBA61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_9B0D1DBAB462FB2A","COLUMN_NAME":"class_name_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":5},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":20}],"status":{"table_rows":20,"data_length":16384,"index_length":32768,"auto_increment":21,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_class_name":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"backend_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_187C95AD61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":5}],"status":{"table_rows":5,"data_length":16384,"index_length":16384,"auto_increment":6,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_product":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_list","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"description_detail","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"search_word","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"free_area","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_C49DE22F557B630","COLUMN_NAME":"product_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":2},{"INDEX_NAME":"IDX_C49DE22F61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2000}],"status":{"table_rows":2000,"data_length":212992,"index_length":98304,"auto_increment":2001,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"8.12"},"table":{"table_name":"b","access_type":"ALL","rows_examined_per_scan":50,"rows_produced_per_join":50,"filtered":"100.00","cost_info":{"read_cost":"3.12","eval_cost":"5.00","prefix_cost":"8.12","data_read_per_join":"103K"},"used_columns":["id","product_id","product_code","price02","stock","product_name","product_status_id","description_detail","search_word","note"],"materialized_from_subquery":{"using_temporary_table":true,"dependent":false,"cacheable":true,"query_block":{"select_id":9,"cost_info":{"query_cost":"459.00"},"ordering_operation":{"using_filesort":false,"nested_loop":[{"table":{"table_name":"pc","access_type":"ref","possible_keys":["idx_bemart_pc_default_order"],"key":"idx_bemart_pc_default_order","used_key_parts":["class_category_id1","class_category_id2"],"key_length":"10","ref":["const","const"],"rows_examined_per_scan":1000,"rows_produced_per_join":1000,"filtered":"100.00","index_condition":"(((`eccubedb_test`.`pc`.`class_category_id1` is null) and (`eccubedb_test`.`pc`.`class_category_id2` is null)) and (`eccubedb_test`.`pc`.`product_id` is not null))","cost_info":{"read_cost":"9.00","eval_cost":"100.00","prefix_cost":"109.00","data_read_per_join":"2M"},"used_columns":["id","product_id","class_category_id1","class_category_id2","product_code","stock","price02"]}},{"table":{"table_name":"p","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1000,"filtered":"100.00","cost_info":{"read_cost":"250.00","eval_cost":"100.00","prefix_cost":"459.00","data_read_per_join":"2M"},"used_columns":["id","product_status_id","name","note","description_detail","search_word"]}}]}}}},"select_list_subqueries":[{"dependent":true,"cacheable":false,"query_block":{"select_id":5,"cost_info":{"query_cost":"197.26"},"table":{"table_name":"cn","access_type":"ref","possible_keys":["<auto_key1>"],"key":"<auto_key1>","used_key_parts":["product_id"],"key_length":"5","ref":["b.product_id"],"rows_examined_per_scan":563,"rows_produced_per_join":563,"filtered":"100.00","cost_info":{"read_cost":"140.90","eval_cost":"56.36","prefix_cost":"197.26","data_read_per_join":"568K"},"used_columns":["name","product_id"],"materialized_from_subquery":{"using_temporary_table":true,"dependent":false,"cacheable":true,"query_block":{"union_result":{"using_temporary_table":true,"select_id":8,"table_name":"<union6,7>","access_type":"ALL","query_specifications":[{"dependent":false,"cacheable":true,"query_block":{"select_id":6,"cost_info":{"query_cost":"375.17"},"nested_loop":[{"table":{"table_name":"cn1","access_type":"ALL","possible_keys":["PRIMARY"],"rows_examined_per_scan":5,"rows_produced_per_join":5,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.50","prefix_cost":"0.75","data_read_per_join":"15K"},"used_columns":["id","name"]}},{"table":{"table_name":"cc1","access_type":"ref","possible_keys":["PRIMARY","IDX_9B0D1DBAB462FB2A"],"key":"IDX_9B0D1DBAB462FB2A","used_key_parts":["class_name_id"],"key_length":"5","ref":["eccubedb_test.cn1.id"],"rows_examined_per_scan":4,"rows_produced_per_join":20,"filtered":"100.00","using_index":true,"cost_info":{"read_cost":"1.25","eval_cost":"2.00","prefix_cost":"4.00","data_read_per_join":"60K"},"used_columns":["id","class_name_id"]}},{"table":{"table_name":"pc1","access_type":"ref","possible_keys":["IDX_1A11D1BA248D128","idx_bemart_pc_default_order"],"key":"idx_bemart_pc_default_order","used_key_parts":["class_category_id1"],"key_length":"5","ref":["eccubedb_test.cc1.id"],"rows_examined_per_scan":181,"rows_produced_per_join":3636,"filtered":"100.00","using_index":true,"cost_info":{"read_cost":"7.53","eval_cost":"363.64","prefix_cost":"375.17","data_read_per_join":"10M"},"used_columns":["product_id","class_category_id1"]}}]}},{"dependent":false,"cacheable":true,"query_block":{"select_id":7,"cost_info":{"query_cost":"1603.00"},"nested_loop":[{"table":{"table_name":"pc2","access_type":"index","possible_keys":["IDX_1A11D1BA9B418092"],"key":"idx_bemart_pc_default_order","used_key_parts":["class_category_id1","class_category_id2","id","product_id"],"key_length":"19","rows_examined_per_scan":2000,"rows_produced_per_join":2000,"filtered":"100.00","using_index":true,"cost_info":{"read_cost":"3.00","eval_cost":"200.00","prefix_cost":"203.00","data_read_per_join":"5M"},"used_columns":["product_id","class_category_id2"],"attached_condition":"(`eccubedb_test`.`pc2`.`class_category_id2` is not null)"}},{"table":{"table_name":"cc2","access_type":"eq_ref","possible_keys":["PRIMARY","IDX_9B0D1DBAB462FB2A"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pc2.class_category_id2"],"rows_examined_per_scan":1,"rows_produced_per_join":2000,"filtered":"100.00","cost_info":{"read_cost":"500.00","eval_cost":"200.00","prefix_cost":"903.00","data_read_per_join":"5M"},"used_columns":["id","class_name_id"],"attached_condition":"(`eccubedb_test`.`cc2`.`class_name_id` is not null)"}},{"table":{"table_name":"cn2","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.cc2.class_name_id"],"rows_examined_per_scan":1,"rows_produced_per_join":2000,"filtered":"100.00","cost_info":{"read_cost":"500.00","eval_cost":"200.00","prefix_cost":"1603.00","data_read_per_join":"5M"},"used_columns":["id","name"]}}]}}]}}}}}},{"dependent":true,"cacheable":false,"query_block":{"select_id":4,"cost_info":{"query_cost":"0.70"},"nested_loop":[{"table":{"table_name":"pt","access_type":"ref","possible_keys":["IDX_4433E7214584665A","IDX_4433E721BAD26311"],"key":"IDX_4433E7214584665A","used_key_parts":["product_id"],"key_length":"5","ref":["b.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"1K"},"used_columns":["product_id","tag_id"],"attached_condition":"(`eccubedb_test`.`pt`.`tag_id` is not null)"}},{"table":{"table_name":"t","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pt.tag_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.70","data_read_per_join":"2K"},"used_columns":["id","name","sort_no"]}}]}},{"dependent":true,"cacheable":false,"query_block":{"select_id":3,"cost_info":{"query_cost":"0.70"},"nested_loop":[{"table":{"table_name":"pcat","access_type":"ref","possible_keys":["PRIMARY","IDX_B05778914584665A","IDX_B057789112469DE2"],"key":"PRIMARY","used_key_parts":["product_id"],"key_length":"4","ref":["b.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","using_index":true,"cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"1K"},"used_columns":["product_id","category_id"]}},{"table":{"table_name":"c","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.pcat.category_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.70","data_read_per_join":"2K"},"used_columns":["id","category_name","hierarchy","sort_no"]}}]}},{"dependent":true,"cacheable":false,"query_block":{"select_id":2,"cost_info":{"query_cost":"0.35"},"ordering_operation":{"using_filesort":true,"table":{"table_name":"pi","access_type":"ref","possible_keys":["IDX_3267CC7A4584665A","idx_bemart_pi_product_sort"],"key":"IDX_3267CC7A4584665A","used_key_parts":["product_id"],"key_length":"5","ref":["b.product_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"2K"},"used_columns":["id","product_id","file_name","sort_no"]}}}}]}

### EXPLAIN ANALYZE
-> Table scan on b  (cost=464..467 rows=50) (actual time=0.216..0.227 rows=50 loops=1)
    -> Materialize  (cost=464..464 rows=50) (actual time=0.215..0.215 rows=50 loops=1)
        -> Limit: 50 row(s)  (cost=459 rows=50) (actual time=0.111..0.171 rows=50 loops=1)
            -> Nested loop inner join  (cost=459 rows=1000) (actual time=0.111..0.167 rows=50 loops=1)
                -> Index lookup on pc using idx_bemart_pc_default_order (class_category_id1=NULL, class_category_id2=NULL), with index condition: (((pc.class_category_id1 is null) and (pc.class_category_id2 is null)) and (pc.product_id is not null))  (cost=109 rows=1000) (actual time=0.105..0.113 rows=50 loops=1)
                -> Single-row index lookup on p using PRIMARY (id=pc.product_id)  (cost=0.25 rows=1) (actual time=863e-6..900e-6 rows=1 loops=50)

### SHOW WARNINGS
[{"Level":"Note","Code":1276,"Message":"Field or reference 'b.product_id' of SELECT #2 was resolved in SELECT #1"},{"Level":"Note","Code":1276,"Message":"Field or reference 'b.product_id' of SELECT #3 was resolved in SELECT #1"},{"Level":"Note","Code":1276,"Message":"Field or reference 'b.product_id' of SELECT #4 was resolved in SELECT #1"},{"Level":"Note","Code":1276,"Message":"Field or reference 'b.product_id' of SELECT #5 was resolved in SELECT #1"}]

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。