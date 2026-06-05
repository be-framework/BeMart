INSERT INTO dtb_product (product_status_id, name, note, description_detail, search_word, create_date, update_date, discriminator_type)
VALUES (CAST(JSON_VALUE(CAST(:product AS CHAR), '$.productStatus') AS UNSIGNED), JSON_VALUE(CAST(:product AS CHAR), '$.productName'), JSON_VALUE(CAST(:product AS CHAR), '$.note'), JSON_VALUE(CAST(:product AS CHAR), '$.description'), JSON_VALUE(CAST(:product AS CHAR), '$.searchWord'), NOW(), NOW(), 'product');
SET @bemart_product_id = LAST_INSERT_ID();
INSERT INTO dtb_product_class (product_id, sale_type_id, product_code, price02, stock, stock_unlimited, visible, create_date, update_date, discriminator_type)
VALUES (@bemart_product_id, 1, JSON_VALUE(CAST(:product AS CHAR), '$.productCode'), CAST(JSON_VALUE(CAST(:product AS CHAR), '$.price02') AS SIGNED), CAST(JSON_VALUE(CAST(:product AS CHAR), '$.stock') AS SIGNED), CASE WHEN JSON_VALUE(CAST(:product AS CHAR), '$.stock') IS NULL THEN 1 ELSE 0 END, 1, NOW(), NOW(), 'productclass');
