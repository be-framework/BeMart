INSERT INTO dtb_order
(customer_id, payment_id, pre_order_id, order_no,
 name01, name02, subtotal, discount, delivery_fee_total,
 charge, tax, total, payment_total, add_point, use_point,
 order_status_id, order_date, payment_date,
 create_date, update_date, discriminator_type)
VALUES (:customerId, :paymentId, :preOrderId, :orderNo,
        :name01, :name02, :subtotal, :discount, :deliveryFeeTotal,
        :charge, :tax, :total, :paymentTotal, :addPoint, :usePoint,
        :orderStatus, :orderDate, :paymentDate,
        NOW(), NOW(), :discriminator)
