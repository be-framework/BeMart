SELECT CAST(id AS CHAR), layout_name, CAST(device_type_id AS UNSIGNED) FROM dtb_layout WHERE :layoutId REGEXP '^[0-9]+$' AND id = CAST(:layoutId AS UNSIGNED) LIMIT 1
