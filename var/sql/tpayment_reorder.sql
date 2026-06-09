UPDATE
  dtb_payment
SET
  sort_no = :sortNo,
  update_date = NOW()
WHERE
  :paymentId REGEXP '^[0-9]+$'
  AND id = CAST(:paymentId AS UNSIGNED)
