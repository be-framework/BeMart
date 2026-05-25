SELECT id, tax_rate, rounding_type_id, apply_date FROM dtb_tax_rule WHERE :taxRuleId REGEXP '^[0-9]+$' AND id = CAST(:taxRuleId AS UNSIGNED) LIMIT 1
