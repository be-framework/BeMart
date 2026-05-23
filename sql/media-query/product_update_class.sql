UPDATE dtb_product_class SET price02 = :price02, stock = :stock, update_date = NOW() WHERE product_id = :id AND class_category_id1 IS NULL AND class_category_id2 IS NULL
