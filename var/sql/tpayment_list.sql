SELECT
  CAST(id AS CHAR),
  payment_method,
  CAST(charge AS SIGNED),
  CAST(rule_min AS SIGNED),
  CAST(rule_max AS SIGNED),
  visible
FROM
  dtb_payment
ORDER BY
  id ASC
