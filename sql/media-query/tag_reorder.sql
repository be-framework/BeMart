UPDATE dtb_tag SET sort_no = :sortNo, update_date = NOW() WHERE :tagId REGEXP '^[0-9]+$' AND id = CAST(:tagId AS UNSIGNED)
