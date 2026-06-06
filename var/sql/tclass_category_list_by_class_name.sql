SELECT CAST(id AS CHAR), CAST(class_name_id AS CHAR), name FROM dtb_class_category WHERE :classNameId REGEXP '^[0-9]+$' AND class_name_id = CAST(:classNameId AS UNSIGNED) ORDER BY sort_no ASC, id ASC
