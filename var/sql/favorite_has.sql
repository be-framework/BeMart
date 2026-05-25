SELECT 1 AS found
FROM dtb_customer_favorite_product fav
INNER JOIN dtb_product_class pc ON pc.product_id = fav.product_id
WHERE :customerId REGEXP '^[0-9]+$'
  AND fav.customer_id = CAST(:customerId AS UNSIGNED)
  AND pc.product_code = :productCode
  AND pc.class_category_id1 IS NULL AND pc.class_category_id2 IS NULL
LIMIT 1
