# SQL Performance Analysis
- **SQL File:** `order_history_by_order_no.sql`
- **Cost:** 0.70

## SQL
```sql
SELECT
  o.order_no,
  o.customer_id,
  o.message,
  p.payment_method,
  o.subtotal,
  o.delivery_fee_total,
  o.charge,
  o.discount,
  o.tax,
  o.total,
  o.payment_total,
  o.add_point,
  o.use_point,
  o.order_status_id,
  o.order_date,
  o.payment_date,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            JSON_OBJECT(
              'name01',
              s.name01,
              'name02',
              s.name02,
              'kana01',
              s.kana01,
              'kana02',
              s.kana02,
              'postalCode',
              s.postal_code,
              'prefName',
              pref.name,
              'addr01',
              s.addr01,
              'addr02',
              s.addr02,
              'phoneNumber',
              s.phone_number,
              'deliveryName',
              s.delivery_name,
              'deliveryDate',
              s.delivery_date,
              'deliveryTime',
              s.delivery_time,
              'items',
              (
                SELECT
                  COALESCE(
                    CONCAT(
                      '[',
                      GROUP_CONCAT(
                        JSON_OBJECT(
                          'productCode', oi.product_code, 'productName',
                          oi.product_name, 'quantity', oi.quantity,
                          'unitPrice', oi.price
                        )
                        ORDER BY
                          oi.id ASC SEPARATOR ','
                      ),
                      ']'
                    ),
                    JSON_ARRAY()
                  )
                FROM
                  dtb_order_item oi
                WHERE
                  oi.order_id = o.id
                  AND (
                    oi.shipping_id = s.id
                    OR oi.shipping_id IS NULL
                  )
              )
            )
            ORDER BY
              s.sort_no IS NULL,
              s.sort_no ASC,
              s.id ASC SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      dtb_shipping s
      LEFT JOIN mtb_pref pref ON pref.id = s.pref_id
    WHERE
      s.order_id = o.id
  ) AS shippings_json,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            JSON_OBJECT(
              'sendDate',
              DATE_FORMAT(mh.send_date, '%Y-%m-%d %H:%i:%s'),
              'mailSubject',
              mh.mail_subject, 'mailBody', mh.mail_body
            )
            ORDER BY
              mh.send_date ASC,
              mh.id ASC SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      dtb_mail_history mh
    WHERE
      mh.order_id = o.id
  ) AS mail_histories_json
FROM
  dtb_order o
  LEFT JOIN dtb_payment p ON p.id = o.payment_id
WHERE
  o.order_no = :orderNo
  AND o.order_status_id <> 8
LIMIT
  1

```

## Detected Issues


## Explain Tree
```
JOIN
+- Index lookup
   key             dtb_order_order_no_idx
   rows            1
   filtered        100.00
   +- Table
      table           o
```
## Analysis Detail

### Schema
{"dtb_order_item":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"order_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_class_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"shipping_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"rounding_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"tax_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"tax_display_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"order_item_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"product_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_name1","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_name2","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_name1","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"class_category_name2","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"price","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"quantity","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"tax","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"tax_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"tax_adjust","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"tax_rule_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"processor_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"point_rate","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(10,0) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_A0C8C3ED1BD5C574","COLUMN_NAME":"rounding_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3ED21B06187","COLUMN_NAME":"product_class_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3ED4584665A","COLUMN_NAME":"product_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3ED4887F3F8","COLUMN_NAME":"shipping_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3ED84042C99","COLUMN_NAME":"tax_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3ED8D9F6D38","COLUMN_NAME":"order_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3EDA2505856","COLUMN_NAME":"tax_display_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_A0C8C3EDCAD13EAD","COLUMN_NAME":"order_item_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":131072,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"dtb_shipping":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"order_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"country_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pref_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"company_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"phone_number","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(14)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"postal_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(8)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"time_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_time","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"delivery_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"shipping_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"tracking_number","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"mail_send_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_2EBD22CE12136921","COLUMN_NAME":"delivery_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_2EBD22CE61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_2EBD22CE8D9F6D38","COLUMN_NAME":"order_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_2EBD22CEE171EF5F","COLUMN_NAME":"pref_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_2EBD22CEF92F3E70","COLUMN_NAME":"country_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":81920,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"mtb_pref":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":47}],"status":{"table_rows":47,"data_length":16384,"index_length":0,"auto_increment":null,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}},"dtb_mail_history":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"order_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"send_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"mail_subject","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"mail_body","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"mail_html_body","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_4870AB1161220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_4870AB118D9F6D38","COLUMN_NAME":"order_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":32768,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"dtb_order":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"customer_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"country_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pref_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sex_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"job_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"payment_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"device_type_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"pre_order_id","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"UNI","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"order_no","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"message","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"name02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"kana02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"company_name","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"email","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"phone_number","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(14)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"postal_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(8)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr01","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"addr02","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"birth","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"subtotal","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"discount","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"delivery_fee_total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"charge","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"tax","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"payment_total","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"payment_method","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"note","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(4000)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"order_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"payment_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"currency_code","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"complete_message","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"complete_mail_message","DATA_TYPE":"longtext","COLUMN_TYPE":"longtext","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"add_point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"use_point","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,0) unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"0","EXTRA":""},{"COLUMN_NAME":"order_status_id","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"dtb_order_email_idx","COLUMN_NAME":"email","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_order_date_idx","COLUMN_NAME":"order_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_order_no_idx","COLUMN_NAME":"order_no","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_payment_date_idx","COLUMN_NAME":"payment_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_pre_order_id_idx","COLUMN_NAME":"pre_order_id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"dtb_order_update_date_idx","COLUMN_NAME":"update_date","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8074C3A3BB","COLUMN_NAME":"payment_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8074FFA550E","COLUMN_NAME":"device_type_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8075A2DB2A0","COLUMN_NAME":"sex_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D8079395C3F3","COLUMN_NAME":"customer_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807BE04EA9","COLUMN_NAME":"job_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807D7707B45","COLUMN_NAME":"order_status_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807E171EF5F","COLUMN_NAME":"pref_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"IDX_1D66D807F92F3E70","COLUMN_NAME":"country_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":0},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":0}],"status":{"table_rows":0,"data_length":16384,"index_length":229376,"auto_increment":1,"create_time":"2026-06-12 21:40:12","update_time":null}},"dtb_payment":{"columns":[{"COLUMN_NAME":"id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"NO","COLUMN_KEY":"PRI","COLUMN_DEFAULT":null,"EXTRA":"auto_increment"},{"COLUMN_NAME":"creator_id","DATA_TYPE":"int","COLUMN_TYPE":"int unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"MUL","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"payment_method","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"charge","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":"0.00","EXTRA":""},{"COLUMN_NAME":"rule_max","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"sort_no","DATA_TYPE":"smallint","COLUMN_TYPE":"smallint unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"fixed","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"payment_image","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"rule_min","DATA_TYPE":"decimal","COLUMN_TYPE":"decimal(12,2) unsigned","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"method_class","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"YES","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"visible","DATA_TYPE":"tinyint","COLUMN_TYPE":"tinyint(1)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":"1","EXTRA":""},{"COLUMN_NAME":"create_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"update_date","DATA_TYPE":"datetime","COLUMN_TYPE":"datetime","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""},{"COLUMN_NAME":"discriminator_type","DATA_TYPE":"varchar","COLUMN_TYPE":"varchar(255)","IS_NULLABLE":"NO","COLUMN_KEY":"","COLUMN_DEFAULT":null,"EXTRA":""}],"indexes":[{"INDEX_NAME":"IDX_7AFF628F61220EA6","COLUMN_NAME":"creator_id","NON_UNIQUE":1,"SEQ_IN_INDEX":1,"CARDINALITY":1},{"INDEX_NAME":"PRIMARY","COLUMN_NAME":"id","NON_UNIQUE":0,"SEQ_IN_INDEX":1,"CARDINALITY":2}],"status":{"table_rows":2,"data_length":16384,"index_length":16384,"auto_increment":3,"create_time":"2026-06-12 21:40:12","update_time":"2026-06-12 21:40:13"}}}

### EXPLAIN JSON
{"select_id":1,"cost_info":{"query_cost":"0.70"},"nested_loop":[{"table":{"table_name":"o","access_type":"ref","possible_keys":["IDX_1D66D807D7707B45","dtb_order_order_no_idx"],"key":"dtb_order_order_no_idx","used_key_parts":["order_no"],"key_length":"1023","ref":["const"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"44K"},"used_columns":["id","customer_id","payment_id","order_no","message","subtotal","discount","delivery_fee_total","charge","tax","total","payment_total","order_date","payment_date","add_point","use_point","order_status_id"],"attached_condition":"(`eccubedb_test`.`o`.`order_status_id` <> 8)"}},{"table":{"table_name":"p","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"4","ref":["eccubedb_test.o.payment_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.70","data_read_per_join":"4K"},"used_columns":["id","payment_method"]}}],"select_list_subqueries":[{"dependent":true,"cacheable":false,"query_block":{"select_id":4,"cost_info":{"query_cost":"0.35"},"table":{"table_name":"mh","access_type":"ref","possible_keys":["IDX_4870AB118D9F6D38"],"key":"IDX_4870AB118D9F6D38","used_key_parts":["order_id"],"key_length":"5","ref":["eccubedb_test.o.id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"2K"},"used_columns":["id","order_id","send_date","mail_subject","mail_body"]}}},{"dependent":true,"cacheable":false,"query_block":{"select_id":2,"cost_info":{"query_cost":"0.70"},"nested_loop":[{"table":{"table_name":"s","access_type":"ref","possible_keys":["IDX_2EBD22CE8D9F6D38"],"key":"IDX_2EBD22CE8D9F6D38","used_key_parts":["order_id"],"key_length":"5","ref":["eccubedb_test.o.id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"26K"},"used_columns":["id","order_id","pref_id","name01","name02","kana01","kana02","phone_number","postal_code","addr01","addr02","delivery_name","delivery_time","delivery_date","sort_no"]}},{"table":{"table_name":"pref","access_type":"eq_ref","possible_keys":["PRIMARY"],"key":"PRIMARY","used_key_parts":["id"],"key_length":"2","ref":["eccubedb_test.s.pref_id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.70","data_read_per_join":"2K"},"used_columns":["id","name"]}}],"select_list_subqueries":[{"dependent":true,"cacheable":false,"query_block":{"select_id":3,"cost_info":{"query_cost":"0.35"},"table":{"table_name":"oi","access_type":"ref","possible_keys":["IDX_A0C8C3ED8D9F6D38","IDX_A0C8C3ED4887F3F8"],"key":"IDX_A0C8C3ED8D9F6D38","used_key_parts":["order_id"],"key_length":"5","ref":["eccubedb_test.o.id"],"rows_examined_per_scan":1,"rows_produced_per_join":1,"filtered":"100.00","cost_info":{"read_cost":"0.25","eval_cost":"0.10","prefix_cost":"0.35","data_read_per_join":"9K"},"used_columns":["id","order_id","shipping_id","product_name","product_code","price","quantity"],"attached_condition":"((`eccubedb_test`.`oi`.`shipping_id` = `eccubedb_test`.`s`.`id`) or (`eccubedb_test`.`oi`.`shipping_id` is null))"}}}]}}]}

### EXPLAIN ANALYZE
-> Limit: 1 row(s)  (cost=0.7 rows=1) (actual time=0.00867..0.00867 rows=0 loops=1)
    -> Nested loop left join  (cost=0.7 rows=1) (actual time=0.00812..0.00812 rows=0 loops=1)
        -> Filter: (o.order_status_id <> 8)  (cost=0.35 rows=1) (actual time=0.00775..0.00775 rows=0 loops=1)
            -> Index lookup on o using dtb_order_order_no_idx (order_no='ORDER-0001')  (cost=0.35 rows=1) (actual time=0.00729..0.00729 rows=0 loops=1)
        -> Single-row index lookup on p using PRIMARY (id=o.payment_id)  (cost=0.35 rows=1) (never executed)
-> Select #2 (subquery in projection; dependent)
    -> Aggregate: group_concat(json_object('name01',s.name01,'name02',s.name02,'kana01',s.kana01,'kana02',s.kana02,'postalCode',s.postal_code,'prefName',pref.`name`,'addr01',s.addr01,'addr02',s.addr02,'phoneNumber',s.phone_number,'deliveryName',s.delivery_name,'deliveryDate',s.delivery_date,'deliveryTime',s.delivery_time,'items',(select #3)) order by (s.sort_no is null) ASC,s.sort_no ASC,s.id ASC separator ',')  (cost=0.8 rows=1) (never executed)
        -> Nested loop left join  (cost=0.7 rows=1) (never executed)
            -> Index lookup on s using IDX_2EBD22CE8D9F6D38 (order_id=o.id)  (cost=0.35 rows=1) (never executed)
            -> Single-row index lookup on pref using PRIMARY (id=s.pref_id)  (cost=0.35 rows=1) (never executed)
    -> Select #3 (subquery in projection; dependent)
        -> Aggregate: group_concat(json_object('productCode',oi.product_code,'productName',oi.product_name,'quantity',oi.quantity,'unitPrice',oi.price) order by oi.id ASC separator ',')  (cost=0.45 rows=1) (never executed)
            -> Filter: ((oi.shipping_id = s.id) or (oi.shipping_id is null))  (cost=0.35 rows=1) (never executed)
                -> Index lookup on oi using IDX_A0C8C3ED8D9F6D38 (order_id=o.id)  (cost=0.35 rows=1) (never executed)
    -> Select #3 (subquery in projection; dependent)
        -> Aggregate: group_concat(json_object('productCode',oi.product_code,'productName',oi.product_name,'quantity',oi.quantity,'unitPrice',oi.price) order by oi.id ASC separator ',')  (cost=0.45 rows=1) (never executed)
            -> Filter: ((oi.shipping_id = s.id) or (oi.shipping_id is null))  (cost=0.35 rows=1) (never executed)
                -> Index lookup on oi using IDX_A0C8C3ED8D9F6D38 (order_id=o.id)  (cost=0.35 rows=1) (never executed)
-> Select #4 (subquery in projection; dependent)
    -> Aggregate: group_concat(json_object('sendDate',date_format(mh.send_date,'%Y-%m-%d %H:%i:%s'),'mailSubject',mh.mail_subject,'mailBody',mh.mail_body) order by mh.send_date ASC,mh.id ASC separator ',')  (cost=0.45 rows=1) (never executed)
        -> Index lookup on mh using IDX_4870AB118D9F6D38 (order_id=o.id)  (cost=0.35 rows=1) (never executed)

### SHOW WARNINGS
[{"Level":"Note","Code":1276,"Message":"Field or reference 'eccubedb_test.o.id' of SELECT #3 was resolved in SELECT #1"},{"Level":"Note","Code":1276,"Message":"Field or reference 'eccubedb_test.s.id' of SELECT #3 was resolved in SELECT #2"},{"Level":"Note","Code":1276,"Message":"Field or reference 'eccubedb_test.o.id' of SELECT #2 was resolved in SELECT #1"},{"Level":"Note","Code":1276,"Message":"Field or reference 'eccubedb_test.o.id' of SELECT #4 was resolved in SELECT #1"}]

## Analysis Instructions
Create a SQL performance analysis report for this query. Begin with a table of key metrics showing current values and their impact. Then describe the detected issues, focusing on the root causes. Follow with specific improvement recommendations, including SQL examples and their expected impact. End with implementation priorities and any important considerations. Keep the analysis focused on actionable insights that will lead to significant performance gains.


以上の分析を日本語で記述してください。