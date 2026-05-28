INSERT INTO dtb_member (id, work_id, authority_id, name, login_id, password, sort_no, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:admin, '$.adminId') AS UNSIGNED),
       CAST(JSON_VALUE(:admin, '$.work') AS UNSIGNED),
       CAST(JSON_VALUE(:admin, '$.authority') AS UNSIGNED),
       JSON_VALUE(:admin, '$.name'),
       JSON_VALUE(:admin, '$.loginId'),
       JSON_VALUE(:admin, '$.passwordHash'),
       COALESCE(CAST(JSON_VALUE(:admin, '$.sortNo') AS UNSIGNED), 0),
       NOW(), NOW(), 'member'
WHERE JSON_VALUE(:admin, '$.adminId') REGEXP '^[0-9]+$'
