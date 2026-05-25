INSERT INTO dtb_tax_rule (id, tax_rate, rounding_type_id, apply_date, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:taxRule, '$.taxRuleId') AS UNSIGNED), CAST(JSON_VALUE(:taxRule, '$.taxRate') AS DECIMAL(10,2)), CAST(JSON_VALUE(:taxRule, '$.roundingType') AS UNSIGNED), CASE WHEN LENGTH(REPLACE(JSON_VALUE(:taxRule, '$.applyDate'), 'T', ' ')) = 10 THEN CONCAT(REPLACE(JSON_VALUE(:taxRule, '$.applyDate'), 'T', ' '), ' 00:00:00') ELSE REPLACE(JSON_VALUE(:taxRule, '$.applyDate'), 'T', ' ') END, NOW(), NOW(), 'taxrule'
WHERE JSON_VALUE(:taxRule, '$.taxRuleId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE tax_rate=VALUES(tax_rate), rounding_type_id=VALUES(rounding_type_id), apply_date=VALUES(apply_date), update_date=NOW()
