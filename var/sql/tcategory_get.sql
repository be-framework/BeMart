SELECT id, category_name, parent_category_id, sort_no FROM dtb_category WHERE :categoryId REGEXP '^[0-9]+$' AND id = CAST(:categoryId AS UNSIGNED) LIMIT 1
