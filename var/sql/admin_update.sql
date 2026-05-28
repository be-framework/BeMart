UPDATE dtb_member
SET login_id = JSON_VALUE(:admin, '$.loginId'),
    password = JSON_VALUE(:admin, '$.passwordHash'),
    name = JSON_VALUE(:admin, '$.name'),
    authority_id = CAST(JSON_VALUE(:admin, '$.authority') AS UNSIGNED),
    work_id = CAST(JSON_VALUE(:admin, '$.work') AS UNSIGNED),
    sort_no = COALESCE(CAST(JSON_VALUE(:admin, '$.sortNo') AS UNSIGNED), sort_no),
    update_date = NOW()
WHERE JSON_VALUE(:admin, '$.adminId') REGEXP '^[0-9]+$'
  AND id = CAST(JSON_VALUE(:admin, '$.adminId') AS UNSIGNED)
