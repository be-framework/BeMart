SELECT s.tracking_number
FROM dtb_order o
INNER JOIN dtb_shipping s ON s.order_id = o.id
WHERE o.order_no = :orderNo
ORDER BY s.id ASC
LIMIT 1
