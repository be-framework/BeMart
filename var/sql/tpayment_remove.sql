DELETE FROM
  dtb_payment_option
WHERE
  :paymentId REGEXP '^[0-9]+$'
  AND payment_id = CAST(:paymentId AS UNSIGNED);

DELETE FROM
  dtb_payment
WHERE
  :paymentId REGEXP '^[0-9]+$'
  AND id = CAST(:paymentId AS UNSIGNED)
