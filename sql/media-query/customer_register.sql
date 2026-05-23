INSERT INTO dtb_customer (id, customer_status_id, sex_id, job_id, pref_id, name01, name02, kana01, kana02, company_name, postal_code, addr01, addr02, email, phone_number, birth, password, secret_key, point, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:customer, '$.customerId') AS UNSIGNED),
       CAST(JSON_VALUE(:customer, '$.customerStatus') AS UNSIGNED),
       CAST(JSON_VALUE(:customer, '$.sex') AS UNSIGNED),
       CAST(JSON_VALUE(:customer, '$.job') AS UNSIGNED),
       CAST(JSON_VALUE(:customer, '$.pref') AS UNSIGNED),
       JSON_VALUE(:customer, '$.name01'), JSON_VALUE(:customer, '$.name02'), JSON_VALUE(:customer, '$.kana01'), JSON_VALUE(:customer, '$.kana02'), JSON_VALUE(:customer, '$.companyName'), JSON_VALUE(:customer, '$.postalCode'), JSON_VALUE(:customer, '$.addr01'), JSON_VALUE(:customer, '$.addr02'), JSON_VALUE(:customer, '$.email'), JSON_VALUE(:customer, '$.phoneNumber'), JSON_VALUE(:customer, '$.birth'), JSON_VALUE(:customer, '$.passwordHash'), COALESCE(NULLIF(JSON_VALUE(:customer, '$.secretKey'), ''), LOWER(HEX(RANDOM_BYTES(16)))), CAST(JSON_VALUE(:customer, '$.initialPoint') AS SIGNED), NOW(), NOW(), 'customer'
WHERE JSON_VALUE(:customer, '$.customerId') REGEXP '^[0-9]+$'
