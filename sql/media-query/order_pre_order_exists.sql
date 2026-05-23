SELECT 1 AS present
FROM dtb_order
WHERE pre_order_id = :preOrderId
LIMIT 1
