INSERT INTO dtb_member (id, work_id, authority_id, name, login_id, password, sort_no, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:admin AS JSON), '$.adminId') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:admin AS JSON), '$.work') AS UNSIGNED),
       CAST(JSON_VALUE(CAST(:admin AS JSON), '$.authority') AS UNSIGNED),
       JSON_VALUE(CAST(:admin AS JSON), '$.name'),
       JSON_VALUE(CAST(:admin AS JSON), '$.loginId'),
       JSON_VALUE(CAST(:admin AS JSON), '$.passwordHash'),
       COALESCE(CAST(JSON_VALUE(CAST(:admin AS JSON), '$.sortNo') AS UNSIGNED), 0),
       NOW(), NOW(), 'member'
WHERE JSON_VALUE(CAST(:admin AS JSON), '$.adminId') REGEXP '^[0-9]+$'
