INSERT INTO dtb_layout (id, layout_name, device_type_id, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:layout, '$.layoutId') AS UNSIGNED), JSON_VALUE(:layout, '$.layoutName'), CAST(JSON_VALUE(:layout, '$.deviceType') AS UNSIGNED), NOW(), NOW(), 'layout'
WHERE JSON_VALUE(:layout, '$.layoutId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE layout_name=VALUES(layout_name), device_type_id=VALUES(device_type_id), update_date=NOW()
