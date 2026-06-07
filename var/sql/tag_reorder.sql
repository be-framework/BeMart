UPDATE dtb_tag SET sort_no = :sortNo WHERE :tagId REGEXP '^[0-9]+$' AND id = CAST(:tagId AS UNSIGNED)
