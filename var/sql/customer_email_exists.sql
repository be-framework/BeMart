SELECT
  1 AS found
FROM
  dtb_customer
WHERE
  email = :email
LIMIT
  1
