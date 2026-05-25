INSERT INTO dtb_product (product_status_id, name, note, description_detail, search_word, create_date, update_date, discriminator_type)
VALUES (CAST(JSON_VALUE(:product, '$.productStatus') AS UNSIGNED), JSON_VALUE(:product, '$.productName'), JSON_VALUE(:product, '$.note'), JSON_VALUE(:product, '$.description'), JSON_VALUE(:product, '$.searchWord'), NOW(), NOW(), 'product');
SET @bemart_product_id = LAST_INSERT_ID();
INSERT INTO dtb_product_class (product_id, product_code, price02, stock, stock_unlimited, visible, create_date, update_date, discriminator_type)
VALUES (@bemart_product_id, JSON_VALUE(:product, '$.productCode'), CAST(JSON_VALUE(:product, '$.price02') AS SIGNED), CAST(JSON_VALUE(:product, '$.stock') AS SIGNED), CASE WHEN JSON_VALUE(:product, '$.stock') IS NULL THEN 1 ELSE 0 END, 1, NOW(), NOW(), 'productclass')
