SELECT pre_order_id, customer_id, payment_id, delivery_fee_total
FROM dtb_order
WHERE pre_order_id = :preOrderId
  AND order_status_id = 8
LIMIT 1
