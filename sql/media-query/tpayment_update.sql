UPDATE dtb_payment SET payment_method = :paymentMethod, charge = :charge, rule_min = :ruleMin, rule_max = :ruleMax, visible = :visible, update_date = NOW() WHERE id = :id
