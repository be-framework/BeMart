SELECT CAST(id AS CHAR), CAST(class_name_id AS CHAR), name FROM dtb_class_category WHERE :classCategoryId REGEXP '^[0-9]+$' AND id = CAST(:classCategoryId AS UNSIGNED) LIMIT 1
