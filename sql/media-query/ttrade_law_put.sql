INSERT INTO dtb_tradelaw (id, description, create_date, update_date, discriminator_type)
VALUES (1, JSON_VALUE(:entity, '$.body'), NOW(), NOW(), 'tradelaw')
ON DUPLICATE KEY UPDATE description=VALUES(description), update_date=NOW()
