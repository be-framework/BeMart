UPDATE dtb_shipping
SET name01 = :name01,
    name02 = :name02,
    postal_code = :postalCode,
    pref_id = :prefId,
    addr01 = :addr01,
    addr02 = :addr02,
    phone_number = :phoneNumber,
    update_date = NOW()
WHERE id = :id
