UPDATE
  dtb_customer
SET
  reset_key = NULL,
  reset_expire = NULL
WHERE
  reset_key = :resetKey
