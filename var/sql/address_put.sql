INSERT INTO dtb_customer_address (id, customer_id, name01, name02, kana01, kana02, company_name, phone_number, postal_code, pref_id, addr01, addr02, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:address AS JSON), '$.addressId') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:address AS JSON), '$.customerId') AS UNSIGNED),
       JSON_VALUE(CAST(:address AS JSON), '$.name01'),
       JSON_VALUE(CAST(:address AS JSON), '$.name02'),
       JSON_VALUE(CAST(:address AS JSON), '$.kana01'),
       JSON_VALUE(CAST(:address AS JSON), '$.kana02'),
       JSON_VALUE(CAST(:address AS JSON), '$.companyName'),
       JSON_VALUE(CAST(:address AS JSON), '$.phoneNumber'),
       JSON_VALUE(CAST(:address AS JSON), '$.postalCode'),
       NULLIF(CAST(JSON_VALUE(CAST(:address AS JSON), '$.pref') AS SIGNED), 0),
       JSON_VALUE(CAST(:address AS JSON), '$.addr01'),
       JSON_VALUE(CAST(:address AS JSON), '$.addr02'),
       NOW(), NOW(), 'customeraddress'
WHERE JSON_VALUE(CAST(:address AS JSON), '$.addressId') REGEXP '^[0-9]+$'
  AND JSON_VALUE(CAST(:address AS JSON), '$.customerId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE
    customer_id = VALUES(customer_id), name01 = VALUES(name01), name02 = VALUES(name02),
    kana01 = VALUES(kana01), kana02 = VALUES(kana02), company_name = VALUES(company_name),
    phone_number = VALUES(phone_number), postal_code = VALUES(postal_code), pref_id = VALUES(pref_id),
    addr01 = VALUES(addr01), addr02 = VALUES(addr02), update_date = NOW()
