SELECT tracking_number
FROM dtb_shipping
WHERE order_id = :orderId
ORDER BY id ASC
LIMIT 1
