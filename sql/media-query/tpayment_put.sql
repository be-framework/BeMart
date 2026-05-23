INSERT INTO dtb_payment (id, payment_method, charge, rule_min, rule_max, visible, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:payment, '$.paymentId') AS UNSIGNED), JSON_VALUE(:payment, '$.paymentMethodName'), CAST(JSON_VALUE(:payment, '$.charge') AS SIGNED), CAST(JSON_VALUE(:payment, '$.ruleMin') AS SIGNED), CAST(JSON_VALUE(:payment, '$.ruleMax') AS SIGNED), CAST(JSON_VALUE(:payment, '$.visible') AS UNSIGNED), NOW(), NOW(), 'payment'
WHERE JSON_VALUE(:payment, '$.paymentId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE payment_method=VALUES(payment_method), charge=VALUES(charge), rule_min=VALUES(rule_min), rule_max=VALUES(rule_max), visible=VALUES(visible), update_date=NOW()
