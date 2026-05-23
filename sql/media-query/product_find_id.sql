SELECT product_id FROM dtb_product_class WHERE product_code = :productCode AND class_category_id1 IS NULL AND class_category_id2 IS NULL ORDER BY id ASC LIMIT 1
