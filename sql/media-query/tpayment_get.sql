SELECT id, payment_method, charge, rule_min, rule_max, visible FROM dtb_payment WHERE :paymentId REGEXP '^[0-9]+$' AND id = CAST(:paymentId AS UNSIGNED) LIMIT 1
