INSERT IGNORE INTO dtb_customer_favorite_product (customer_id, product_id, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:favorite, '$.customerId') AS UNSIGNED), p.id, NOW(), NOW(), 'customerfavoriteproduct'
FROM dtb_product p
INNER JOIN dtb_product_class pc ON pc.product_id = p.id
WHERE JSON_VALUE(:favorite, '$.customerId') REGEXP '^[0-9]+$'
  AND pc.product_code = JSON_VALUE(:favorite, '$.productCode')
  AND pc.class_category_id1 IS NULL AND pc.class_category_id2 IS NULL
LIMIT 1
