INSERT INTO dtb_order_item (
  order_id, product_id, product_class_id,
  product_name, product_code, price,
  quantity, tax, tax_rate, tax_adjust,
  discriminator_type
)
SELECT
  o.id,
  NULL,
  NULL,
  jt.product_name,
  jt.product_code,
  jt.unit_price,
  jt.quantity,
  0,
  0,
  0,
  'orderitem'
FROM
  dtb_order o
  JOIN JSON_TABLE(
    CAST(:items AS CHAR),
    '$[*]' COLUMNS (
      product_code VARCHAR(255) PATH '$.productCode',
      product_name VARCHAR(255) PATH '$.productName',
      unit_price INT PATH '$.unitPrice',
      quantity INT PATH '$.quantity'
    )
  ) AS jt
WHERE
  o.order_no = :orderNo
