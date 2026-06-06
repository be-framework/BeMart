SELECT CAST(id AS CHAR), login_id, password, COALESCE(name, ''), COALESCE(authority_id, 0), COALESCE(work_id, 1), sort_no
FROM dtb_member
WHERE :adminId REGEXP '^[0-9]+$'
  AND id = CAST(:adminId AS UNSIGNED)
LIMIT 1
