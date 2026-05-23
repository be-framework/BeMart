INSERT INTO dtb_shipping
(order_id, name01, name02, tracking_number,
 create_date, update_date, discriminator_type)
VALUES (:orderId, :name01, :name02, :trackingNumber,
        NOW(), NOW(), :discriminator)
