SELECT IFNULL(MAX(sort_no), 0) + 1 AS next_sort FROM dtb_class_category WHERE class_name_id = :classNameId
