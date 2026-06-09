UPDATE
  dtb_customer
SET
  reset_key = JSON_VALUE(
    CAST(:token AS CHAR),
    '$.resetKey'
  ),
  reset_expire = JSON_VALUE(
    CAST(:token AS CHAR),
    '$.expiresAt'
  ),
  update_date = NOW()
WHERE
  JSON_VALUE(
    CAST(:token AS CHAR),
    '$.customerId'
  ) REGEXP '^[0-9]+$'
  AND id = CAST(
    JSON_VALUE(
      CAST(:token AS CHAR),
      '$.customerId'
    ) AS UNSIGNED
  )
