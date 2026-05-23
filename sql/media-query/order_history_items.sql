SELECT oi.product_code, oi.product_name, oi.quantity, oi.price
FROM dtb_order_item oi
WHERE oi.order_id = :orderId
  AND (oi.shipping_id = :shippingId OR oi.shipping_id IS NULL)
ORDER BY oi.id ASC
