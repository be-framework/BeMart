UPDATE
  dtb_member
SET
  authority_id = :newAuthority,
  update_date = NOW()
WHERE
  :adminId REGEXP '^[0-9]+$'
  AND id = CAST(:adminId AS UNSIGNED)
