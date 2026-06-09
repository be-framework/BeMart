INSERT INTO dtb_member (
  id, work_id, authority_id, name, login_id,
  password, sort_no, create_date, update_date,
  discriminator_type
)
SELECT
  CAST(
    JSON_VALUE(
      CAST(:admin AS CHAR),
      '$.adminId'
    ) AS UNSIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:admin AS CHAR),
      '$.work'
    ) AS UNSIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:admin AS CHAR),
      '$.authority'
    ) AS UNSIGNED
  ),
  JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.name'
  ),
  JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.loginId'
  ),
  JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.passwordHash'
  ),
  COALESCE(
    CAST(
      JSON_VALUE(
        CAST(:admin AS CHAR),
        '$.sortNo'
      ) AS UNSIGNED
    ),
    0
  ),
  NOW(),
  NOW(),
  'member'
WHERE
  JSON_VALUE(
    CAST(:admin AS CHAR),
    '$.adminId'
  ) REGEXP '^[0-9]+$'
