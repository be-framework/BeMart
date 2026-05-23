SELECT o.id, o.order_no, o.customer_id, o.message,
       o.subtotal, o.delivery_fee_total, o.charge, o.discount,
       o.tax, o.total, o.payment_total, o.add_point, o.use_point,
       o.order_status_id, o.order_date, o.payment_date,
       p.payment_method
FROM dtb_order o
LEFT JOIN dtb_payment p ON p.id = o.payment_id
WHERE o.order_no = :orderNo
  AND o.order_status_id <> :processing
LIMIT 1
