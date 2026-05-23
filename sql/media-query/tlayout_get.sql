SELECT id, layout_name, device_type_id FROM dtb_layout WHERE :layoutId REGEXP '^[0-9]+$' AND id = CAST(:layoutId AS UNSIGNED) LIMIT 1
