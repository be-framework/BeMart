INSERT INTO dtb_delivery (id, name, visible, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:delivery AS CHAR), '$.deliveryId') AS UNSIGNED), JSON_VALUE(CAST(:delivery AS CHAR), '$.deliveryName'), IF(JSON_VALUE(CAST(:delivery AS CHAR), '$.visible') = 'true', 1, 0), NOW(), NOW(), 'delivery'
WHERE JSON_VALUE(CAST(:delivery AS CHAR), '$.deliveryId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE name=VALUES(name), visible=VALUES(visible), update_date=NOW()
