SELECT
  IFNULL(
    MAX(id),
    0
  ) + 1 AS next_id
FROM
  dtb_payment
