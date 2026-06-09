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
