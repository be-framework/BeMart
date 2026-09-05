SELECT
  COALESCE(o.order_no, '') AS order_no
FROM
  dtb_order o
WHERE
  o.pre_order_id = :preOrderId
LIMIT
  1
