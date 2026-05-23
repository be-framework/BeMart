SELECT id
FROM dtb_order
WHERE order_no = :orderNo
LIMIT 1
