UPDATE dtb_member
SET sort_no = :sortNo,
    update_date = NOW()
WHERE :adminId REGEXP '^[0-9]+$'
  AND id = CAST(:adminId AS UNSIGNED)
