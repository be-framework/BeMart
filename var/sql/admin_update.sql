UPDATE dtb_member
SET login_id = JSON_VALUE(CAST(:admin AS JSON), '$.loginId'),
    password = JSON_VALUE(CAST(:admin AS JSON), '$.passwordHash'),
    name = JSON_VALUE(CAST(:admin AS JSON), '$.name'),
    authority_id = CAST(JSON_VALUE(CAST(:admin AS JSON), '$.authority') AS UNSIGNED),
    work_id = CAST(JSON_VALUE(CAST(:admin AS JSON), '$.work') AS UNSIGNED),
    sort_no = COALESCE(CAST(JSON_VALUE(CAST(:admin AS JSON), '$.sortNo') AS UNSIGNED), sort_no),
    update_date = NOW()
WHERE JSON_VALUE(CAST(:admin AS JSON), '$.adminId') REGEXP '^[0-9]+$'
  AND id = CAST(JSON_VALUE(CAST(:admin AS JSON), '$.adminId') AS UNSIGNED)
