UPDATE dtb_shipping s
INNER JOIN dtb_order o ON o.id = s.order_id
SET s.pref_id = NULLIF(CAST(JSON_VALUE(CAST(:address AS JSON), '$.pref') AS SIGNED), 0), s.name01 = JSON_VALUE(CAST(:address AS JSON), '$.name01'), s.name02 = JSON_VALUE(CAST(:address AS JSON), '$.name02'), s.postal_code = JSON_VALUE(CAST(:address AS JSON), '$.postalCode'), s.addr01 = JSON_VALUE(CAST(:address AS JSON), '$.addr01'), s.addr02 = JSON_VALUE(CAST(:address AS JSON), '$.addr02'), s.phone_number = JSON_VALUE(CAST(:address AS JSON), '$.phoneNumber'), s.update_date = NOW()
WHERE o.order_no = JSON_VALUE(CAST(:address AS JSON), '$.orderNo');
INSERT INTO dtb_shipping (order_id, pref_id, name01, name02, postal_code, addr01, addr02, phone_number, create_date, update_date, discriminator_type)
SELECT o.id, NULLIF(CAST(JSON_VALUE(CAST(:address AS JSON), '$.pref') AS SIGNED), 0), JSON_VALUE(CAST(:address AS JSON), '$.name01'), JSON_VALUE(CAST(:address AS JSON), '$.name02'), JSON_VALUE(CAST(:address AS JSON), '$.postalCode'), JSON_VALUE(CAST(:address AS JSON), '$.addr01'), JSON_VALUE(CAST(:address AS JSON), '$.addr02'), JSON_VALUE(CAST(:address AS JSON), '$.phoneNumber'), NOW(), NOW(), 'shipping'
FROM dtb_order o
WHERE o.order_no = JSON_VALUE(CAST(:address AS JSON), '$.orderNo') AND NOT EXISTS (SELECT 1 FROM dtb_shipping existing WHERE existing.order_id = o.id)
LIMIT 1
