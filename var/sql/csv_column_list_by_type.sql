SELECT csv_type_id, field_name, enabled, sort_no
FROM dtb_csv
WHERE csv_type_id = :csvType
ORDER BY sort_no ASC, id ASC
