UPDATE dtb_order
SET customer_id = :customerId,
    payment_id = :paymentId,
    subtotal = :subtotal,
    delivery_fee_total = :deliveryFeeTotal,
    charge = :charge,
    discount = :discount,
    tax = :tax,
    total = :total,
    payment_total = :paymentTotal,
    add_point = :addPoint,
    use_point = :usePoint,
    order_status_id = :orderStatus,
    order_date = :orderDate,
    payment_date = :paymentDate,
    update_date = NOW()
WHERE order_no = :orderNo
