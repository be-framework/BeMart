INSERT INTO dtb_delivery (id, name, visible, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:delivery, '$.deliveryId') AS UNSIGNED), JSON_VALUE(:delivery, '$.deliveryName'), CAST(JSON_VALUE(:delivery, '$.visible') AS UNSIGNED), NOW(), NOW(), 'delivery'
WHERE JSON_VALUE(:delivery, '$.deliveryId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE name=VALUES(name), visible=VALUES(visible), update_date=NOW()
