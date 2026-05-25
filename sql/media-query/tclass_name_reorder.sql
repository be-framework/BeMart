UPDATE dtb_class_name SET sort_no = :sortNo, update_date = NOW() WHERE :classNameId REGEXP '^[0-9]+$' AND id = CAST(:classNameId AS UNSIGNED)
