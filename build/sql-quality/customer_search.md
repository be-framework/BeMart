# SQL Performance Analysis
- **SQL File:** `customer_search.sql`
- **Cost:** 4.75

## SQL
```sql
SELECT
  c.id,
  c.email,
  c.password,
  c.name01,
  c.name02,
  c.kana01,
  c.kana02,
  c.company_name,
  c.phone_number,
  c.postal_code,
  c.pref_id,
  c.addr01,
  c.addr02,
  c.birth,
  c.sex_id,
  c.job_id,
  c.customer_status_id,
  c.secret_key
FROM
  (
    SELECT
      id,
      email,
      password,
      name01,
      name02,
      kana01,
      kana02,
      company_name,
      phone_number,
      postal_code,
      pref_id,
      addr01,
      addr02,
      DATE_FORMAT(birth, '%Y-%m-%d') AS birth,
      sex_id,
      job_id,
      customer_status_id,
      secret_key
    FROM
      dtb_customer
    WHERE
      (
        :nameKeyword IS NULL
        OR :nameKeyword = ''
        OR INSTR(
          CONCAT_WS(' ', name01, name02, company_name),
          :nameKeyword
        ) > 0
      )
      AND (
        :emailKeyword IS NULL
        OR :emailKeyword = ''
        OR INSTR(email, :emailKeyword) > 0
      )
    ORDER BY
      id ASC
    LIMIT
      :limit
  ) c

```

## Detected Issues
- フルテーブルスキャンが検出されました。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/FullTableScan)
- 非効率的なソート操作が検出されました。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/IneffectiveSort)
- グループ化のために一時テーブルが必要です。 [Learn more](https://koriym.github.io/Koriym.SqlQuality/issues/TemporaryTableGrouping)

## Explain Tree
```
Table scan
+- Table
   table           c
   rows            20
   filtered        100.00
```
## Analysis Detail

### Schema
{"dtb_customer":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"customer_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sex_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"job_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"country_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pref_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"company_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"postal_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(8)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"email","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"phone_number","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(14)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"birth","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"password","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"salt","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"secret_key","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"UNI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"first_buy_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"last_buy_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"buy_times","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"buy_total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"reset_key","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"reset_expire","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_customer_buy_times_idx","COLUMN_NAME":"buy_times","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"dtb_customer_buy_total_idx","COLUMN_NAME":"buy_total","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"dtb_customer_create_date_idx","COLUMN_NAME":"create_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"dtb_customer_email_idx","COLUMN_NAME":"email","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"dtb_customer_last_buy_date_idx","COLUMN_NAME":"last_buy_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"dtb_customer_update_date_idx","COLUMN_NAME":"update_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_8298BBE35A2DB2A0","COLUMN_NAME":"sex_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_8298BBE3BE04EA9","COLUMN_NAME":"job_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_8298BBE3C00AF8A7","COLUMN_NAME":"customer_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_8298BBE3E171EF5F","COLUMN_NAME":"pref_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"IDX_8298BBE3F92F3E70","COLUMN_NAME":"country_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_customer_reset_key","COLUMN_NAME":"reset_key","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"idx_bemart_customer_reset_key","COLUMN_NAME":"id","NON_UNIQUE":1,"SEQ_IN_INDEX":2,"CARDINALITY":500},{"INDEX_NAME":"idx_bemart_customer_reset_key","COLUMN_NAME":"reset_expire","NON_UNIQUE":1,"SEQ_IN_INDEX":3,"CARDINALITY":500},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":500},{"INDEX_NAME":"secret_key","COLUMN_NAME":"secret_key","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":500}],"status":{"table_rows":500,"data_length":163840,"index_length":278528,"auto_increment":501,"create_time":"2026-06-12 21:40:13","update_time":"2026-06-12 21:40:14"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"4.75"},"table":{"table_name":"c","access_type":"ALL","rows_examined_per_scan":20,"rows_produced_per_join":20,"filtered":"100.00","cost_info":{"read_cost":"2.75","eval_cost":"2.00","prefix_cost":"4.75","data_read_per_join":"202K"},"used_columns":["id","email","password","name01","name02","kana01","kana02","company_name","phone_number","postal_code","pref_id","addr01","addr02","birth","sex_id","job_id","customer_status_id","secret_key"],"materialized_from_subquery":{"using_temporary_table":true,"dependent":false,"cacheable":true,"query_block":{"select_id":2,"cost_info":{"query_cost":"52.50"},"ordering_operation":{"using_filesort":false,"table":{"table_name":"dtb_customer","access_type":"index","key":"PRIMARY","used_key_parts":["id"],"key_length":"4","rows_examined_per_scan":20,"rows_produced_per_join":500,"filtered":"100.00","cost_info":{"read_cost":"2.50","eval_cost":"50.00","prefix_cost":"52.50","data_read_per_join":"14M"},"used_columns":["id","customer_status_id","sex_id","job_id","pref_id","name01","name02","kana01","kana02","company_name","postal_code","addr01","addr02","email","phone_number","birth","password","secret_key"],"attached_condition":"((locate('\u59d3',concat_ws(' ',`eccubedb_test`.`dtb_customer`.`name01`,`eccubedb_test`.`dtb_customer`.`name02`,`eccubedb_test`.`dtb_customer`.`company_name`)) > 0) and (locate('example',`eccubedb_test`.`dtb_customer`.`email`) > 0))"}}}}}}

### EXPLAIN ANALYZE
-> Table scan on c  (cost=2.32..4.93 rows=20) (actual time=0.0596..0.0638 rows=20 loops=1)
    -> Materialize  (cost=2.18..2.18 rows=20) (actual time=0.0585..0.0585 rows=20 loops=1)
        -> Limit: 20 row(s)  (cost=0.18 rows=20) (actual time=0.00879..0.0355 rows=20 loops=1)
            -> Filter: ((locate('姓',concat_ws(' ',dtb_customer.name01,dtb_customer.name02,dtb_customer.company_name)) > 0) and (locate('example',dtb_customer.email) > 0))  (cost=0.18 rows=20) (actual time=0.00838..0.0335 rows=20 loops=1)
                -> Index scan on dtb_customer using PRIMARY  (cost=0.18 rows=20) (actual time=0.00725..0.0238 rows=20 loops=1)

### SHOW WARNINGS
N/A

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。