SELECT id, payment_method, charge, rule_min, rule_max, visible FROM dtb_payment WHERE id = :id LIMIT 1
