UPDATE
  dtb_member
SET
  login_id = JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.loginId'
  ),
  password = JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.passwordHash'
  ),
  name = JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.name'
  ),
  authority_id = CAST(
    JSON_VALUE(
      CAST(:admin AS CHAR),
      '$.authority'
    ) AS UNSIGNED
  ),
  work_id = CAST(
    JSON_VALUE(
      CAST(:admin AS CHAR),
      '$.work'
    ) AS UNSIGNED
  ),
  sort_no = COALESCE(
    CAST(
      JSON_VALUE(
        CAST(:admin AS CHAR),
        '$.sortNo'
      ) AS UNSIGNED
    ),
    sort_no
  ),
  update_date = NOW()
WHERE
  JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.adminId'
  ) REGEXP '^[0-9]+$'
  AND id = CAST(
    JSON_VALUE(
      CAST(:admin AS CHAR),
      '$.adminId'
    ) AS UNSIGNED
  )
