SELECT o.order_no, oi.product_code, oi.product_name, oi.quantity, oi.price
FROM dtb_order_item oi
INNER JOIN dtb_order o ON o.id = oi.order_id
WHERE o.order_no = :orderNo
ORDER BY oi.id ASC
