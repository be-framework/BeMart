UPDATE dtb_product p
INNER JOIN dtb_product_class pc ON pc.product_id = p.id AND pc.class_category_id1 IS NULL AND pc.class_category_id2 IS NULL
SET p.product_status_id = 3, p.update_date = NOW()
WHERE pc.product_code = :productCode AND p.product_status_id <> 3
