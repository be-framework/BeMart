SELECT
  id,
  reset_key,
  COALESCE(
    reset_expire,
    DATE_SUB(
      NOW(),
      INTERVAL 1 SECOND
    )
  ) AS reset_expire
FROM
  dtb_customer
WHERE
  reset_key = :resetKey
LIMIT
  1
