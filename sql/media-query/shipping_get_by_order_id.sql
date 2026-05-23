SELECT name01, name02, postal_code, pref_id, addr01, addr02, phone_number
FROM dtb_shipping
WHERE order_id = :orderId
ORDER BY id ASC
LIMIT 1
