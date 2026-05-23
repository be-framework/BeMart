SELECT order_no, pre_order_id, customer_id, payment_id,
       subtotal, delivery_fee_total, charge, discount, tax, total, payment_total,
       add_point, use_point, order_status_id, order_date, payment_date
FROM dtb_order
WHERE customer_id = :customerId
  AND order_status_id <> :processing
ORDER BY order_date DESC, id DESC
LIMIT :limit OFFSET :offset
