UPDATE
  dtb_order
SET
  order_status_id = :newStatus,
  update_date = NOW()
WHERE
  order_no = :orderNo
