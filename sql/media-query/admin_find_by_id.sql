SELECT id, login_id, password, name, authority_id, work_id
FROM dtb_member
WHERE :adminId REGEXP '^[0-9]+$'
  AND id = CAST(:adminId AS UNSIGNED)
LIMIT 1
