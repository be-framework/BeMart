SELECT id, class_name_id, name FROM dtb_class_category WHERE :classNameId REGEXP '^[0-9]+$' AND class_name_id = CAST(:classNameId AS UNSIGNED) ORDER BY sort_no ASC, id ASC
