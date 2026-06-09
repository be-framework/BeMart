DELETE FROM
  dtb_tax_rule
WHERE
  :taxRuleId REGEXP '^[0-9]+$'
  AND id = CAST(:taxRuleId AS UNSIGNED)
