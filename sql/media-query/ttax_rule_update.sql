UPDATE dtb_tax_rule SET tax_rate = :taxRate, rounding_type_id = NULL, apply_date = :applyDate, update_date = NOW() WHERE id = :id
