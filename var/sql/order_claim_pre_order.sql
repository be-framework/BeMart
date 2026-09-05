UPDATE
  dtb_order
SET
  order_no = :orderNo,
  order_status_id = 1,
  update_date = NOW()
WHERE
  pre_order_id = :preOrderId
  AND order_status_id = 8
