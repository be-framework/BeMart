SELECT
  CAST(id AS CHAR),
  CAST(
    tax_rate AS DECIMAL(10, 2)
  ),
  CAST(rounding_type_id AS UNSIGNED),
  DATE_FORMAT(apply_date, '%Y-%m-%d')
FROM
  dtb_tax_rule
WHERE
  :taxRuleId REGEXP '^[0-9]+$'
  AND id = CAST(:taxRuleId AS UNSIGNED)
LIMIT
  1
