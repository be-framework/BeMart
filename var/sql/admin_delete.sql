UPDATE
  dtb_member
SET
  work_id = 0,
  update_date = NOW()
WHERE
  :adminId REGEXP '^[0-9]+$'
  AND id = CAST(:adminId AS UNSIGNED)
