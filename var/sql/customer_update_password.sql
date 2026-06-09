UPDATE
  dtb_customer
SET
  password = :passwordHash,
  update_date = NOW()
WHERE
  :customerId REGEXP '^[0-9]+$'
  AND id = CAST(:customerId AS UNSIGNED)
