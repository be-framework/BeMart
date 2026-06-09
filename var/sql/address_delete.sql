DELETE FROM
  dtb_customer_address
WHERE
  :addressId REGEXP '^[0-9]+$'
  AND id = CAST(:addressId AS UNSIGNED)
