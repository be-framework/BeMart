INSERT INTO dtb_layout (id, layout_name, device_type_id, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:layout AS CHAR), '$.layoutId') AS UNSIGNED), JSON_VALUE(CAST(:layout AS CHAR), '$.layoutName'), CAST(JSON_VALUE(CAST(:layout AS CHAR), '$.deviceType') AS UNSIGNED), NOW(), NOW(), 'layout'
WHERE JSON_VALUE(CAST(:layout AS CHAR), '$.layoutId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE layout_name=VALUES(layout_name), device_type_id=VALUES(device_type_id), update_date=NOW()
