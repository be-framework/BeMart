INSERT INTO dtb_customer (id, customer_status_id, sex_id, job_id, pref_id, name01, name02, kana01, kana02, company_name, postal_code, addr01, addr02, email, phone_number, birth, password, secret_key, point, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:customer AS CHAR), '$.customerId') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:customer AS CHAR), '$.customerStatus') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:customer AS CHAR), '$.sex') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:customer AS CHAR), '$.job') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:customer AS CHAR), '$.pref') AS UNSIGNED),
       JSON_VALUE(CAST(:customer AS CHAR), '$.name01'), JSON_VALUE(CAST(:customer AS CHAR), '$.name02'), JSON_VALUE(CAST(:customer AS CHAR), '$.kana01'), JSON_VALUE(CAST(:customer AS CHAR), '$.kana02'), JSON_VALUE(CAST(:customer AS CHAR), '$.companyName'), JSON_VALUE(CAST(:customer AS CHAR), '$.postalCode'), JSON_VALUE(CAST(:customer AS CHAR), '$.addr01'), JSON_VALUE(CAST(:customer AS CHAR), '$.addr02'), JSON_VALUE(CAST(:customer AS CHAR), '$.email'), JSON_VALUE(CAST(:customer AS CHAR), '$.phoneNumber'), JSON_VALUE(CAST(:customer AS CHAR), '$.birth'), JSON_VALUE(CAST(:customer AS CHAR), '$.passwordHash'), COALESCE(NULLIF(JSON_VALUE(CAST(:customer AS CHAR), '$.secretKey'), ''), LOWER(HEX(RANDOM_BYTES(16)))), CAST(JSON_VALUE(CAST(:customer AS CHAR), '$.initialPoint') AS SIGNED), NOW(), NOW(), 'customer'
WHERE JSON_VALUE(CAST(:customer AS CHAR), '$.customerId') REGEXP '^[0-9]+$'
