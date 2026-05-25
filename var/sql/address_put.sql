INSERT INTO dtb_customer_address (id, customer_id, name01, name02, kana01, kana02, company_name, phone_number, postal_code, pref_id, addr01, addr02, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:address, '$.addressId') AS UNSIGNED),
       CAST(JSON_VALUE(:address, '$.customerId') AS UNSIGNED),
       JSON_VALUE(:address, '$.name01'),
       JSON_VALUE(:address, '$.name02'),
       JSON_VALUE(:address, '$.kana01'),
       JSON_VALUE(:address, '$.kana02'),
       JSON_VALUE(:address, '$.companyName'),
       JSON_VALUE(:address, '$.phoneNumber'),
       JSON_VALUE(:address, '$.postalCode'),
       NULLIF(CAST(JSON_VALUE(:address, '$.pref') AS SIGNED), 0),
       JSON_VALUE(:address, '$.addr01'),
       JSON_VALUE(:address, '$.addr02'),
       NOW(), NOW(), 'customeraddress'
WHERE JSON_VALUE(:address, '$.addressId') REGEXP '^[0-9]+$'
  AND JSON_VALUE(:address, '$.customerId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE
    customer_id = VALUES(customer_id), name01 = VALUES(name01), name02 = VALUES(name02),
    kana01 = VALUES(kana01), kana02 = VALUES(kana02), company_name = VALUES(company_name),
    phone_number = VALUES(phone_number), postal_code = VALUES(postal_code), pref_id = VALUES(pref_id),
    addr01 = VALUES(addr01), addr02 = VALUES(addr02), update_date = NOW()
