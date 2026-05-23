INSERT INTO dtb_shipping
(order_id, pref_id, name01, name02, postal_code,
 addr01, addr02, phone_number,
 create_date, update_date, discriminator_type)
VALUES (:orderId, :prefId, :name01, :name02, :postalCode,
        :addr01, :addr02, :phoneNumber, NOW(), NOW(), :discriminator)
