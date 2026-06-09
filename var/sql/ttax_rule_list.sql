SELECT CAST(id AS CHAR), CAST(tax_rate AS DECIMAL(10,2)), CAST(rounding_type_id AS UNSIGNED), DATE_FORMAT(apply_date, '%Y-%m-%d') FROM dtb_tax_rule ORDER BY id ASC
