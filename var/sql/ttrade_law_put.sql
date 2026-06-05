INSERT INTO dtb_tradelaw (id, name, description, sort_no, display_order_screen, discriminator_type)
VALUES (1, 'body', JSON_VALUE(CAST(:entity AS CHAR), '$.body'), 1, 1, 'tradelaw')
ON DUPLICATE KEY UPDATE description=VALUES(description)
