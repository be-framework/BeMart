# SQL Performance Analysis
- **SQL File:** `order_list_all.sql`
- **Cost:** 1.41

## SQL
```sql
SELECT
  order_no,
  pre_order_id,
  customer_id,
  payment_id,
  subtotal,
  delivery_fee_total,
  charge,
  discount,
  tax,
  total,
  payment_total,
  add_point,
  use_point,
  order_status_id,
  order_date,
  payment_date,
  JSON_OBJECT(
    'name01',
    name01,
    'name02',
    name02,
    'kana01',
    kana01,
    'kana02',
    kana02,
    'companyName',
    company_name,
    'email',
    email,
    'phoneNumber',
    phone_number,
    'postalCode',
    postal_code,
    'pref',
    pref_id,
    'addr01',
    addr01,
    'addr02',
    addr02
  ) AS customer_snapshot_json
FROM
  dtb_order
WHERE
  order_status_id <> 8
ORDER BY
  order_date DESC,
  id DESC
LIMIT
  :limit
OFFSET
  :offset

```

## Detected Issues


## Explain Tree
```
Sort
+- Table scan
   +- Table
      table           dtb_order
      rows            1
      filtered        100.00
      condition       (`eccubedb_test`.`dtb_order`.`order_status_id` <> 8)
```
## Analysis Detail

### Schema
{"dtb_order":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"customer_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"country_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pref_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sex_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"job_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"payment_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"device_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pre_order_id","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"UNI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"order_no","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"message","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"company_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"email","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"phone_number","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(14)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"postal_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(8)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"birth","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"subtotal","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"discount","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"delivery_fee_total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"charge","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"tax","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"payment_total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"payment_method","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"order_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"payment_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"complete_message","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"complete_mail_message","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"add_point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"use_point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"order_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_order_email_idx","COLUMN_NAME":"email","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_order_date_idx","COLUMN_NAME":"order_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_order_no_idx","COLUMN_NAME":"order_no","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_payment_date_idx","COLUMN_NAME":"payment_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_pre_order_id_idx","COLUMN_NAME":"pre_order_id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_update_date_idx","COLUMN_NAME":"update_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8074C3A3BB","COLUMN_NAME":"payment_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8074FFA550E","COLUMN_NAME":"device_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8075A2DB2A0","COLUMN_NAME":"sex_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8079395C3F3","COLUMN_NAME":"customer_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807BE04EA9","COLUMN_NAME":"job_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807D7707B45","COLUMN_NAME":"order_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807E171EF5F","COLUMN_NAME":"pref_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807F92F3E70","COLUMN_NAME":"country_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":229376,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"1.41"},"ordering_operation":{"using_filesort":false,"table":{"table_name":"dtb_order","access_type":"index","possible_keys":["IDX_1D66D807D7707B45"],"key":"dtb_order_order_date_idx","used_key_parts":["order_date"],"key_length":"6","rows_examined_per_scan":1,"rows_produced_per_join":2,"filtered":"100.00","backward_index_scan":true,"cost_info":{"read_cost":"1.21","eval_cost":"0.20","prefix_cost":"1.41","data_read_per_join":"88K"},"used_columns":["id","customer_id","pref_id","payment_id","pre_order_id","order_no","name01","name02","kana01","kana02","company_name","email","phone_number","postal_code","addr01","addr02","subtotal","discount","delivery_fee_total","charge","tax","total","payment_total","order_date","payment_date","add_point","use_point","order_status_id"],"attached_condition":"(`eccubedb_test`.`dtb_order`.`order_status_id` <> 8)"}}}

### EXPLAIN ANALYZE
-> Limit: 20 row(s)  (cost=0.655 rows=1) (actual time=0.00546..0.00546 rows=0 loops=1)
    -> Filter: (dtb_order.order_status_id <> 8)  (cost=0.655 rows=1) (actual time=0.00525..0.00525 rows=0 loops=1)
        -> Index scan on dtb_order using dtb_order_order_date_idx (reverse)  (cost=0.655 rows=1) (actual time=0.00492..0.00492 rows=0 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。